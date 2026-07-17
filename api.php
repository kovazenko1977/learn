<?php
// api.php - Backend API supporting JWT auth, Ticket CRUD, comments, settings, metrics, and JSON/MySQL sync.

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        exit(0);
    }
}

require_once 'db.php';

// Helper function to send JSON response
function sendJSON($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// JWT Helper Functions
$jwt_secret = 'super-secret-crm-key-13579';

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function generateJWT($user) {
    global $jwt_secret;
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'full_name' => $user['full_name'] ?? '',
        'exp' => time() + 86400 * 7 // Valid for 7 days
    ]);

    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $jwt_secret, true);
    $base64UrlSignature = base64UrlEncode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verifyAndDecodeJWT() {
    global $jwt_secret;
    $authHeader = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (empty($authHeader)) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }

    if (empty($authHeader) && isset($_GET['token'])) {
        $authHeader = 'Bearer ' . $_GET['token'];
    }

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) === 3) {
            $header = base64UrlDecode($tokenParts[0]);
            $payload = base64UrlDecode($tokenParts[1]);
            $signatureProvided = $tokenParts[2];

            // Check signature
            $signatureCalculated = base64UrlEncode(hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], $jwt_secret, true));
            if ($signatureProvided === $signatureCalculated) {
                $payloadArr = json_decode($payload, true);
                if (isset($payloadArr['exp']) && $payloadArr['exp'] > time()) {
                    return $payloadArr;
                }
            }
        }
    }
    return null;
}

// Ensure first-time init contains default settings if they don't exist
$db = DB::getInstance();
if (!$db->getSetting('categories')) {
    $db->saveSetting('categories', ['Электрика', 'Сантехника', 'Оборудование', 'Офис', 'Другое']);
}
if (!$db->getSetting('priorities')) {
    $db->saveSetting('priorities', [
        ['key' => 'low', 'label' => 'Низкий', 'color' => '#28a745'],
        ['key' => 'medium', 'label' => 'Средний', 'color' => '#ffc107'],
        ['key' => 'high', 'label' => 'Высокий', 'color' => '#dc3545']
    ]);
}
if (!$db->getSetting('sla')) {
    $db->saveSetting('sla', [
        'low' => 72,     // 72 hours
        'medium' => 24,  // 24 hours
        'high' => 4      // 4 hours
    ]);
}
if (!$db->getSetting('form_fields')) {
    $db->saveSetting('form_fields', [
        ['id' => 'room_number', 'label' => 'Номер кабинета / помещения', 'type' => 'text', 'required' => true],
        ['id' => 'contact_phone', 'label' => 'Контактный телефон на месте', 'type' => 'text', 'required' => false]
    ]);
}

// Only run the API routing if executed directly (not included in test runner)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'api.php' || php_sapi_name() === 'cli' && basename($argv[0] ?? '') === 'api.php') {
    handleApiRoute();
}

function handleApiRoute() {
    global $db, $currentUser;
    $action = $_GET['action'] ?? '';

    // --- Authentication Enforcement for Protected Endpoints ---
    $publicActions = ['login', 'register', 'recover', 'reset_password_mock'];
    if (!in_array($action, $publicActions)) {
        $currentUser = verifyAndDecodeJWT();
        if (!$currentUser) {
            sendJSON(['error' => 'Не авторизован или сессия истекла.'], 401);
        }
    }

    // --- Unauthenticated Endpoints ---

    if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        sendJSON(['error' => 'Username and password are required.'], 400);
    }

    $user = $db->getUserByUsername($username);
    if ($user && password_verify($password, $user['password_hash'])) {
        $token = generateJWT($user);
        $db->addLog($user['username'], 'login', 'user', $user['id'], 'User logged in successfully');
        sendJSON([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'notifications_freq' => $user['notifications_freq'] ?? 'immediate'
            ]
        ]);
    } else {
        $db->addLog($username ?: 'anonymous', 'failed_login', 'user', '', 'Failed login attempt');
        sendJSON(['error' => 'Неверный логин или пароль.'], 401);
    }
}

