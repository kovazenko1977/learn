<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

Auth::seedDefaultUsers();

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse URL path
$parsedUrl = parse_url($uri, PHP_URL_PATH);
$path = preg_replace('#^/api/#', '', $parsedUrl);
$path = trim($path, '/');
$segments = explode('/', $path);

$action = $segments[0] ?? '';
$subAction = $segments[1] ?? null;
$subSubAction = $segments[2] ?? null;

function sendJson($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function logAuditAction(string $action, string $details = ''): void {
    $currentUser = Auth::getCurrentUser();
    if ($currentUser) {
        $storage = Storage::getInstance();
        $storage->save('audit_logs', [
            'id' => uniqid('log_', true),
            'user_id' => $currentUser['id'],
            'user_name' => $currentUser['full_name'],
            'action' => $action,
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

$storage = Storage::getInstance();

// 1. AUTH ROUTES
if ($action === 'auth') {
    if ($subAction === 'login' && $method === 'POST') {
        $input = getJsonInput();
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if (!$username || !$password) {
            sendJson(['error' => 'Укажите имя пользователя и пароль'], 400);
        }

        $user = Auth::authenticate($username, $password);
        if ($user) {
            logAuditAction('USER_LOGIN', "Вход пользователя: $username");
            sendJson(['success' => true, 'user' => $user]);
        } else {
            sendJson(['error' => 'Неверное имя пользователя или пароль'], 401);
        }
    }

    if ($subAction === 'forgot-password' && $method === 'POST') {
        $input = getJsonInput();
        $identifier = trim($input['identifier'] ?? '');
        if (!$identifier) {
            sendJson(['error' => 'Укажите имя пользователя или Email'], 400);
        }

        $resetToken = Auth::generateResetToken($identifier);
        if ($resetToken) {
            sendJson(['success' => true, 'message' => 'Инструкция и токен восстановления сгенерированы', 'reset_token' => $resetToken]);
        } else {
            sendJson(['error' => 'Пользователь с такими данными не найден'], 404);
        }
    }

    if ($subAction === 'reset-password' && $method === 'POST') {
        $input = getJsonInput();
        $resetToken = trim($input['reset_token'] ?? '');
        $newPassword = trim($input['new_password'] ?? '');

        if (!$resetToken || !$newPassword) {
            sendJson(['error' => 'Необходим токен и новый пароль'], 400);
        }

        if (Auth::resetPasswordWithToken($resetToken, $newPassword)) {
            sendJson(['success' => true, 'message' => 'Пароль успешно изменён']);
        } else {
            sendJson(['error' => 'Недействительный токен сброса пароля'], 400);
        }
    }

    sendJson(['error' => 'Действие не найдено'], 404);
}

// Public Settings GET fallback or allow if user authenticated
if ($action === 'settings' && $method === 'GET') {
    $settings = $storage->getSettings();
    $currentUser = Auth::getCurrentUser();
    if (!$currentUser || $currentUser['role'] !== Auth::ROLE_ADMIN) {
        unset($settings['mysql_pass']);
    }
    sendJson(['success' => true, 'settings' => $settings]);
}

// Check Authentication for rest of API
$currentUser = Auth::getCurrentUser();
if (!$currentUser) {
    sendJson(['error' => 'Необходима авторизация'], 401);
}

// 2. PROFILE ROUTES
if ($action === 'profile') {
    if ($method === 'GET') {
        sendJson(['success' => true, 'user' => $currentUser]);
    }

    if ($method === 'PUT') {
        $input = getJsonInput();
        $updateData = [];

        if (!empty($input['full_name'])) $updateData['full_name'] = trim($input['full_name']);
        if (isset($input['email'])) $updateData['email'] = trim($input['email']);
        if (!empty($input['department'])) $updateData['department'] = trim($input['department']);

        if (!empty($input['new_password'])) {
            if (!empty($input['current_password'])) {
                $dbUser = $storage->getById('users', $currentUser['id']);
                if (!password_verify($input['current_password'], $dbUser['password_hash'])) {
                    sendJson(['error' => 'Текущий пароль указан неверно'], 400);
                }
            }
            $updateData['password_hash'] = password_hash(trim($input['new_password']), PASSWORD_BCRYPT);
        }

        if (!empty($updateData)) {
            $updateData['id'] = $currentUser['id'];
            $storage->save('users', $updateData);
            logAuditAction('PROFILE_UPDATE', 'Обновление личного профиля');
        }

        $updatedUser = $storage->getById('users', $currentUser['id']);
        unset($updatedUser['password_hash']);
        sendJson(['success' => true, 'user' => $updatedUser]);
    }
}

// 3. USERS MANAGEMENT ROUTES
if ($action === 'users') {
    if ($method === 'GET') {
        $users = $storage->getAll('users');
        $cleanUsers = array_map(function($u) {
            unset($u['password_hash'], $u['reset_token']);
            return $u;
        }, $users);
        sendJson(['success' => true, 'users' => $cleanUsers]);
    }

    if ($currentUser['role'] !== Auth::ROLE_ADMIN) {
        sendJson(['error' => 'Недостаточно прав администратора'], 403);
    }

    if ($method === 'POST') {
        $input = getJsonInput();
        if (empty($input['username']) || empty($input['password']) || empty($input['full_name']) || empty($input['role'])) {
            sendJson(['error' => 'Заполните все обязательные поля'], 400);
        }

        $existing = $storage->getAll('users');
        foreach ($existing as $u) {
            if ($u['username'] === trim($input['username'])) {
                sendJson(['error' => 'Пользователь с таким логином уже существует'], 400);
            }
        }

        $newUser = [
            'id' => uniqid('user_', true),
            'username' => trim($input['username']),
            'password_hash' => password_hash(trim($input['password']), PASSWORD_BCRYPT),
            'full_name' => trim($input['full_name']),
            'email' => trim($input['email'] ?? ''),
            'role' => trim($input['role']),
            'department' => trim($input['department'] ?? ''),
            'status' => $input['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $storage->save('users', $newUser);
        logAuditAction('USER_CREATE', "Создан пользователь: {$newUser['username']}");
        unset($newUser['password_hash']);
        sendJson(['success' => true, 'user' => $newUser]);
    }

    if ($method === 'PUT' && $subAction) {
        $input = getJsonInput();
        $targetUser = $storage->getById('users', $subAction);
        if (!$targetUser) {
            sendJson(['error' => 'Пользователь не найден'], 404);
        }

        if (!empty($input['full_name'])) $targetUser['full_name'] = trim($input['full_name']);
        if (isset($input['email'])) $targetUser['email'] = trim($input['email']);
        if (isset($input['department'])) $targetUser['department'] = trim($input['department']);
        if (!empty($input['role'])) $targetUser['role'] = trim($input['role']);
        if (!empty($input['status'])) $targetUser['status'] = trim($input['status']);

        if (!empty($input['password'])) {
            $targetUser['password_hash'] = password_hash(trim($input['password']), PASSWORD_BCRYPT);
        }

        $storage->save('users', $targetUser);
        logAuditAction('USER_UPDATE', "Обновлен пользователь ID: $subAction");
        unset($targetUser['password_hash']);
        sendJson(['success' => true, 'user' => $targetUser]);
    }

    if ($method === 'DELETE' && $subAction) {
        if ($subAction === $currentUser['id']) {
            sendJson(['error' => 'Нельзя удалить собственного пользователя'], 400);
        }
        $storage->delete('users', $subAction);
        logAuditAction('USER_DELETE', "Удален пользователь ID: $subAction");
        sendJson(['success' => true, 'message' => 'Пользователь удален']);
    }
}

// 4. SETTINGS UPDATE ROUTE
if ($action === 'settings' && $method === 'PUT') {
    if ($currentUser['role'] !== Auth::ROLE_ADMIN) {
        sendJson(['error' => 'Доступ разрешен только администраторам'], 403);
    }

    $input = getJsonInput();
    $currentSettings = $storage->getSettings();
    $oldMode = $currentSettings['storage_mode'] ?? 'json';

    $storage->saveSettings($input);
    $newSettings = $storage->getSettings();
    $newMode = $newSettings['storage_mode'] ?? 'json';

    if ($oldMode !== $newMode && $newMode === 'mysql') {
        $migrated = $storage->migrateJsonToMySQL();
        if ($migrated) {
            logAuditAction('SETTINGS_UPDATE', 'Переключен режим хранения на MySQL и выполнена миграция данных');
        } else {
            sendJson(['error' => 'Ошибка подключения или миграции MySQL'], 500);
        }
    } else {
        logAuditAction('SETTINGS_UPDATE', 'Обновлены настройки системы');
    }

    unset($newSettings['mysql_pass']);
    sendJson(['success' => true, 'settings' => $newSettings]);
}

// 5. UPLOADS ROUTE
if ($action === 'uploads' && $method === 'POST') {
    if (empty($_FILES['file'])) {
        sendJson(['error' => 'Файл не загружен'], 400);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendJson(['error' => 'Ошибка загрузки файла'], 400);
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        sendJson(['error' => 'Формат файла не поддерживается'], 400);
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid('file_', true) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $fileUrl = 'uploads/' . $fileName;
        sendJson([
            'success' => true,
            'url' => $fileUrl,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ]);
    } else {
        sendJson(['error' => 'Не удалось сохранить файл'], 500);
    }
}

// 6. FORM BUILDER ROUTES
if ($action === 'form-fields') {
    if ($method === 'GET') {
        $fields = $storage->getAll('form_fields');
        usort($fields, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        sendJson(['success' => true, 'fields' => $fields]);
    }

    if ($currentUser['role'] !== Auth::ROLE_ADMIN) {
        sendJson(['error' => 'Доступ разрешен только администраторам'], 403);
    }

    if ($method === 'POST') {
        $input = getJsonInput();
        if (empty($input['label']) || empty($input['type'])) {
            sendJson(['error' => 'Укажите название и тип поля'], 400);
        }

        $field = [
            'id' => uniqid('field_', true),
            'label' => trim($input['label']),
            'type' => trim($input['type']),
            'required' => !empty($input['required']),
            'options' => is_array($input['options'] ?? null) ? $input['options'] : [],
            'sort_order' => (int)($input['sort_order'] ?? 0)
        ];

        $storage->save('form_fields', $field);
        logAuditAction('FORM_FIELD_CREATE', "Добавлено настраиваемое поле: {$field['label']}");
        sendJson(['success' => true, 'field' => $field]);
    }

    if ($method === 'PUT' && $subAction === 'reorder') {
        $input = getJsonInput();
        $orders = $input['orders'] ?? []; // array of {id, sort_order}
        foreach ($orders as $item) {
            if (!empty($item['id'])) {
                $f = $storage->getById('form_fields', $item['id']);
                if ($f) {
                    $f['sort_order'] = (int)($item['sort_order'] ?? 0);
                    $storage->save('form_fields', $f);
                }
            }
        }
        sendJson(['success' => true, 'message' => 'Порядок полей обновлен']);
    }

    if ($method === 'PUT' && $subAction) {
        $input = getJsonInput();
        $field = $storage->getById('form_fields', $subAction);
        if (!$field) {
            sendJson(['error' => 'Поле не найдено'], 404);
        }

        if (isset($input['label'])) $field['label'] = trim($input['label']);
        if (isset($input['type'])) $field['type'] = trim($input['type']);
        if (isset($input['required'])) $field['required'] = !empty($input['required']);
        if (isset($input['options']) && is_array($input['options'])) $field['options'] = $input['options'];
        if (isset($input['sort_order'])) $field['sort_order'] = (int)$input['sort_order'];

        $storage->save('form_fields', $field);
        logAuditAction('FORM_FIELD_UPDATE', "Обновлено поле ID: $subAction");
        sendJson(['success' => true, 'field' => $field]);
    }

    if ($method === 'DELETE' && $subAction) {
        $storage->delete('form_fields', $subAction);
        logAuditAction('FORM_FIELD_DELETE', "Удалено поле ID: $subAction");
        sendJson(['success' => true, 'message' => 'Поле удалено']);
    }
}

// Helper: Calculate Due Date from Priority & SLA Settings
function calculateDueDate(string $priority, array $settings): string {
    $hours = 48; // default
    $p = mb_strtolower($priority);
    if ($p === 'критический' || $p === 'urgent') {
        $hours = $settings['sla_urgent'] ?? 8;
    } else if ($p === 'высокий' || $p === 'high') {
        $hours = $settings['sla_high'] ?? 24;
    } else if ($p === 'средний' || $p === 'medium') {
        $hours = $settings['sla_medium'] ?? 48;
    } else if ($p === 'низкий' || $p === 'low') {
        $hours = $settings['sla_low'] ?? 72;
    }
    return date('Y-m-d H:i:s', strtotime("+$hours hours"));
}

// 7. REQUESTS / TICKETS ROUTES
if ($action === 'requests') {
    $allRequests = $storage->getAll('requests');

    // GET Requests (with RBAC filtering, search, status, category, priority filters)
    if ($method === 'GET' && !$subAction) {
        $role = $currentUser['role'];
        $filtered = [];

        foreach ($allRequests as $req) {
            // Role level filtering
            if ($role === Auth::ROLE_RESPONSIBLE && $req['author_id'] !== $currentUser['id']) {
                continue;
            }
            if ($role === Auth::ROLE_EXECUTOR && ($req['executor_id'] ?? '') !== $currentUser['id']) {
                continue;
            }
            if ($role === Auth::ROLE_HEAD && !empty($req['department']) && $req['department'] !== $currentUser['department']) {
                // Head views requests for their department or unassigned department
            }

            // Search query filter
            if (!empty($_GET['search'])) {
                $q = mb_strtolower($_GET['search']);
                $title = mb_strtolower($req['title'] ?? '');
                $desc = mb_strtolower($req['description'] ?? '');
                if (strpos($title, $q) === false && strpos($desc, $q) === false && strpos($req['id'], $q) === false) {
                    continue;
                }
            }

            // Status filter
            if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
                if ($req['status'] !== $_GET['status']) {
                    continue;
                }
            }

            // Category filter
            if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
                if ($req['category'] !== $_GET['category']) {
                    continue;
                }
            }

            // Priority filter
            if (!empty($_GET['priority']) && $_GET['priority'] !== 'all') {
                if ($req['priority'] !== $_GET['priority']) {
                    continue;
                }
            }

            $filtered[] = $req;
        }

        // Sort latest first
        usort($filtered, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        sendJson(['success' => true, 'requests' => array_values($filtered)]);
    }

    // GET Single Request Detail
    if ($method === 'GET' && $subAction && $subAction !== 'batch-assign') {
        $req = $storage->getById('requests', $subAction);
        if (!$req) {
            sendJson(['error' => 'Заявка не найдена'], 404);
        }

        // Fetch comments
        $allComments = $storage->getAll('comments');
        $reqComments = array_values(array_filter($allComments, fn($c) => $c['request_id'] === $subAction));
        usort($reqComments, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

        $req['comments'] = $reqComments;
        sendJson(['success' => true, 'request' => $req]);
    }

    // POST Create Request
    if ($method === 'POST' && !$subAction) {
        $input = getJsonInput();
        if (empty($input['title']) || empty($input['category']) || empty($input['priority'])) {
            sendJson(['error' => 'Заполните заголовок, категорию и приоритет'], 400);
        }

        $settings = $storage->getSettings();
        $reqId = uniqid('req_', true);
        $dueDate = calculateDueDate($input['priority'], $settings);

        $newReq = [
            'id' => $reqId,
            'title' => trim($input['title']),
            'description' => trim($input['description'] ?? ''),
            'category' => trim($input['category']),
            'priority' => trim($input['priority']),
            'status' => 'new',
            'author_id' => $currentUser['id'],
            'executor_id' => !empty($input['executor_id']) ? $input['executor_id'] : null,
            'department' => $currentUser['department'] ?? '',
            'custom_fields' => is_array($input['custom_fields'] ?? null) ? $input['custom_fields'] : [],
            'attachments' => is_array($input['attachments'] ?? null) ? $input['attachments'] : [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'due_date' => $dueDate,
            'completed_at' => null
        ];

        if (!empty($newReq['executor_id'])) {
            $newReq['status'] = 'assigned';
        }

        $storage->save('requests', $newReq);
        logAuditAction('REQUEST_CREATE', "Создана заявка ID: $reqId ({$newReq['title']})");
        sendJson(['success' => true, 'request' => $newReq]);
    }

    // POST Bulk Mass Assign
    if ($method === 'POST' && $subAction === 'batch-assign') {
        if (!in_array($currentUser['role'], [Auth::ROLE_ADMIN, Auth::ROLE_HEAD])) {
            sendJson(['error' => 'Только начальник или администратор может назначать исполнителей'], 403);
        }

        $input = getJsonInput();
        $requestIds = $input['request_ids'] ?? [];
        $executorId = $input['executor_id'] ?? null;

        if (empty($requestIds) || !$executorId) {
            sendJson(['error' => 'Укажите список заявок и исполнителя'], 400);
        }

        $updatedCount = 0;
        foreach ($requestIds as $rid) {
            $r = $storage->getById('requests', $rid);
            if ($r) {
                $r['executor_id'] = $executorId;
                if ($r['status'] === 'new') {
                    $r['status'] = 'assigned';
                }
                $r['updated_at'] = date('Y-m-d H:i:s');
                $storage->save('requests', $r);
                $updatedCount++;
            }
        }

        logAuditAction('REQUEST_BATCH_ASSIGN', "Массовое назначение $updatedCount заявок на исполнителя ID: $executorId");
        sendJson(['success' => true, 'message' => "Назначено заявок: $updatedCount"]);
    }

    // PUT Update Request Status / Executor / Fields
    if ($method === 'PUT' && $subAction) {
        $req = $storage->getById('requests', $subAction);
        if (!$req) {
            sendJson(['error' => 'Заявка не найдена'], 404);
        }

        $input = getJsonInput();

        if (isset($input['status'])) {
            $allowedStatuses = ['new', 'assigned', 'in_progress', 'completed', 'rejected'];
            if (!in_array($input['status'], $allowedStatuses)) {
                sendJson(['error' => 'Недопустимый статус заявки'], 400);
            }

            // Role constraints check
            if ($currentUser['role'] === Auth::ROLE_EXECUTOR && $req['executor_id'] !== $currentUser['id']) {
                sendJson(['error' => 'Исполнитель может изменять только свои заявки'], 403);
            }

            $req['status'] = $input['status'];
            if ($input['status'] === 'completed') {
                $req['completed_at'] = date('Y-m-d H:i:s');
            }
        }

        if (isset($input['executor_id'])) {
            if (!in_array($currentUser['role'], [Auth::ROLE_ADMIN, Auth::ROLE_HEAD])) {
                sendJson(['error' => 'Назначать исполнителя могут только начальник и администратор'], 403);
            }
            $req['executor_id'] = $input['executor_id'];
            if ($req['status'] === 'new' && !empty($input['executor_id'])) {
                $req['status'] = 'assigned';
            }
        }

        if (isset($input['title'])) $req['title'] = trim($input['title']);
        if (isset($input['description'])) $req['description'] = trim($input['description']);
        if (isset($input['category'])) $req['category'] = trim($input['category']);
        if (isset($input['priority'])) $req['priority'] = trim($input['priority']);
        if (isset($input['custom_fields'])) $req['custom_fields'] = $input['custom_fields'];
        if (isset($input['attachments'])) $req['attachments'] = $input['attachments'];

        $req['updated_at'] = date('Y-m-d H:i:s');
        $storage->save('requests', $req);

        logAuditAction('REQUEST_UPDATE', "Обновлена заявка ID: $subAction (Статус: {$req['status']})");
        sendJson(['success' => true, 'request' => $req]);
    }

    // POST Add Comment
    if ($method === 'POST' && $subAction && $subSubAction === 'comments') {
        $req = $storage->getById('requests', $subAction);
        if (!$req) {
            sendJson(['error' => 'Заявка не найдена'], 404);
        }

        $input = getJsonInput();
        $content = trim($input['content'] ?? '');
        $attachments = $input['attachments'] ?? [];

        if (!$content && empty($attachments)) {
            sendJson(['error' => 'Введите текст комментария или прикрепите файл'], 400);
        }

        $comment = [
            'id' => uniqid('cmt_', true),
            'request_id' => $subAction,
            'author_id' => $currentUser['id'],
            'author_name' => $currentUser['full_name'],
            'content' => $content,
            'attachments' => is_array($attachments) ? $attachments : [],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $storage->save('comments', $comment);
        $req['updated_at'] = date('Y-m-d H:i:s');
        $storage->save('requests', $req);

        logAuditAction('COMMENT_ADD', "Добавлен комментарий к заявке ID: $subAction");
        sendJson(['success' => true, 'comment' => $comment]);
    }

    // DELETE Request (Admin only)
    if ($method === 'DELETE' && $subAction) {
        if ($currentUser['role'] !== Auth::ROLE_ADMIN) {
            sendJson(['error' => 'Удаление заявок разрешено только администраторам'], 403);
        }
        $storage->delete('requests', $subAction);
        logAuditAction('REQUEST_DELETE', "Удалена заявка ID: $subAction");
        sendJson(['success' => true, 'message' => 'Заявка удалена']);
    }
}

// 8. ANALYTICS & DASHBOARD KPI ROUTES
if ($action === 'analytics' && $method === 'GET') {
    $allRequests = $storage->getAll('requests');
    $allUsers = $storage->getAll('users');

    // Counts by status
    $byStatus = [
        'new' => 0,
        'assigned' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'rejected' => 0,
        'total' => count($allRequests)
    ];

    $byPriority = [
        'Низкий' => 0,
        'Средний' => 0,
        'Высокий' => 0,
        'Критический' => 0
    ];

    $totalCompletionTimeSeconds = 0;
    $completedCount = 0;
    $slaCompliantCount = 0;
    $slaViolatedCount = 0;

    // Employee Workload mapping
    $executors = array_filter($allUsers, fn($u) => in_array($u['role'], [Auth::ROLE_EXECUTOR, Auth::ROLE_HEAD, Auth::ROLE_ADMIN]));
    $workload = [];
    foreach ($executors as $e) {
        $workload[$e['id']] = [
            'id' => $e['id'],
            'full_name' => $e['full_name'],
            'assigned' => 0,
            'in_progress' => 0,
            'completed' => 0
        ];
    }

    foreach ($allRequests as $r) {
        $st = $r['status'] ?? 'new';
        if (isset($byStatus[$st])) {
            $byStatus[$st]++;
        }

        $pr = $r['priority'] ?? 'Средний';
        if (isset($byPriority[$pr])) {
            $byPriority[$pr]++;
        } else {
            $byPriority[$pr] = 1;
        }

        // Workload tracking
        if (!empty($r['executor_id']) && isset($workload[$r['executor_id']])) {
            if ($st === 'assigned') $workload[$r['executor_id']]['assigned']++;
            if ($st === 'in_progress') $workload[$r['executor_id']]['in_progress']++;
            if ($st === 'completed') $workload[$r['executor_id']]['completed']++;
        }

        // SLA & Avg time calculation
        if ($st === 'completed' && !empty($r['created_at']) && !empty($r['completed_at'])) {
            $createdTs = strtotime($r['created_at']);
            $completedTs = strtotime($r['completed_at']);
            if ($completedTs >= $createdTs) {
                $totalCompletionTimeSeconds += ($completedTs - $createdTs);
                $completedCount++;

                if (!empty($r['due_date'])) {
                    $dueTs = strtotime($r['due_date']);
                    if ($completedTs <= $dueTs) {
                        $slaCompliantCount++;
                    } else {
                        $slaViolatedCount++;
                    }
                }
            }
        } else if ($st !== 'completed' && !empty($r['due_date'])) {
            if (time() > strtotime($r['due_date'])) {
                $slaViolatedCount++;
            }
        }
    }

    $avgCompletionHours = $completedCount > 0 ? round(($totalCompletionTimeSeconds / $completedCount) / 3600, 1) : 0;
    $slaComplianceRate = ($slaCompliantCount + $slaViolatedCount) > 0 ? round(($slaCompliantCount / ($slaCompliantCount + $slaViolatedCount)) * 100, 1) : 100;

    sendJson([
        'success' => true,
        'analytics' => [
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'avg_completion_hours' => $avgCompletionHours,
            'sla_compliance_rate' => $slaComplianceRate,
            'sla_stats' => [
                'compliant' => $slaCompliantCount,
                'violated' => $slaViolatedCount
            ],
            'workload' => array_values($workload)
        ]
    ]);
}

// 9. EXPORT DATA ROUTE (CSV / Excel format)
if ($action === 'export' && $method === 'GET') {
    $allRequests = $storage->getAll('requests');
    $allUsers = $storage->getAll('users');
    $userMap = [];
    foreach ($allUsers as $u) {
        $userMap[$u['id']] = $u['full_name'];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="requests_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID', 'Заголовок', 'Категория', 'Приоритет', 'Статус', 'Автор', 'Исполнитель', 'Дата создания', 'Срок SLA', 'Дата завершения']);

    foreach ($allRequests as $r) {
        fputcsv($output, [
            $r['id'],
            $r['title'],
            $r['category'],
            $r['priority'],
            $r['status'],
            $userMap[$r['author_id']] ?? $r['author_id'],
            !empty($r['executor_id']) ? ($userMap[$r['executor_id']] ?? $r['executor_id']) : 'Не назначен',
            $r['created_at'],
            $r['due_date'] ?? '-',
            $r['completed_at'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

sendJson(['error' => 'Эндпоинт не найден'], 404);
