<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/helpers.php';

$storage = StorageFactory::getStorage();

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$endpoint = preg_replace('#^.*?api/#', '', $parsedPath);
$endpoint = trim($endpoint, '/');
$method = $_SERVER['REQUEST_METHOD'];

$inputJson = file_get_contents('php://input');
$body = [];
if (!empty($inputJson)) {
    $decoded = json_decode($inputJson, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}
$inputData = array_merge($_POST, $body);

try {
    switch ($endpoint) {
        case 'auth/login':
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }
            $username = trim((string)($inputData['username'] ?? ''));
            $password = (string)($inputData['password'] ?? '');

            if ($username === '' || $password === '') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Логин и пароль обязательны'], 400);
                break;
            }

            $user = $storage->findOneBy('users', ['username' => $username]);
            if (!$user || !password_verify($password, $user['password'])) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Неверное имя пользователя или пароль'], 401);
                break;
            }

            if (($user['status'] ?? 'active') !== 'active') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Ваша учетная запись заблокирована'], 403);
                break;
            }

            $tokenPayload = [
                'sub' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'name' => $user['name']
            ];
            $token = AuthHelper::generateToken($tokenPayload);

            AuthHelper::logAction($storage, $user, 'login', 'Успешный вход в систему');

            $userData = $user;
            unset($userData['password']);

            AuthHelper::jsonResponse([
                'success' => true,
                'token' => $token,
                'user' => $userData
            ]);
            break;

        case 'auth/me':
            $currentUser = AuthHelper::requireAuth($storage);
            unset($currentUser['password']);
            AuthHelper::jsonResponse(['success' => true, 'user' => $currentUser]);
            break;

        case 'auth/profile':
            $currentUser = AuthHelper::requireAuth($storage);
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }

            $updates = [];
            if (isset($inputData['name']) && trim($inputData['name']) !== '') {
                $updates['name'] = trim((string)$inputData['name']);
            }
            if (isset($inputData['email']) && filter_var($inputData['email'], FILTER_VALIDATE_EMAIL)) {
                $updates['email'] = trim((string)$inputData['email']);
            }
            if (isset($inputData['phone'])) {
                $updates['phone'] = trim((string)$inputData['phone']);
            }
            if (isset($inputData['department'])) {
                $updates['department'] = trim((string)$inputData['department']);
            }
            if (!empty($inputData['new_password'])) {
                if (strlen($inputData['new_password']) < 6) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Пароль должен содержать минимум 6 символов'], 400);
                    break;
                }
                $updates['password'] = password_hash((string)$inputData['new_password'], PASSWORD_BCRYPT);
            }

            $updated = $storage->update('users', $currentUser['id'], $updates);
            AuthHelper::logAction($storage, $currentUser, 'update_profile', 'Обновление личного профиля');

            unset($updated['password']);
            AuthHelper::jsonResponse(['success' => true, 'user' => $updated, 'message' => 'Профиль успешно обновлен']);
            break;

        case 'auth/reset-password':
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }
            $usernameOrEmail = trim((string)($inputData['identity'] ?? ''));
            if ($usernameOrEmail === '') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Укажите логин или email'], 400);
                break;
            }

            $user = $storage->findOneBy('users', ['username' => $usernameOrEmail]);
            if (!$user) {
                $user = $storage->findOneBy('users', ['email' => $usernameOrEmail]);
            }

            if (!$user) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Пользователь с такими данными не найден'], 404);
                break;
            }

            $tempPass = substr(bin2hex(random_bytes(4)), 0, 8);
            $storage->update('users', $user['id'], [
                'password' => password_hash($tempPass, PASSWORD_BCRYPT)
            ]);

            AuthHelper::logAction($storage, $user, 'reset_password', "Сброс пароля для {$user['username']}");

            AuthHelper::jsonResponse([
                'success' => true,
                'message' => 'Пароль успешно сброшен. Временный пароль для входа: ' . $tempPass,
                'temp_password' => $tempPass
            ]);
            break;

        case 'tickets':
            $currentUser = AuthHelper::requireAuth($storage);

            if ($method === 'GET') {
                $allTickets = $storage->getAll('tickets');
                $role = $currentUser['role'];
                $filtered = [];

                $filterStatus = $_GET['status'] ?? '';
                $filterCategory = $_GET['category'] ?? '';
                $filterPriority = $_GET['priority'] ?? '';
                $filterSearch = trim($_GET['search'] ?? '');
                $filterExecutor = $_GET['executor_id'] ?? '';
                $filterArchive = isset($_GET['archive']) && $_GET['archive'] === '1';

                foreach ($allTickets as $t) {
                    if ($role === 'requester' && (int)($t['requester_id'] ?? 0) !== (int)$currentUser['id']) {
                        continue;
                    }
                    if ($role === 'executor') {
                        if ((int)($t['executor_id'] ?? 0) !== (int)$currentUser['id']) {
                            continue;
                        }
                    }

                    $isArchived = in_array($t['status'], ['completed', 'rejected'], true);
                    if ($filterArchive && !$isArchived) {
                        continue;
                    }
                    if (!$filterArchive && isset($_GET['archive']) && $_GET['archive'] === '0' && $isArchived) {
                        continue;
                    }

                    if ($filterStatus !== '' && $t['status'] !== $filterStatus) {
                        continue;
                    }
                    if ($filterCategory !== '' && $t['category'] !== $filterCategory) {
                        continue;
                    }
                    if ($filterPriority !== '' && $t['priority'] !== $filterPriority) {
                        continue;
                    }
                    if ($filterExecutor !== '' && (string)$t['executor_id'] !== (string)$filterExecutor) {
                        continue;
                    }

                    if ($filterSearch !== '') {
                        $searchLower = mb_strtolower($filterSearch, 'UTF-8');
                        $haystack = mb_strtolower(
                            ($t['ticket_number'] ?? '') . ' ' .
                            ($t['title'] ?? '') . ' ' .
                            ($t['description'] ?? '') . ' ' .
                            ($t['requester_name'] ?? '') . ' ' .
                            ($t['executor_name'] ?? ''),
                            'UTF-8'
                        );
                        if (mb_strpos($haystack, $searchLower) === false) {
                            continue;
                        }
                    }

                    $filtered[] = $t;
                }

                usort($filtered, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
                AuthHelper::jsonResponse(['success' => true, 'tickets' => $filtered, 'count' => count($filtered)]);
                break;
            }

            if ($method === 'POST') {
                $title = trim((string)($inputData['title'] ?? ''));
                $description = trim((string)($inputData['description'] ?? ''));
                $category = (string)($inputData['category'] ?? 'cat_plumbing');
                $priority = (string)($inputData['priority'] ?? 'medium');
                $customFields = is_array($inputData['custom_fields'] ?? null) ? $inputData['custom_fields'] : [];
                $attachments = is_array($inputData['attachments'] ?? null) ? $inputData['attachments'] : [];

                if ($title === '') {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Заголовок заявки обязателен'], 400);
                    break;
                }

                $settings = json_decode((string)@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
                $slaHours = 24;
                if (!empty($settings['priorities'])) {
                    foreach ($settings['priorities'] as $p) {
                        if ($p['id'] === $priority) {
                            $slaHours = (int)$p['sla_hours'];
                            break;
                        }
                    }
                }

                $createdAt = date('Y-m-d H:i:s');
                $deadline = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

                $allTickets = $storage->getAll('tickets');
                $nextNum = count($allTickets) + 1;
                $ticketNumber = sprintf('REQ-%s-%03d', date('Y'), $nextNum);

                $newTicket = [
                    'ticket_number' => $ticketNumber,
                    'title' => $title,
                    'description' => $description,
                    'category' => $category,
                    'priority' => $priority,
                    'status' => 'new',
                    'requester_id' => $currentUser['id'],
                    'requester_name' => $currentUser['name'],
                    'executor_id' => null,
                    'executor_name' => null,
                    'custom_fields' => $customFields,
                    'attachments' => $attachments,
                    'sla_hours' => $slaHours,
                    'deadline' => $deadline,
                    'completed_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt
                ];

                $created = $storage->insert('tickets', $newTicket);

                AuthHelper::logAction($storage, $currentUser, 'create_ticket', "Создана заявка {$ticketNumber}: {$title}");

                $managers = $storage->findBy('users', ['role' => 'manager']);
                foreach ($managers as $mgr) {
                    AuthHelper::createNotification(
                        $storage,
                        (int)$mgr['id'],
                        'ticket_created',
                        'Новая заявка',
                        "Создана новая заявка {$ticketNumber}: {$title}",
                        (int)$created['id']
                    );
                }

                AuthHelper::jsonResponse(['success' => true, 'ticket' => $created, 'message' => 'Заявка успешно создана'], 201);
                break;
            }
            break;

        case 'tickets/assign':
            $currentUser = AuthHelper::requireAuth($storage, ['admin', 'manager']);
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }

            $ticketId = $inputData['ticket_id'] ?? null;
            $executorId = $inputData['executor_id'] ?? null;

            if (!$ticketId || !$executorId) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'ID заявки и исполнителя обязательны'], 400);
                break;
            }

            $ticket = $storage->findById('tickets', $ticketId);
            if (!$ticket) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Заявка не найдена'], 404);
                break;
            }

            $executor = $storage->findById('users', $executorId);
            if (!$executor || $executor['role'] !== 'executor') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Выбранный пользователь не является исполнителем'], 400);
                break;
            }

            $updates = [
                'executor_id' => $executor['id'],
                'executor_name' => $executor['name'],
                'status' => 'assigned',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = $storage->update('tickets', $ticketId, $updates);

            AuthHelper::logAction($storage, $currentUser, 'assign_ticket', "Заявка {$ticket['ticket_number']} назначена на {$executor['name']}");

            AuthHelper::createNotification(
                $storage,
                (int)$executor['id'],
                'ticket_assigned',
                'Вам назначена заявка',
                "Вам назначена заявка {$ticket['ticket_number']}: {$ticket['title']}",
                (int)$ticket['id']
            );

            AuthHelper::jsonResponse(['success' => true, 'ticket' => $updated, 'message' => 'Исполнитель назначен']);
            break;

        case 'tickets/mass-assign':
            $currentUser = AuthHelper::requireAuth($storage, ['admin', 'manager']);
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }

            $ticketIds = $inputData['ticket_ids'] ?? [];
            $executorId = $inputData['executor_id'] ?? null;

            if (!is_array($ticketIds) || empty($ticketIds) || !$executorId) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Укажите список заявок и исполнителя'], 400);
                break;
            }

            $executor = $storage->findById('users', $executorId);
            if (!$executor || $executor['role'] !== 'executor') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Выбранный пользователь не является исполнителем'], 400);
                break;
            }

            $count = 0;
            foreach ($ticketIds as $tId) {
                $ticket = $storage->findById('tickets', $tId);
                if ($ticket) {
                    $storage->update('tickets', $tId, [
                        'executor_id' => $executor['id'],
                        'executor_name' => $executor['name'],
                        'status' => 'assigned',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $count++;

                    AuthHelper::createNotification(
                        $storage,
                        (int)$executor['id'],
                        'ticket_assigned',
                        'Вам назначена заявка',
                        "Вам назначена заявка {$ticket['ticket_number']}: {$ticket['title']}",
                        (int)$ticket['id']
                    );
                }
            }

            AuthHelper::logAction($storage, $currentUser, 'mass_assign', "Массовое назначение {$count} заявок на {$executor['name']}");

            AuthHelper::jsonResponse(['success' => true, 'assigned_count' => $count, 'message' => "Успешно назначено {$count} заявок"]);
            break;

        case 'tickets/status':
            $currentUser = AuthHelper::requireAuth($storage);
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }

            $ticketId = $inputData['ticket_id'] ?? null;
            $newStatus = $inputData['status'] ?? '';
            $commentMessage = trim((string)($inputData['comment'] ?? ''));

            $validStatuses = ['new', 'assigned', 'in_progress', 'completed', 'rejected'];
            if (!$ticketId || !in_array($newStatus, $validStatuses, true)) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Некорректный ID заявки или статус'], 400);
                break;
            }

            $ticket = $storage->findById('tickets', $ticketId);
            if (!$ticket) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Заявка не найдена'], 404);
                break;
            }

            if ($currentUser['role'] === 'executor') {
                if ((int)$ticket['executor_id'] !== (int)$currentUser['id']) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Вы можете изменять только свои заявки'], 403);
                    break;
                }
                if (!in_array($newStatus, ['in_progress', 'completed'], true)) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Исполнитель может переводить заявку только в «В работе» или «Выполнено»'], 403);
                    break;
                }
            } elseif ($currentUser['role'] === 'requester') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Ответственный сотрудник не может менять статус напрямую'], 403);
                break;
            }

            $updates = [
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($newStatus === 'completed') {
                $updates['completed_at'] = date('Y-m-d H:i:s');
            } else {
                $updates['completed_at'] = null;
            }

            $updated = $storage->update('tickets', $ticketId, $updates);

            if ($commentMessage !== '') {
                $storage->insert('comments', [
                    'ticket_id' => (int)$ticketId,
                    'user_id' => (int)$currentUser['id'],
                    'user_name' => $currentUser['name'],
                    'user_role' => $currentUser['role'],
                    'message' => $commentMessage,
                    'attachments' => is_array($inputData['attachments'] ?? null) ? $inputData['attachments'] : [],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            AuthHelper::logAction($storage, $currentUser, 'status_change', "Статус заявки {$ticket['ticket_number']} изменен на {$newStatus}");

            if (!empty($ticket['requester_id'])) {
                AuthHelper::createNotification(
                    $storage,
                    (int)$ticket['requester_id'],
                    'status_changed',
                    'Статус заявки изменен',
                    "Статус вашей заявки {$ticket['ticket_number']} изменен на «{$newStatus}»",
                    (int)$ticket['id']
                );
            }

            AuthHelper::jsonResponse(['success' => true, 'ticket' => $updated, 'message' => 'Статус успешно обновлен']);
            break;

        case 'tickets/detail':
            $currentUser = AuthHelper::requireAuth($storage);
            $ticketId = $_GET['id'] ?? null;
            if (!$ticketId) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'ID заявки обязателен'], 400);
                break;
            }

            $ticket = $storage->findById('tickets', $ticketId);
            if (!$ticket) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Заявка не найдена'], 404);
                break;
            }

            if ($currentUser['role'] === 'requester' && (int)$ticket['requester_id'] !== (int)$currentUser['id']) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Доступ запрещен'], 403);
                break;
            }
            if ($currentUser['role'] === 'executor' && (int)$ticket['executor_id'] !== (int)$currentUser['id']) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Доступ запрещен'], 403);
                break;
            }

            $comments = $storage->findBy('comments', ['ticket_id' => $ticketId]);
            usort($comments, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

            AuthHelper::jsonResponse([
                'success' => true,
                'ticket' => $ticket,
                'comments' => $comments
            ]);
            break;

        case 'comments':
            $currentUser = AuthHelper::requireAuth($storage);

            if ($method === 'POST') {
                $ticketId = $inputData['ticket_id'] ?? null;
                $message = trim((string)($inputData['message'] ?? ''));
                $attachments = is_array($inputData['attachments'] ?? null) ? $inputData['attachments'] : [];

                if (!$ticketId || ($message === '' && empty($attachments))) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Сообщение или вложение обязательно'], 400);
                    break;
                }

                $ticket = $storage->findById('tickets', $ticketId);
                if (!$ticket) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Заявка не найдена'], 404);
                    break;
                }

                $comment = [
                    'ticket_id' => (int)$ticketId,
                    'user_id' => (int)$currentUser['id'],
                    'user_name' => $currentUser['name'],
                    'user_role' => $currentUser['role'],
                    'message' => $message,
                    'attachments' => $attachments,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $created = $storage->insert('comments', $comment);
                $storage->update('tickets', $ticketId, ['updated_at' => date('Y-m-d H:i:s')]);

                AuthHelper::logAction($storage, $currentUser, 'add_comment', "Добавлен комментарий к заявке {$ticket['ticket_number']}");

                if ($currentUser['role'] === 'executor' && !empty($ticket['requester_id'])) {
                    AuthHelper::createNotification(
                        $storage,
                        (int)$ticket['requester_id'],
                        'comment_added',
                        'Новый комментарий по заявке',
                        "Исполнитель оставил комментарий к заявке {$ticket['ticket_number']}",
                        (int)$ticket['id']
                    );
                } elseif (!empty($ticket['executor_id']) && (int)$currentUser['id'] !== (int)$ticket['executor_id']) {
                    AuthHelper::createNotification(
                        $storage,
                        (int)$ticket['executor_id'],
                        'comment_added',
                        'Новый комментарий по заявке',
                        "Поступил комментарий к заявке {$ticket['ticket_number']}",
                        (int)$ticket['id']
                    );
                }

                AuthHelper::jsonResponse(['success' => true, 'comment' => $created, 'message' => 'Комментарий добавлен'], 201);
                break;
            }
            break;

        case 'fields':
            if ($method === 'GET') {
                $fields = $storage->getAll('fields');
                usort($fields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
                AuthHelper::jsonResponse(['success' => true, 'fields' => $fields]);
                break;
            }

            if ($method === 'POST') {
                $currentUser = AuthHelper::requireAuth($storage, ['admin']);
                $fieldsList = $inputData['fields'] ?? null;

                if (!is_array($fieldsList)) {
                    $fieldId = $inputData['id'] ?? ('field_' . uniqid());
                    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($inputData['name'] ?? 'custom_' . time()));
                    $label = trim((string)($inputData['label'] ?? 'Поле'));
                    $type = (string)($inputData['type'] ?? 'text');
                    $required = !empty($inputData['required']);
                    $placeholder = (string)($inputData['placeholder'] ?? '');
                    $options = is_array($inputData['options'] ?? null) ? $inputData['options'] : [];
                    $order = (int)($inputData['order'] ?? 99);
                    $active = isset($inputData['active']) ? (bool)$inputData['active'] : true;

                    $fieldData = [
                        'id' => $fieldId,
                        'name' => $name,
                        'label' => $label,
                        'type' => $type,
                        'required' => $required,
                        'placeholder' => $placeholder,
                        'options' => $options,
                        'order' => $order,
                        'active' => $active
                    ];

                    $existing = $storage->findById('fields', $fieldId);
                    if ($existing) {
                        $saved = $storage->update('fields', $fieldId, $fieldData);
                    } else {
                        $saved = $storage->insert('fields', $fieldData);
                    }

                    AuthHelper::logAction($storage, $currentUser, 'save_field', "Сохранено поле {$label} ({$name})");
                    AuthHelper::jsonResponse(['success' => true, 'field' => $saved]);
                    break;
                }

                foreach ($fieldsList as $idx => $f) {
                    $f['order'] = $idx + 1;
                    $existing = $storage->findById('fields', $f['id']);
                    if ($existing) {
                        $storage->update('fields', $f['id'], $f);
                    } else {
                        $storage->insert('fields', $f);
                    }
                }

                AuthHelper::logAction($storage, $currentUser, 'reorder_fields', 'Обновлена конфигурация конструктора форм');
                AuthHelper::jsonResponse(['success' => true, 'fields' => $storage->getAll('fields'), 'message' => 'Поля успешно сохранены']);
                break;
            }

            if ($method === 'DELETE') {
                $currentUser = AuthHelper::requireAuth($storage, ['admin']);
                $fieldId = $_GET['id'] ?? $inputData['id'] ?? null;
                if (!$fieldId) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'ID поля обязателен'], 400);
                    break;
                }

                $storage->delete('fields', $fieldId);
                AuthHelper::logAction($storage, $currentUser, 'delete_field', "Удалено поле {$fieldId}");
                AuthHelper::jsonResponse(['success' => true, 'message' => 'Поле удалено']);
                break;
            }
            break;

        case 'users':
            $currentUser = AuthHelper::requireAuth($storage);

            if ($method === 'GET') {
                $roleFilter = $_GET['role'] ?? null;
                $allUsers = $storage->getAll('users');
                $result = [];
                foreach ($allUsers as $u) {
                    if ($roleFilter && $u['role'] !== $roleFilter) {
                        continue;
                    }
                    unset($u['password']);
                    $result[] = $u;
                }
                AuthHelper::jsonResponse(['success' => true, 'users' => $result]);
                break;
            }

            if ($method === 'POST') {
                $currentUser = AuthHelper::requireAuth($storage, ['admin']);
                $userId = $inputData['id'] ?? null;
                $username = trim((string)($inputData['username'] ?? ''));
                $name = trim((string)($inputData['name'] ?? ''));
                $email = trim((string)($inputData['email'] ?? ''));
                $role = (string)($inputData['role'] ?? 'requester');
                $department = trim((string)($inputData['department'] ?? ''));
                $phone = trim((string)($inputData['phone'] ?? ''));
                $status = (string)($inputData['status'] ?? 'active');

                if ($userId) {
                    $updates = [
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                        'department' => $department,
                        'phone' => $phone,
                        'status' => $status
                    ];
                    if (!empty($inputData['password'])) {
                        $updates['password'] = password_hash((string)$inputData['password'], PASSWORD_BCRYPT);
                    }
                    $updated = $storage->update('users', $userId, $updates);
                    unset($updated['password']);
                    AuthHelper::logAction($storage, $currentUser, 'update_user', "Обновлен пользователь {$updated['username']}");
                    AuthHelper::jsonResponse(['success' => true, 'user' => $updated, 'message' => 'Пользователь обновлен']);
                    break;
                }

                if ($username === '' || empty($inputData['password'])) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Логин и пароль обязательны'], 400);
                    break;
                }

                $existing = $storage->findOneBy('users', ['username' => $username]);
                if ($existing) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Пользователь с таким логином уже существует'], 400);
                    break;
                }

                $newUser = [
                    'username' => $username,
                    'password' => password_hash((string)$inputData['password'], PASSWORD_BCRYPT),
                    'name' => $name ?: $username,
                    'email' => $email ?: ($username . '@crm-service.local'),
                    'phone' => $phone,
                    'role' => $role,
                    'status' => $status,
                    'department' => $department,
                    'avatar' => '',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $created = $storage->insert('users', $newUser);
                unset($created['password']);

                AuthHelper::logAction($storage, $currentUser, 'create_user', "Создан новый пользователь {$username} (роль: {$role})");
                AuthHelper::jsonResponse(['success' => true, 'user' => $created, 'message' => 'Пользователь успешно создан'], 201);
                break;
            }

            if ($method === 'DELETE') {
                $currentUser = AuthHelper::requireAuth($storage, ['admin']);
                $userId = $_GET['id'] ?? $inputData['id'] ?? null;
                if (!$userId) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'ID пользователя обязателен'], 400);
                    break;
                }
                if ((int)$userId === (int)$currentUser['id']) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Нельзя удалить собственный аккаунт'], 400);
                    break;
                }

                $storage->delete('users', $userId);
                AuthHelper::logAction($storage, $currentUser, 'delete_user', "Удален пользователь ID {$userId}");
                AuthHelper::jsonResponse(['success' => true, 'message' => 'Пользователь удален']);
                break;
            }
            break;

        case 'settings':
            $settingsFile = __DIR__ . '/../data/settings.json';
            $settings = json_decode((string)@file_get_contents($settingsFile), true) ?: [];

            if ($method === 'GET') {
                AuthHelper::jsonResponse(['success' => true, 'settings' => $settings]);
                break;
            }

            if ($method === 'POST') {
                $currentUser = AuthHelper::requireAuth($storage, ['admin']);

                if (isset($inputData['system'])) {
                    $settings['system'] = array_merge($settings['system'] ?? [], $inputData['system']);
                }
                if (isset($inputData['categories'])) {
                    $settings['categories'] = $inputData['categories'];
                }
                if (isset($inputData['priorities'])) {
                    $settings['priorities'] = $inputData['priorities'];
                }
                if (isset($inputData['notifications'])) {
                    $settings['notifications'] = array_merge($settings['notifications'] ?? [], $inputData['notifications']);
                }
                if (isset($inputData['mysql_config'])) {
                    $settings['mysql_config'] = array_merge($settings['mysql_config'] ?? [], $inputData['mysql_config']);
                }

                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                AuthHelper::logAction($storage, $currentUser, 'update_settings', 'Обновлены системные настройки');

                AuthHelper::jsonResponse(['success' => true, 'settings' => $settings, 'message' => 'Настройки успешно сохранены']);
                break;
            }
            break;

        case 'settings/storage-switch':
            $currentUser = AuthHelper::requireAuth($storage, ['admin']);
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }

            $targetMode = (string)($inputData['mode'] ?? 'json');
            if (!in_array($targetMode, ['json', 'mysql'], true)) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Недопустимый режим хранения'], 400);
                break;
            }

            $settingsFile = __DIR__ . '/../data/settings.json';
            $settings = json_decode((string)@file_get_contents($settingsFile), true) ?: [];

            if ($targetMode === 'mysql') {
                $mysqlConfig = $inputData['mysql_config'] ?? ($settings['mysql_config'] ?? []);
                try {
                    require_once __DIR__ . '/drivers/MysqlDriver.php';
                    $mysqlDriver = new MysqlDriver($mysqlConfig);
                    $migrationStats = [];

                    if (!empty($inputData['migrate_data'])) {
                        $migrationStats = $mysqlDriver->migrateFromJson(__DIR__ . '/../data');
                    }

                    $settings['system']['storage_mode'] = 'mysql';
                    if (!empty($inputData['mysql_config'])) {
                        $settings['mysql_config'] = $mysqlConfig;
                    }
                    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    StorageFactory::reset();

                    AuthHelper::logAction($storage, $currentUser, 'switch_storage_mysql', 'Переключение на MySQL');
                    AuthHelper::jsonResponse([
                        'success' => true,
                        'mode' => 'mysql',
                        'migration' => $migrationStats,
                        'message' => 'Успешно подключен MySQL, схемы созданы' . (!empty($migrationStats) ? ' и данные перенесены.' : '.')
                    ]);
                    break;
                } catch (Throwable $e) {
                    AuthHelper::jsonResponse(['success' => false, 'error' => 'Ошибка подключения к MySQL: ' . $e->getMessage()], 500);
                    break;
                }
            } else {
                $settings['system']['storage_mode'] = 'json';
                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                StorageFactory::reset();

                AuthHelper::logAction($storage, $currentUser, 'switch_storage_json', 'Переключение на JSON хранилище');
                AuthHelper::jsonResponse(['success' => true, 'mode' => 'json', 'message' => 'Включен режим файлового JSON хранилища']);
                break;
            }
            break;

        case 'analytics':
            $currentUser = AuthHelper::requireAuth($storage);
            $tickets = $storage->getAll('tickets');
            $users = $storage->getAll('users');

            $byStatus = [
                'new' => 0,
                'assigned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'rejected' => 0
            ];

            $byPriority = [
                'low' => 0,
                'medium' => 0,
                'high' => 0,
                'critical' => 0
            ];

            $byCategory = [];
            $slaBreachedCount = 0;
            $completedWithinSla = 0;
            $totalCompleted = 0;
            $completionTimes = [];

            $employeeWorkload = [];
            foreach ($users as $u) {
                if ($u['role'] === 'executor') {
                    $employeeWorkload[$u['id']] = [
                        'id' => $u['id'],
                        'name' => $u['name'],
                        'department' => $u['department'] ?? '',
                        'active_tickets' => 0,
                        'completed_tickets' => 0
                    ];
                }
            }

            $now = time();

            foreach ($tickets as $t) {
                $status = $t['status'] ?? 'new';
                if (isset($byStatus[$status])) {
                    $byStatus[$status]++;
                }

                $prio = $t['priority'] ?? 'medium';
                if (isset($byPriority[$prio])) {
                    $byPriority[$prio]++;
                }

                $cat = $t['category'] ?? 'other';
                $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;

                $deadlineTime = !empty($t['deadline']) ? strtotime($t['deadline']) : 0;
                $createdTime = !empty($t['created_at']) ? strtotime($t['created_at']) : 0;

                if ($status === 'completed' && !empty($t['completed_at'])) {
                    $totalCompleted++;
                    $compTime = strtotime($t['completed_at']);
                    if ($createdTime > 0 && $compTime >= $createdTime) {
                        $completionTimes[] = ($compTime - $createdTime) / 3600.0;
                    }

                    if ($deadlineTime > 0) {
                        if ($compTime <= $deadlineTime) {
                            $completedWithinSla++;
                        } else {
                            $slaBreachedCount++;
                        }
                    }
                } else {
                    if ($deadlineTime > 0 && $now > $deadlineTime && !in_array($status, ['completed', 'rejected'], true)) {
                        $slaBreachedCount++;
                    }
                }

                $execId = $t['executor_id'] ?? null;
                if ($execId && isset($employeeWorkload[$execId])) {
                    if (in_array($status, ['completed', 'rejected'], true)) {
                        $employeeWorkload[$execId]['completed_tickets']++;
                    } else {
                        $employeeWorkload[$execId]['active_tickets']++;
                    }
                }
            }

            $avgCompletionHours = !empty($completionTimes) ? round(array_sum($completionTimes) / count($completionTimes), 1) : 0;
            $slaSuccessRate = $totalCompleted > 0 ? round(($completedWithinSla / $totalCompleted) * 100, 1) : 100;

            AuthHelper::jsonResponse([
                'success' => true,
                'total_tickets' => count($tickets),
                'by_status' => $byStatus,
                'by_priority' => $byPriority,
                'by_category' => $byCategory,
                'avg_completion_hours' => $avgCompletionHours,
                'sla_breached_count' => $slaBreachedCount,
                'sla_success_rate' => $slaSuccessRate,
                'employee_workload' => array_values($employeeWorkload)
            ]);
            break;

        case 'notifications':
            $currentUser = AuthHelper::requireAuth($storage);

            if ($method === 'GET') {
                $notes = $storage->findBy('notifications', ['user_id' => $currentUser['id']]);
                usort($notes, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
                AuthHelper::jsonResponse(['success' => true, 'notifications' => $notes]);
                break;
            }

            if ($method === 'POST') {
                $noteId = $inputData['id'] ?? null;
                if ($noteId) {
                    $storage->update('notifications', $noteId, ['is_read' => true]);
                } else {
                    $allNotes = $storage->findBy('notifications', ['user_id' => $currentUser['id']]);
                    foreach ($allNotes as $n) {
                        $storage->update('notifications', $n['id'], ['is_read' => true]);
                    }
                }
                AuthHelper::jsonResponse(['success' => true, 'message' => 'Уведомления прочитаны']);
                break;
            }
            break;

        case 'upload':
            $currentUser = AuthHelper::requireAuth($storage);
            if ($method !== 'POST') {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                break;
            }

            if (empty($_FILES['file'])) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Файл не передан'], 400);
                break;
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Ошибка при загрузке файла: ' . $file['error']], 400);
                break;
            }

            if ($file['size'] > 15 * 1024 * 1024) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Размер файла превышает 15 МБ'], 400);
                break;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
            if (!in_array($ext, $allowedExtensions, true)) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Недопустимый тип файла. Разрешены фото и документы.'], 400);
                break;
            }

            $safeFileName = uniqid('att_', true) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . '/' . $safeFileName;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                AuthHelper::jsonResponse(['success' => false, 'error' => 'Не удалось сохранить файл на сервере'], 500);
                break;
            }

            $fileUrl = 'uploads/' . $safeFileName;
            AuthHelper::jsonResponse([
                'success' => true,
                'file' => [
                    'name' => htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'),
                    'url' => $fileUrl,
                    'size' => $file['size'],
                    'type' => $ext
                ]
            ]);
            break;

        case 'export':
            $currentUser = AuthHelper::requireAuth($storage, ['admin', 'manager']);
            $format = $_GET['format'] ?? 'excel';

            $tickets = $storage->getAll('tickets');
            usort($tickets, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

            if ($format === 'excel' || $format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="crm_tickets_' . date('Y-m-d') . '.csv"');
                echo "\xEF\xBB\xBF";

                $output = fopen('php://output', 'w');
                fputcsv($output, [
                    'Номер', 'Заголовок', 'Категория', 'Приоритет', 'Статус',
                    'Заявитель', 'Исполнитель', 'Дедлайн SLA', 'Создана', 'Завершена'
                ], ';');

                foreach ($tickets as $t) {
                    fputcsv($output, [
                        $t['ticket_number'] ?? '',
                        $t['title'] ?? '',
                        $t['category'] ?? '',
                        $t['priority'] ?? '',
                        $t['status'] ?? '',
                        $t['requester_name'] ?? '',
                        $t['executor_name'] ?? 'Не назначен',
                        $t['deadline'] ?? '',
                        $t['created_at'] ?? '',
                        $t['completed_at'] ?? ''
                    ], ';');
                }
                fclose($output);
                exit;
            }

            AuthHelper::jsonResponse(['success' => true, 'tickets' => $tickets]);
            break;

        case 'backup':
            $currentUser = AuthHelper::requireAuth($storage, ['admin']);
            if ($method === 'GET') {
                $backupData = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0.0',
                    'users' => $storage->getAll('users'),
                    'tickets' => $storage->getAll('tickets'),
                    'comments' => $storage->getAll('comments'),
                    'fields' => $storage->getAll('fields'),
                    'settings' => json_decode((string)@file_get_contents(__DIR__ . '/../data/settings.json'), true),
                    'notifications' => $storage->getAll('notifications'),
                    'logs' => $storage->getAll('logs')
                ];

                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="crm_backup_' . date('Y-m-d_His') . '.json"');
                echo json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            break;

        default:
            AuthHelper::jsonResponse(['success' => false, 'error' => "Endpoint not found: {$endpoint}"], 404);
            break;
    }
} catch (Throwable $e) {
    error_log("API Error [{$endpoint}]: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    AuthHelper::jsonResponse([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], 500);
}