if ($action === 'register') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = trim($input['role'] ?? 'creator'); // creator = Ответственный сотрудник
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');

    if (empty($username) || empty($password)) {
        sendJSON(['error' => 'Имя пользователя и пароль обязательны.'], 400);
    }

    $existing = $db->getUserByUsername($username);
    if ($existing) {
        sendJSON(['error' => 'Пользователь с таким именем уже существует.'], 400);
    }

    // Whitelist role values
    $allowedRoles = ['admin', 'manager', 'creator', 'executor'];
    if (!in_array($role, $allowedRoles)) {
        $role = 'creator';
    }

    $user = [
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'role' => $role,
        'full_name' => $full_name,
        'email' => $email,
        'phone' => trim($input['phone'] ?? ''),
        'notifications_freq' => 'immediate',
        'notify_email' => 1,
        'notify_push' => 1,
        'notify_tg' => 0
    ];

    $saved = $db->saveUser($user);
    $db->addLog($username, 'register', 'user', $saved['id'], 'User registered self');

    $token = generateJWT($saved);
    sendJSON([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $saved['id'],
            'username' => $saved['username'],
            'role' => $saved['role'],
            'full_name' => $saved['full_name'],
            'email' => $saved['email'],
            'phone' => $saved['phone']
        ]
    ]);
}

if ($action === 'recover') {
    $input = json_decode(file_get_contents('php://input'), true);
    $usernameOrEmail = trim($input['identity'] ?? '');

    if (empty($usernameOrEmail)) {
        sendJSON(['error' => 'Введите логин или Email.'], 400);
    }

    // Search user
    $users = $db->getUsers();
    $foundUser = null;
    foreach ($users as $u) {
        if (strtolower($u['username']) === strtolower($usernameOrEmail) || strtolower($u['email'] ?? '') === strtolower($usernameOrEmail)) {
            $foundUser = $u;
            break;
        }
    }

    if ($foundUser) {
        // Mock recovery token and log it
        $tempToken = bin2hex(random_bytes(16));
        $db->addLog('system', 'password_recovery_requested', 'user', $foundUser['id'], "Password recovery link: /api.php?action=reset_password_mock&token={$tempToken}&user={$foundUser['id']}");

        // Simulating sending recovery message
        sendJSON([
            'success' => true,
            'message' => 'Инструкция по восстановлению пароля выслана (смотри логи системы). Временная ссылка сгенерирована.',
            'mock_link' => "?action=reset_password_mock&token={$tempToken}&user={$foundUser['id']}"
        ]);
    } else {
        sendJSON(['error' => 'Пользователь не найден.'], 404);
    }
}

if ($action === 'reset_password_mock') {
    $token = $_GET['token'] ?? '';
    $userId = $_GET['user'] ?? '';
    if (empty($token) || empty($userId)) {
        die("Invalid token or user.");
    }

    $user = $db->getUserById($userId);
    if (!$user) {
        die("User not found.");
    }

    // Auto reset to "password123" for simple demo & security
    $newPass = "password123";
    $user['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT);
    $db->saveUser($user);
    $db->addLog($user['username'], 'password_reset_success', 'user', $user['id'], 'Password reset via mock recovery link to default');

    echo "<h1>Пароль успешно сброшен!</h1><p>Новый пароль для пользователя <b>{$user['username']}</b>: <mark>password123</mark></p><p><a href='/'>Перейти к авторизации</a></p>";
    exit;
}

// Check role authorizations helper
function authorize($allowedRoles) {
    global $currentUser;
    if (!$currentUser || !in_array($currentUser['role'], $allowedRoles)) {
        sendJSON(['error' => 'Доступ запрещён.'], 403);
    }
}

// --- Authenticated Action Mappings ---

if ($action === 'profile') {
    $user = $db->getUserById($currentUser['id']);
    if (!$user) {
        sendJSON(['error' => 'Пользователь не найден.'], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        sendJSON([
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'full_name' => $user['full_name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'notifications_freq' => $user['notifications_freq'] ?? 'immediate',
            'notify_email' => $user['notify_email'] ?? 1,
            'notify_push' => $user['notify_push'] ?? 1,
            'notify_tg' => $user['notify_tg'] ?? 0
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $user['full_name'] = trim($input['full_name'] ?? $user['full_name']);
        $user['email'] = trim($input['email'] ?? $user['email']);
        $user['phone'] = trim($input['phone'] ?? $user['phone']);
        $user['notifications_freq'] = trim($input['notifications_freq'] ?? $user['notifications_freq']);
        $user['notify_email'] = isset($input['notify_email']) ? (int)$input['notify_email'] : $user['notify_email'];
        $user['notify_push'] = isset($input['notify_push']) ? (int)$input['notify_push'] : $user['notify_push'];
        $user['notify_tg'] = isset($input['notify_tg']) ? (int)$input['notify_tg'] : $user['notify_tg'];

        if (!empty($input['password'])) {
            $user['password_hash'] = password_hash(trim($input['password']), PASSWORD_BCRYPT);
        }

        $db->saveUser($user);
        $db->addLog($currentUser['username'], 'update_profile', 'user', $user['id'], 'Updated own profile details');
        sendJSON(['success' => true, 'message' => 'Профиль обновлен.']);
    }
}

if ($action === 'users') {
    authorize(['admin', 'manager']);
    $users = $db->getUsers();
    $sanitized = [];
    foreach ($users as $u) {
        $sanitized[] = [
            'id' => $u['id'],
            'username' => $u['username'],
            'role' => $u['role'],
            'full_name' => $u['full_name'] ?? '',
            'email' => $u['email'] ?? '',
            'phone' => $u['phone'] ?? ''
        ];
    }
    sendJSON($sanitized);
}

if ($action === 'user_save') {
    authorize(['admin']);
    $input = json_decode(file_get_contents('php://input'), true);

    $userId = $input['id'] ?? null;
    $username = trim($input['username'] ?? '');
    $role = trim($input['role'] ?? 'creator');
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if (empty($username)) {
        sendJSON(['error' => 'Имя пользователя обязательно.'], 400);
    }

    if (!$userId) {
        $existing = $db->getUserByUsername($username);
        if ($existing) {
            sendJSON(['error' => 'Имя пользователя уже занято.'], 400);
        }
        $password = trim($input['password'] ?? 'user123');
        $user = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'notifications_freq' => 'immediate',
            'notify_email' => 1,
            'notify_push' => 1,
            'notify_tg' => 0
        ];
    } else {
        $user = $db->getUserById($userId);
        if (!$user) {
            sendJSON(['error' => 'Пользователь не найден.'], 404);
        }

        // check username collision
        $existing = $db->getUserByUsername($username);
        if ($existing && (string)$existing['id'] !== (string)$userId) {
            sendJSON(['error' => 'Имя пользователя уже занято.'], 400);
        }

        $user['username'] = $username;
        $user['role'] = $role;
        $user['full_name'] = $full_name;
        $user['email'] = $email;
        $user['phone'] = $phone;

        if (!empty($input['password'])) {
            $user['password_hash'] = password_hash(trim($input['password']), PASSWORD_BCRYPT);
        }
    }

    $saved = $db->saveUser($user);
    $db->addLog($currentUser['username'], $userId ? 'admin_edit_user' : 'admin_create_user', 'user', $saved['id'], "Admin saved user: {$saved['username']} (role: {$saved['role']})");
    sendJSON(['success' => true, 'user' => $saved]);
}

if ($action === 'user_delete') {
    authorize(['admin']);
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['id'] ?? null;

    if (!$userId) {
        sendJSON(['error' => 'ID пользователя не указан.'], 400);
    }

    if ((string)$userId === (string)$currentUser['id']) {
        sendJSON(['error' => 'Нельзя удалить самого себя.'], 400);
    }

    $user = $db->getUserById($userId);
    if ($user) {
        $db->deleteUser($userId);
        $db->addLog($currentUser['username'], 'admin_delete_user', 'user', $userId, "Admin deleted user: {$user['username']}");
        sendJSON(['success' => true]);
    } else {
        sendJSON(['error' => 'Пользователь не найден.'], 404);
    }
}

// --- Tickets Endpoints ---

if ($action === 'tickets') {
    $tickets = $db->getTickets();
    $users = $db->getUsers();

    // Create users lookup map
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = [
            'username' => $u['username'],
            'full_name' => $u['full_name'] ?? $u['username'],
            'role' => $u['role']
        ];
    }

    // Map status, assignee, creator details, check SLA violation
    foreach ($tickets as &$t) {
        $t['creator_name'] = $userMap[$t['created_by']]['full_name'] ?? 'System';
        $t['assignee_name'] = isset($t['assignee_id']) ? ($userMap[$t['assignee_id']]['full_name'] ?? 'Не назначен') : 'Не назначен';

        // SLA Control calculation
        $t['sla_expired'] = false;
        if ($t['status'] !== 'completed' && $t['status'] !== 'rejected' && $t['status'] !== 'выполнено' && $t['status'] !== 'отклонено') {
            if (!empty($t['sla_deadline']) && strtotime($t['sla_deadline']) < time()) {
                $t['sla_expired'] = true;
            }
        }
    }

    // Role-based scoping
    // Admin & Manager see everything.
    // Creator (Ответственный сотрудник) sees tickets they created.
    // Executor (Исполнитель) sees tickets assigned to them.
    $role = $currentUser['role'];
    $filtered = [];
    foreach ($tickets as $t) {
        if ($role === 'admin' || $role === 'manager') {
            $filtered[] = $t;
        } elseif ($role === 'creator') {
            if ((string)$t['created_by'] === (string)$currentUser['id']) {
                $filtered[] = $t;
            }
        } elseif ($role === 'executor') {
            if (isset($t['assignee_id']) && (string)$t['assignee_id'] === (string)$currentUser['id']) {
                $filtered[] = $t;
            }
        }
    }

    sendJSON($filtered);
}

if ($action === 'ticket_save') {
    $input = json_decode(file_get_contents('php://input'), true);

    $ticketId = $input['id'] ?? null;
    $title = trim($input['title'] ?? '');
    $category = trim($input['category'] ?? '');
    $priority = trim($input['priority'] ?? 'medium');
    $description = trim($input['description'] ?? '');
    $customFields = $input['custom_fields'] ?? [];

    // Authorization checks:
    // Only creator & admin/manager can create a ticket.
    if (!$ticketId) {
        authorize(['admin', 'manager', 'creator']);
        if (empty($title)) {
            sendJSON(['error' => 'Тема заявки обязательна.'], 400);
        }

        // Calculate SLA deadline
        $slaSetting = $db->getSetting('sla');
        $hoursToAdd = isset($slaSetting[$priority]) ? (int)$slaSetting[$priority] : 24;
        $slaDeadline = date('Y-m-d H:i:s', time() + $hoursToAdd * 3600);

        $ticket = [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'priority' => $priority,
            'status' => 'new',
            'created_by' => $currentUser['id'],
            'assignee_id' => null,
            'sla_deadline' => $slaDeadline,
            'custom_fields' => $customFields
        ];

        $saved = $db->saveTicket($ticket);
        $db->addLog($currentUser['username'], 'create_ticket', 'ticket', $saved['id'], "Created ticket '{$saved['title']}'");
        sendJSON(['success' => true, 'ticket' => $saved]);
    } else {
        // Edit / Update Status / Assignee
        $ticket = $db->getTicketById($ticketId);
        if (!$ticket) {
            sendJSON(['error' => 'Заявка не найдена.'], 404);
        }

        // Role permissions checks for modifying ticket
        // Creator can edit their own ticket details if it's new
        // Manager/Admin can edit anything and assign executors
        // Executor can only change status or add comments
        $role = $currentUser['role'];

        if ($role === 'creator') {
            if ((string)$ticket['created_by'] !== (string)$currentUser['id']) {
                sendJSON(['error' => 'Нет прав для изменения этой заявки.'], 403);
            }
            // creator can change title, description, custom_fields, priority, category
            $ticket['title'] = $title ?: $ticket['title'];
            $ticket['description'] = $description;
            $ticket['category'] = $category ?: $ticket['category'];
            $ticket['priority'] = $priority ?: $ticket['priority'];
            $ticket['custom_fields'] = $customFields;
        } elseif ($role === 'executor') {
            // Executor can only update Status to in_work, completed, rejected
            if ((string)$ticket['assignee_id'] !== (string)$currentUser['id']) {
                sendJSON(['error' => 'Заявка не назначена вам.'], 403);
            }
            if (isset($input['status'])) {
                $allowedStatus = ['new', 'assigned', 'in_work', 'completed', 'rejected', 'назначена', 'в работе', 'выполнено', 'отклонено'];
                if (in_array($input['status'], $allowedStatus)) {
                    $ticket['status'] = $input['status'];
                }
            }
        } else {
            // Admin or Manager can edit everything including Assignee and Status
            if (isset($input['title'])) $ticket['title'] = $title;
            if (isset($input['description'])) $ticket['description'] = $description;
            if (isset($input['category'])) $ticket['category'] = $category;
            if (isset($input['priority'])) $ticket['priority'] = $priority;
            if (isset($input['status'])) $ticket['status'] = $input['status'];
            if (isset($input['custom_fields'])) $ticket['custom_fields'] = $customFields;

            if (array_key_exists('assignee_id', $input)) {
                $ticket['assignee_id'] = $input['assignee_id'] ? (int)$input['assignee_id'] : null;
                // If assignment changes, change status to assigned/назначена if it was new
                if ($ticket['assignee_id'] && ($ticket['status'] === 'new' || $ticket['status'] === 'новая')) {
                    $ticket['status'] = 'assigned';
                }
            }
        }

        $saved = $db->saveTicket($ticket);
        $db->addLog($currentUser['username'], 'update_ticket', 'ticket', $saved['id'], "Updated ticket status to '{$saved['status']}'");
        sendJSON(['success' => true, 'ticket' => $saved]);
    }
}

// Bulk ticket assignment
if ($action === 'ticket_bulk_assign') {
    authorize(['admin', 'manager']);
    $input = json_decode(file_get_contents('php://input'), true);
    $ticketIds = $input['ticket_ids'] ?? [];
    $assigneeId = $input['assignee_id'] ?? null;

    if (empty($ticketIds)) {
        sendJSON(['error' => 'Не выбраны заявки.'], 400);
    }

    $count = 0;
    foreach ($ticketIds as $id) {
        $ticket = $db->getTicketById($id);
        if ($ticket) {
            $ticket['assignee_id'] = $assigneeId ? (int)$assigneeId : null;
            if ($ticket['assignee_id'] && ($ticket['status'] === 'new' || $ticket['status'] === 'новая')) {
                $ticket['status'] = 'assigned';
            }
            $db->saveTicket($ticket);
            $count++;
        }
    }

    $db->addLog($currentUser['username'], 'bulk_assign', 'tickets', implode(',', $ticketIds), "Bulk assigned {$count} tickets to ID: {$assigneeId}");
    sendJSON(['success' => true, 'message' => "Успешно назначено заявок: {$count}."]);
}

if ($action === 'ticket_delete') {
    authorize(['admin']);
    $input = json_decode(file_get_contents('php://input'), true);
    $ticketId = $input['id'] ?? null;

    if (!$ticketId) {
        sendJSON(['error' => 'ID заявки не указан.'], 400);
    }

    $ticket = $db->getTicketById($ticketId);
    if ($ticket) {
        $db->deleteTicket($ticketId);
        $db->addLog($currentUser['username'], 'delete_ticket', 'ticket', $ticketId, "Deleted ticket '{$ticket['title']}'");
        sendJSON(['success' => true]);
    } else {
        sendJSON(['error' => 'Заявка не найдена.'], 404);
    }
}

// --- Comments & Safe Uploads ---

if ($action === 'comments') {
    $ticketId = $_GET['ticket_id'] ?? null;
    if (!$ticketId) {
        sendJSON(['error' => 'ID заявки обязателен.'], 400);
    }

    $comments = $db->getCommentsByTicketId($ticketId);
    $users = $db->getUsers();
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u['full_name'] ?? $u['username'];
    }

    foreach ($comments as &$c) {
        $c['user_name'] = $userMap[$c['user_id']] ?? 'Unknown';
    }
    sendJSON($comments);
}

if ($action === 'comment_save') {
    $ticketId = $_POST['ticket_id'] ?? null;
    $commentText = trim($_POST['comment_text'] ?? '');

    if (!$ticketId) {
        sendJSON(['error' => 'ID заявки обязателен.'], 400);
    }

    $attachmentPath = null;
    $attachmentName = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Whitelist of strictly safe and allowed extensions:
        $safeExtensions = ['webm', 'wav', 'mp3', 'ogg', 'm4a', 'png', 'jpg', 'jpeg', 'gif', 'pdf', 'xls', 'xlsx', 'txt', 'doc', 'docx'];
        if (!in_array($ext, $safeExtensions)) {
            sendJSON(['error' => 'Недопустимый формат файла. Разрешены только аудио, изображения и стандартные документы.'], 400);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $file['name']);
        $uniqueName = uniqid() . '_' . $safeName;
        $dest = __DIR__ . '/uploads/' . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $attachmentPath = 'uploads/' . $uniqueName;
            $attachmentName = $file['name'];
        } else {
            sendJSON(['error' => 'Ошибка сохранения файла.'], 500);
        }
    }

    if (empty($commentText) && empty($attachmentPath)) {
        sendJSON(['error' => 'Комментарий не может быть пустым.'], 400);
    }

    $comment = [
        'ticket_id' => (int)$ticketId,
        'user_id' => $currentUser['id'],
        'comment_text' => $commentText,
        'attachment_path' => $attachmentPath,
        'attachment_name' => $attachmentName
    ];

    $saved = $db->addComment($comment);
    $db->addLog($currentUser['username'], 'add_comment', 'ticket', $ticketId, "Added comment. Attachment: " . ($attachmentName ?: 'None'));

    // Simulate instantaneous email notification on comments
    // In real app, mail() would be sent. We'll write to system log instead.
    $db->addLog('system', 'notification', 'email', $ticketId, "Email notification sent about new comment on Ticket ID {$ticketId}");

    sendJSON(['success' => true, 'comment' => $saved]);
}

// --- Admin Settings & Form Builder canvas ---

if ($action === 'settings') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        sendJSON([
            'categories' => $db->getSetting('categories'),
            'priorities' => $db->getSetting('priorities'),
            'sla' => $db->getSetting('sla'),
            'form_fields' => $db->getSetting('form_fields'),
            'storage_mode' => $db->getMode()
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        authorize(['admin']);
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['categories'])) {
            $db->saveSetting('categories', $input['categories']);
        }
        if (isset($input['priorities'])) {
            $db->saveSetting('priorities', $input['priorities']);
        }
        if (isset($input['sla'])) {
            $db->saveSetting('sla', $input['sla']);
        }
        if (isset($input['form_fields'])) {
            $db->saveSetting('form_fields', $input['form_fields']);
        }

        $db->addLog($currentUser['username'], 'update_settings', 'system', 'settings', 'Admin updated system settings');
        sendJSON(['success' => true, 'message' => 'Настройки успешно сохранены.']);
    }
}

if ($action === 'switch_storage') {
    authorize(['admin']);
    $input = json_decode(file_get_contents('php://input'), true);
    $newMode = $input['storage_mode'] ?? 'json';
    $mysqlConfig = $input['mysql'] ?? null;

    $result = $db->switchStorage($newMode, $mysqlConfig);
    if ($result['success']) {
        $db->addLog($currentUser['username'], 'switch_storage', 'system', $newMode, $result['message']);
        sendJSON(['success' => true, 'message' => $result['message']]);
    } else {
        sendJSON(['error' => $result['message']], 400);
    }
}

// --- Dashboard / KPI Analytics ---

if ($action === 'dashboard') {
    $tickets = $db->getTickets();
    $users = $db->getUsers();

    $total = count($tickets);
    $statusCounts = [
        'new' => 0,
        'assigned' => 0,
        'in_work' => 0,
        'completed' => 0,
        'rejected' => 0
    ];

    // mapping and translations of status codes
    $statusMap = [
        'new' => 'new', 'новая' => 'new',
        'assigned' => 'assigned', 'назначена' => 'assigned',
        'in_work' => 'in_work', 'в работе' => 'in_work',
        'completed' => 'completed', 'выполнено' => 'completed',
        'rejected' => 'rejected', 'отклонено' => 'rejected'
    ];

    $slaViolations = 0;
    $totalCompletionTime = 0;
    $completedCount = 0;

    $workload = []; // user_id => count of active tasks
    foreach ($users as $u) {
        if ($u['role'] === 'executor') {
            $workload[$u['id']] = [
                'full_name' => $u['full_name'] ?? $u['username'],
                'active_tickets' => 0
            ];
        }
    }

    foreach ($tickets as $t) {
        $st = $statusMap[strtolower($t['status'])] ?? 'new';
        $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;

        // SLA compliance
        if ($st !== 'completed' && $st !== 'rejected') {
            if (!empty($t['sla_deadline']) && strtotime($t['sla_deadline']) < time()) {
                $slaViolations++;
            }
        }

        // Average Completion Time Calculation
        if ($st === 'completed') {
            $created = strtotime($t['created_at']);
            $updated = strtotime($t['updated_at']);
            if ($updated > $created) {
                $totalCompletionTime += ($updated - $created);
                $completedCount++;
            }
        }

        // Staff workload
        if ($st !== 'completed' && $st !== 'rejected' && !empty($t['assignee_id'])) {
            if (isset($workload[$t['assignee_id']])) {
                $workload[$t['assignee_id']]['active_tickets']++;
            }
        }
    }

    $avgHours = 0;
    if ($completedCount > 0) {
        $avgHours = round($totalCompletionTime / (3600 * $completedCount), 1);
    }

    sendJSON([
        'total' => $total,
        'by_status' => $statusCounts,
        'sla_violations' => $slaViolations,
        'average_completion_hours' => $avgHours,
        'workload' => array_values($workload)
    ]);
}

// --- CSV Export (Excel Compatible) ---

if ($action === 'export_csv') {
    // Only admins or managers should export reports
    authorize(['admin', 'manager']);

    $tickets = $db->getTickets();
    $users = $db->getUsers();
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u['full_name'] ?? $u['username'];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="crm_tickets_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header
    fputcsv($output, ['ID', 'Тема', 'Категория', 'Приоритет', 'Статус', 'Создатель', 'Исполнитель', 'Создана', 'Обновлена', 'Дедлайн SLA', 'Описание']);

    foreach ($tickets as $t) {
        $creatorName = $userMap[$t['created_by']] ?? 'System';
        $assigneeName = isset($t['assignee_id']) ? ($userMap[$t['assignee_id']] ?? 'Не назначен') : 'Не назначен';

        fputcsv($output, [
            $t['id'],
            $t['title'],
            $t['category'] ?? '',
            $t['priority'] ?? 'medium',
            $t['status'] ?? 'new',
            $creatorName,
            $assigneeName,
            $t['created_at'] ?? '',
            $t['updated_at'] ?? '',
            $t['sla_deadline'] ?? '',
            $t['description'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// --- Logs Endpoints ---

if ($action === 'logs') {
    authorize(['admin']);
    $logs = $db->getLogs();
    sendJSON($logs);
}

sendJSON(['error' => 'Запрос не распознан.'], 404);
}
