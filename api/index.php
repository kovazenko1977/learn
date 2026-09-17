<?php
/**
 * REST API Router for МедСервис
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Upload.php';

Auth::initSession();
$storage = StorageProvider::getInstance();

// Parse URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

// Strip prefix if any (e.g., /api/...)
$path = preg_replace('#^.*?/api/#', '', $path);
$path = trim($path, '/');
$segments = explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Helper to get JSON body input
function getJsonInput(): array {
    $input = file_get_contents('php://input');
    if (empty($input)) return $_POST;
    $decoded = json_decode($input, true);
    return is_array($decoded) ? $decoded : $_POST;
}

// Helper for JSON output
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    jsonResponse(['error' => $message], $code);
}

// Route Handler Switch
try {
    $endpoint = $segments[0] ?? '';

    // ==========================================
    // INSTALLER & SYSTEM SETUP
    // ==========================================
    if ($endpoint === 'setup') {
        $settings = $storage->getCollection('settings');
        $settingsData = reset($settings) ?: [];

        if ($method === 'GET') {
            jsonResponse(['installed' => !empty($settingsData['installed']), 'settings' => $settingsData]);
        }

        if ($method === 'POST') {
            if (!empty($settingsData['installed'])) {
                jsonError('Система уже инициализирована', 400);
            }

            $input = getJsonInput();
            $hospitalName = trim($input['hospital_name'] ?? 'Больница МедСервис');
            $adminName = trim($input['admin_name'] ?? 'Администратор');
            $adminPhone = trim($input['admin_phone'] ?? '+375291111111');
            $adminPassword = trim($input['admin_password'] ?? '123456');

            // Create admin user
            $adminUser = Auth::register([
                'name' => $adminName,
                'phone' => $adminPhone,
                'password' => $adminPassword,
                'role' => Auth::ROLE_ADMIN,
                'position' => 'Главный администратор',
                'department_name' => 'Администрация'
            ]);

            // Create default dispatcher user
            Auth::register([
                'name' => 'Диспетчерская служба',
                'phone' => '+375292222222',
                'password' => '123456',
                'role' => Auth::ROLE_DISPATCHER,
                'position' => 'Старший диспетчер',
                'department_name' => 'Приемное отделение'
            ]);

            // Update settings
            $settingsData['hospital_name'] = $hospitalName;
            $settingsData['installed'] = true;
            $storage->saveCollection('settings', [$settingsData]);

            // Auto login admin
            $user = Auth::login($adminPhone, $adminPassword);

            jsonResponse(['success' => true, 'message' => 'Первоначальная настройка успешно завершена', 'user' => $user]);
        }
    }

    // Check if system installed
    $settings = $storage->getCollection('settings');
    $settingsData = reset($settings) ?: [];
    if (empty($settingsData['installed']) && $endpoint !== 'auth') {
        jsonResponse(['installed' => false, 'message' => 'Требуется первоначальная настройка системы'], 200);
    }

    // ==========================================
    // AUTHENTICATION ROUTES
    // ==========================================
    if ($endpoint === 'auth') {
        $action = $segments[1] ?? '';

        if ($action === 'login' && $method === 'POST') {
            $input = getJsonInput();
            $phone = $input['phone'] ?? '';
            $pincode = $input['password'] ?? $input['pincode'] ?? '';

            $user = Auth::login($phone, $pincode);
            if (!$user) {
                jsonError('Неверный номер телефона или пароль', 401);
            }

            jsonResponse(['success' => true, 'user' => $user]);
        }

        if ($action === 'register' && $method === 'POST') {
            $input = getJsonInput();
            try {
                $user = Auth::register($input);
                jsonResponse(['success' => true, 'user' => $user]);
            } catch (Exception $e) {
                jsonError($e->getMessage(), 400);
            }
        }

        if ($action === 'logout') {
            Auth::logout();
            jsonResponse(['success' => true, 'message' => 'Вы успешно вышли из системы']);
        }
    }

    // Require logged in user for all remaining routes
    $currentUser = Auth::getCurrentUser();
    if (!$currentUser) {
        jsonError('Необходима авторизация', 401);
    }

    // Update last active time
    $storage->update('users', $currentUser['id'], ['last_active' => date('Y-m-d H:i:s')]);

    // ==========================================
    // USER & EMPLOYEES ROUTES
    // ==========================================
    if ($endpoint === 'user') {
        if ($method === 'GET') {
            unset($currentUser['password']);
            jsonResponse($currentUser);
        }

        if ($method === 'PUT' || $method === 'POST') {
            $input = getJsonInput();
            $updateData = [];

            if (isset($input['name'])) $updateData['name'] = trim($input['name']);
            if (isset($input['department_name'])) $updateData['department_name'] = trim($input['department_name']);
            if (isset($input['position'])) $updateData['position'] = trim($input['position']);
            if (isset($input['hide_phone'])) $updateData['hide_phone'] = (bool)$input['hide_phone'];
            if (isset($input['notification_settings']) && is_array($input['notification_settings'])) {
                $updateData['notification_settings'] = array_merge($currentUser['notification_settings'] ?? [], $input['notification_settings']);
            }

            if (!empty($input['password'])) {
                if (strlen($input['password']) < 4) {
                    jsonError('Пароль должен быть от 4 до 6 цифр');
                }
                $updateData['password'] = Auth::hashPassword($input['password']);
            }

            if (isset($_FILES['avatar'])) {
                $uploader = new UploadHandler();
                $updateData['avatar'] = $uploader->handleUpload($_FILES['avatar']);
            }

            $storage->update('users', $currentUser['id'], $updateData);
            $updatedUser = $storage->findOne('users', fn($u) => $u['id'] == $currentUser['id']);
            unset($updatedUser['password']);
            jsonResponse(['success' => true, 'user' => $updatedUser]);
        }
    }

    if ($endpoint === 'employees') {
        if ($method === 'GET') {
            $search = trim($_GET['search'] ?? '');
            $serviceId = (int)($_GET['service_id'] ?? 0);

            $employees = $storage->getCollection('users');

            $filtered = array_values(array_filter($employees, function($emp) use ($search, $serviceId, $currentUser) {
                if (!empty($emp['is_blocked'])) return false;

                if ($serviceId > 0 && ($emp['service_id'] ?? 0) != $serviceId) {
                    return false;
                }

                if ($search !== '') {
                    $s = mb_strtolower($search);
                    $nameMatch = mb_strpos(mb_strtolower($emp['name'] ?? ''), $s) !== false;
                    $phoneMatch = mb_strpos($emp['phone'] ?? '', $s) !== false;
                    $deptMatch = mb_strpos(mb_strtolower($emp['department_name'] ?? ''), $s) !== false;
                    $posMatch = mb_strpos(mb_strtolower($emp['position'] ?? ''), $s) !== false;
                    if (!$nameMatch && !$phoneMatch && !$deptMatch && !$posMatch) {
                        return false;
                    }
                }

                return true;
            }));

            // Format privacy fields
            $result = array_map(function($emp) use ($currentUser) {
                unset($emp['password'], $emp['token']);
                if (!empty($emp['hide_phone']) && $currentUser['role'] !== Auth::ROLE_ADMIN && $currentUser['id'] != $emp['id']) {
                    $emp['phone'] = 'Скрыт';
                }
                return $emp;
            }, $filtered);

            jsonResponse($result);
        }

        if ($method === 'POST' && $currentUser['role'] === Auth::ROLE_ADMIN) {
            $input = getJsonInput();
            try {
                $newEmp = Auth::register($input);
                unset($newEmp['password']);
                jsonResponse(['success' => true, 'employee' => $newEmp]);
            } catch (Exception $e) {
                jsonError($e->getMessage(), 400);
            }
        }

        // Employee CRUD Operations for Admin
        if (isset($segments[1]) && is_numeric($segments[1]) && $currentUser['role'] === Auth::ROLE_ADMIN) {
            $empId = (int)$segments[1];
            if ($method === 'PUT') {
                $input = getJsonInput();
                if (!empty($input['password'])) {
                    $input['password'] = Auth::hashPassword($input['password']);
                }
                $storage->update('users', $empId, $input);
                $updated = $storage->findOne('users', fn($u) => $u['id'] == $empId);
                unset($updated['password']);
                jsonResponse(['success' => true, 'employee' => $updated]);
            }
            if ($method === 'DELETE') {
                $storage->update('users', $empId, ['is_blocked' => true]);
                jsonResponse(['success' => true, 'message' => 'Сотрудник заблокирован']);
            }
        }
    }

    // ==========================================
    // SERVICES & DEPARTMENTS
    // ==========================================
    if ($endpoint === 'services') {
        if ($method === 'GET') {
            $servicesList = $storage->getCollection('services');
            jsonResponse($servicesList);
        }

        if (($method === 'POST' || $method === 'PUT') && $currentUser['role'] === Auth::ROLE_ADMIN) {
            $input = getJsonInput();
            if ($method === 'POST') {
                $created = $storage->insert('services', $input);
                jsonResponse(['success' => true, 'service' => $created]);
            } else {
                $servId = (int)($segments[1] ?? $input['id'] ?? 0);
                $storage->update('services', $servId, $input);
                jsonResponse(['success' => true, 'message' => 'Служба обновлена']);
            }
        }
    }

    if ($endpoint === 'departments') {
        if ($method === 'GET') {
            jsonResponse($storage->getCollection('departments'));
        }
    }

    // ==========================================
    // REQUESTS / TICKETS SYSTEM
    // ==========================================
    if ($endpoint === 'requests') {
        $requestId = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
        $subAction = $segments[2] ?? null;

        // GET /api/requests or GET /api/requests/{id}
        if ($method === 'GET') {
            if ($requestId !== null) {
                $req = $storage->findOne('requests', fn($r) => isset($r['id']) && $r['id'] == $requestId);
                if (!$req) jsonError('Заявка не найдена', 404);

                // Attach comments and photos
                $comments = $storage->find('comments', fn($c) => isset($c['request_id']) && $c['request_id'] == $requestId);
                $req['comments'] = array_values($comments);
                jsonResponse($req);
            } else {
                // List requests with filter & pagination
                $status = $_GET['status'] ?? null;
                $priority = $_GET['priority'] ?? null;
                $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
                $myRole = $currentUser['role'];
                $myUserId = $currentUser['id'];

                $filter = function($r) use ($status, $priority, $serviceId, $myRole, $myUserId, $currentUser) {
                    if ($status && ($r['status'] ?? '') !== $status) return false;
                    if ($priority && ($r['priority'] ?? '') !== $priority) return false;
                    if ($serviceId && ($r['service_id'] ?? 0) != $serviceId) return false;

                    // Role-based visibility
                    if ($myRole === Auth::ROLE_EMPLOYEE) {
                        return ($r['author_id'] ?? 0) == $myUserId;
                    }
                    if ($myRole === Auth::ROLE_EXECUTOR) {
                        return ($r['executor_id'] ?? 0) == $myUserId || ($r['service_id'] ?? 0) == ($currentUser['service_id'] ?? 0);
                    }
                    if ($myRole === Auth::ROLE_SERVICE_HEAD) {
                        return ($r['service_id'] ?? 0) == ($currentUser['service_id'] ?? 0) || ($r['author_id'] ?? 0) == $myUserId;
                    }

                    return true; // Dispatcher & Admin see all
                };

                $sort = function($a, $b) {
                    // Emergency first, then newest
                    if (($a['priority'] ?? '') === 'Аварийный' && ($b['priority'] ?? '') !== 'Аварийный') return -1;
                    if (($a['priority'] ?? '') !== 'Аварийный' && ($b['priority'] ?? '') === 'Аварийный') return 1;
                    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
                };

                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 30);

                $result = $storage->paginate('requests', $page, $limit, $filter, $sort);
                jsonResponse($result);
            }
        }

        // POST /api/requests (Create request or Emergency)
        if ($method === 'POST' && $requestId === null) {
            $input = getJsonInput();

            $category = trim($input['category'] ?? 'Общее');
            $building = trim($input['building'] ?? '');
            $floor = trim($input['floor'] ?? '');
            $room = trim($input['room'] ?? '');
            $description = trim($input['description'] ?? '');
            $priority = trim($input['priority'] ?? 'Обычный');
            $serviceId = (int)($input['service_id'] ?? 0);

            if (empty($description)) {
                jsonError('Заполните описание проблемы');
            }

            // Auto assign service by category if serviceId is 0
            if ($serviceId === 0) {
                $categoryMap = [
                    'Электрика' => 1,
                    'Сантехника' => 2,
                    'Отопление' => 3,
                    'Канализация' => 2,
                    'Вентиляция' => 3,
                    'Уборка' => 4,
                    'Территория' => 5,
                    'Ремонт помещений' => 6,
                    'Мебель' => 6,
                    'Оборудование' => 7,
                    'IT' => 7
                ];
                $serviceId = $categoryMap[$category] ?? 8; // Default to Дежурная служба
            }

            $serviceObj = $storage->findOne('services', fn($s) => $s['id'] == $serviceId);
            $serviceName = $serviceObj['name'] ?? 'Дежурная служба';

            // Handle multi photo uploads
            $photos = [];
            if (!empty($_FILES['photos'])) {
                $uploader = new UploadHandler();
                $fileCount = count($_FILES['photos']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if (!empty($_FILES['photos']['tmp_name'][$i])) {
                        $singleFile = [
                            'name' => $_FILES['photos']['name'][$i],
                            'type' => $_FILES['photos']['type'][$i],
                            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                            'error' => $_FILES['photos']['error'][$i],
                            'size' => $_FILES['photos']['size'][$i]
                        ];
                        $photos[] = $uploader->handleUpload($singleFile);
                    }
                }
            }

            // Calculate SLA deadlines
            $reactionMin = $serviceObj['sla_reaction_minutes'] ?? 15;
            $completionHours = $serviceObj['sla_completion_hours'] ?? 2;
            if ($priority === 'Аварийный') {
                $reactionMin = 10;
                $completionHours = 1;
            }

            $now = time();
            $slaReactionAt = date('Y-m-d H:i:s', $now + ($reactionMin * 60));
            $slaCompletionAt = date('Y-m-d H:i:s', $now + ($completionHours * 3600));

            $newReq = [
                'number' => sprintf('№%06d', count($storage->getCollection('requests')) + 150),
                'status' => 'Новая',
                'category' => $category,
                'building' => $building,
                'floor' => $floor,
                'room' => $room,
                'location_text' => trim("$building, Этаж $floor, Кабинет $room", ', '),
                'description' => $description,
                'priority' => $priority,
                'service_id' => $serviceId,
                'service_name' => $serviceName,
                'author_id' => $currentUser['id'],
                'author_name' => $currentUser['name'],
                'author_phone' => $currentUser['phone'],
                'author_department' => $currentUser['department_name'],
                'executor_id' => 0,
                'executor_name' => 'Не назначен',
                'photos' => $photos,
                'sla_reaction_deadline' => $slaReactionAt,
                'sla_completion_deadline' => $slaCompletionAt,
                'history' => [
                    [
                        'time' => date('Y-m-d H:i:s'),
                        'author' => $currentUser['name'],
                        'text' => "Заявка создана. Приоритет: $priority"
                    ]
                ]
            ];

            $createdReq = $storage->insert('requests', $newReq);

            // Create notification for dispatchers & service heads
            $storage->insert('notifications', [
                'user_id' => 0, // Broadcast to service/dispatchers
                'title' => ($priority === 'Аварийный' ? '🚨 АВАРИЙНАЯ ЗАЯВКА ' : '🔔 Новая заявка ') . $createdReq['number'],
                'message' => "Категория: {$category}, Место: {$createdReq['location_text']}",
                'request_id' => $createdReq['id'],
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => false
            ]);

            Auth::auditLog('CREATE_REQUEST', $currentUser['id'], "Created request {$createdReq['number']}");

            jsonResponse(['success' => true, 'message' => "Заявка {$createdReq['number']} успешно создана", 'request' => $createdReq]);
        }

        // POST /api/requests/{id}/comments
        if ($method === 'POST' && $requestId !== null && $subAction === 'comments') {
            $input = getJsonInput();
            $text = trim($input['comment'] ?? $input['text'] ?? '');
            if (empty($text)) jsonError('Введите текст комментария');

            $req = $storage->findOne('requests', fn($r) => $r['id'] == $requestId);
            if (!$req) jsonError('Заявка не найдена', 404);

            $comment = [
                'request_id' => $requestId,
                'user_id' => $currentUser['id'],
                'user_name' => $currentUser['name'],
                'user_role' => $currentUser['role'],
                'text' => $text,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $savedComment = $storage->insert('comments', $comment);

            // Update request history
            $history = $req['history'] ?? [];
            $history[] = [
                'time' => date('Y-m-d H:i:s'),
                'author' => $currentUser['name'],
                'text' => "Добавлен комментарий: $text"
            ];
            $storage->update('requests', $requestId, ['history' => $history]);

            jsonResponse(['success' => true, 'comment' => $savedComment]);
        }

        // POST /api/requests/{id}/photos
        if ($method === 'POST' && $requestId !== null && $subAction === 'photos') {
            $req = $storage->findOne('requests', fn($r) => $r['id'] == $requestId);
            if (!$req) jsonError('Заявка не найдена', 404);

            if (empty($_FILES['photo']) && empty($_FILES['photos'])) {
                jsonError('Файл фотографии не выбран');
            }

            $fileToUpload = $_FILES['photo'] ?? $_FILES['photos'];
            $uploader = new UploadHandler();
            $photoPath = $uploader->handleUpload($fileToUpload);

            $existingPhotos = $req['photos'] ?? [];
            $existingPhotos[] = $photoPath;

            $history = $req['history'] ?? [];
            $history[] = [
                'time' => date('Y-m-d H:i:s'),
                'author' => $currentUser['name'],
                'text' => "Загружена новая фотография"
            ];

            $storage->update('requests', $requestId, [
                'photos' => $existingPhotos,
                'history' => $history
            ]);

            jsonResponse(['success' => true, 'photo' => $photoPath, 'photos' => $existingPhotos]);
        }

        // PUT /api/requests/{id} (Update status, assign executor, reassign service)
        if (($method === 'PUT' || $method === 'POST') && $requestId !== null) {
            $input = getJsonInput();
            $req = $storage->findOne('requests', fn($r) => $r['id'] == $requestId);
            if (!$req) jsonError('Заявка не найдена', 404);

            $updateFields = [];
            $historyText = [];

            if (isset($input['status']) && $input['status'] !== $req['status']) {
                $newStatus = $input['status'];
                $updateFields['status'] = $newStatus;
                $historyText[] = "Статус изменен на «{$newStatus}»";
            }

            if (isset($input['executor_id']) && $input['executor_id'] != $req['executor_id']) {
                $execId = (int)$input['executor_id'];
                $execUser = $storage->findOne('users', fn($u) => $u['id'] == $execId);
                $execName = $execUser['name'] ?? 'Не назначен';
                $updateFields['executor_id'] = $execId;
                $updateFields['executor_name'] = $execName;
                $historyText[] = "Назначен исполнитель: {$execName}";
            }

            if (isset($input['service_id']) && $input['service_id'] != $req['service_id']) {
                $servId = (int)$input['service_id'];
                $servObj = $storage->findOne('services', fn($s) => $s['id'] == $servId);
                $servName = $servObj['name'] ?? 'Неизвестная служба';
                $updateFields['service_id'] = $servId;
                $updateFields['service_name'] = $servName;
                $historyText[] = "Перенаправлено в службу: {$servName}";
            }

            if (isset($input['priority']) && $input['priority'] !== $req['priority']) {
                $updateFields['priority'] = $input['priority'];
                $historyText[] = "Приоритет изменен на «{$input['priority']}»";
            }

            if (!empty($historyText)) {
                $history = $req['history'] ?? [];
                foreach ($historyText as $txt) {
                    $history[] = [
                        'time' => date('Y-m-d H:i:s'),
                        'author' => $currentUser['name'],
                        'text' => $txt
                    ];
                }
                $updateFields['history'] = $history;
                $storage->update('requests', $requestId, $updateFields);
            }

            $updatedReq = $storage->findOne('requests', fn($r) => $r['id'] == $requestId);
            jsonResponse(['success' => true, 'request' => $updatedReq]);
        }
    }

    // ==========================================
    // CHATS & MESSAGES
    // ==========================================
    if ($endpoint === 'chats') {
        $chatId = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
        $subAction = $segments[2] ?? null;

        // GET /api/chats
        if ($method === 'GET' && $chatId === null) {
            $chats = $storage->getCollection('chats');
            $myUserId = $currentUser['id'];

            // Attach last message and unread state
            $allMessages = $storage->getCollection('messages');

            $result = array_values(array_filter($chats, function($c) use ($myUserId) {
                if (($c['type'] ?? '') === 'general') return true;
                if (($c['type'] ?? '') === 'direct') {
                    return in_array($myUserId, $c['participants'] ?? []);
                }
                if (($c['type'] ?? '') === 'request') {
                    return true;
                }
                return false;
            }));

            foreach ($result as &$c) {
                $cMsgs = array_filter($allMessages, fn($m) => ($m['chat_id'] ?? 0) == $c['id']);
                usort($cMsgs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
                $last = reset($cMsgs);
                $c['last_message'] = $last ? $last['text'] : '';
                $c['last_time'] = $last ? $last['created_at'] : $c['created_at'];
            }

            jsonResponse($result);
        }

        // GET /api/chats/{id}
        if ($method === 'GET' && $chatId !== null) {
            $messages = $storage->find('messages', fn($m) => ($m['chat_id'] ?? 0) == $chatId);
            usort($messages, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));

            // Pagination (last 50 messages)
            $messages = array_slice($messages, -50);

            jsonResponse(['chat_id' => $chatId, 'messages' => array_values($messages)]);
        }

        // POST /api/chats or POST DM chat
        if ($method === 'POST' && $chatId === null) {
            $input = getJsonInput();
            $targetUserId = (int)($input['user_id'] ?? 0);
            if ($targetUserId <= 0) jsonError('Укажите собеседника');

            $targetUser = $storage->findOne('users', fn($u) => $u['id'] == $targetUserId);
            if (!$targetUser) jsonError('Пользователь не найден', 404);

            // Check if DM chat exists
            $myUserId = $currentUser['id'];
            $existing = $storage->findOne('chats', function($c) use ($myUserId, $targetUserId) {
                return ($c['type'] ?? '') === 'direct' &&
                       in_array($myUserId, $c['participants'] ?? []) &&
                       in_array($targetUserId, $c['participants'] ?? []);
            });

            if ($existing) {
                jsonResponse(['success' => true, 'chat' => $existing]);
            }

            $newChat = [
                'title' => $targetUser['name'],
                'type' => 'direct',
                'participants' => [$myUserId, $targetUserId],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $created = $storage->insert('chats', $newChat);
            jsonResponse(['success' => true, 'chat' => $created]);
        }

        // POST /api/chats/{id}/messages
        if ($method === 'POST' && $chatId !== null && ($subAction === 'messages' || $subAction === null)) {
            $input = getJsonInput();
            $text = trim($input['text'] ?? '');
            $photoPath = '';

            if (isset($_FILES['photo'])) {
                $uploader = new UploadHandler();
                $photoPath = $uploader->handleUpload($_FILES['photo']);
            }

            if (empty($text) && empty($photoPath)) {
                jsonError('Введите сообщение или выберите изображение');
            }

            $msg = [
                'chat_id' => $chatId,
                'user_id' => $currentUser['id'],
                'user_name' => $currentUser['name'],
                'user_avatar' => $currentUser['avatar'] ?? '',
                'text' => $text,
                'photo' => $photoPath,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $savedMsg = $storage->insert('messages', $msg);
            jsonResponse(['success' => true, 'message' => $savedMsg]);
        }
    }

    // ==========================================
    // NOTIFICATIONS & PUSH
    // ==========================================
    if ($endpoint === 'notifications') {
        if ($method === 'GET') {
            $myUserId = $currentUser['id'];
            $notifs = $storage->find('notifications', fn($n) => ($n['user_id'] ?? 0) == 0 || ($n['user_id'] ?? 0) == $myUserId);
            usort($notifs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
            jsonResponse(array_values($notifs));
        }
    }

    if ($endpoint === 'push') {
        if ($segments[1] === 'subscribe' && $method === 'POST') {
            $input = getJsonInput();
            $storage->update('users', $currentUser['id'], ['push_subscription' => $input]);
            jsonResponse(['success' => true, 'message' => 'Подписка на Push-уведомления оформлена']);
        }
    }

    // ==========================================
    // STATISTICS & ANALYTICS
    // ==========================================
    if ($endpoint === 'statistics') {
        $requests = $storage->getCollection('requests');

        $total = count($requests);
        $newCount = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'Новая'));
        $inProgressCount = count(array_filter($requests, fn($r) => in_array($r['status'] ?? '', ['Принято', 'В исполнении'])));
        $completedCount = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'Выполнено'));
        $emergencyCount = count(array_filter($requests, fn($r) => ($r['priority'] ?? '') === 'Аварийный'));

        // Overdue count
        $now = date('Y-m-d H:i:s');
        $overdueCount = count(array_filter($requests, function($r) use ($now) {
            return ($r['status'] ?? '') !== 'Выполнено' &&
                   !empty($r['sla_completion_deadline']) &&
                   $r['sla_completion_deadline'] < $now;
        }));

        // Breakdown by services
        $services = $storage->getCollection('services');
        $byService = [];
        foreach ($services as $s) {
            $sReqs = array_filter($requests, fn($r) => ($r['service_id'] ?? 0) == $s['id']);
            $byService[] = [
                'service_id' => $s['id'],
                'name' => $s['name'],
                'icon' => $s['icon'],
                'count' => count($sReqs)
            ];
        }

        jsonResponse([
            'total' => $total,
            'new' => $newCount,
            'in_progress' => $inProgressCount,
            'completed' => $completedCount,
            'emergency' => $emergencyCount,
            'overdue' => $overdueCount,
            'by_service' => $byService,
            'active_users' => count(array_filter($storage->getCollection('users'), fn($u) => empty($u['is_blocked'])))
        ]);
    }

    // ==========================================
    // EQUIPMENT & LOCATIONS
    // ==========================================
    if ($endpoint === 'equipment') {
        if ($method === 'GET') {
            jsonResponse($storage->getCollection('equipment'));
        }
    }

    if ($endpoint === 'locations') {
        if ($method === 'GET') {
            jsonResponse($storage->getCollection('locations'));
        }
    }

    // ==========================================
    // BACKUP & EXPORT (ADMIN)
    // ==========================================
    if ($endpoint === 'admin') {
        if ($currentUser['role'] !== Auth::ROLE_ADMIN) {
            jsonError('Доступ запрещен', 403);
        }

        $sub = $segments[1] ?? '';

        if ($sub === 'backup') {
            if ($method === 'GET') {
                $backupData = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'users' => $storage->getCollection('users'),
                    'requests' => $storage->getCollection('requests'),
                    'services' => $storage->getCollection('services'),
                    'comments' => $storage->getCollection('comments'),
                    'chats' => $storage->getCollection('chats'),
                    'messages' => $storage->getCollection('messages'),
                    'settings' => $storage->getCollection('settings'),
                    'equipment' => $storage->getCollection('equipment'),
                    'locations' => $storage->getCollection('locations')
                ];

                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="medservice_backup_' . date('Ymd_His') . '.json"');
                echo json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($method === 'POST') {
                $input = getJsonInput();
                if (!empty($input['restore_data']) && is_array($input['restore_data'])) {
                    $rData = $input['restore_data'];
                    foreach (['users', 'requests', 'services', 'comments', 'chats', 'messages', 'settings', 'equipment', 'locations'] as $coll) {
                        if (isset($rData[$coll]) && is_array($rData[$coll])) {
                            $storage->saveCollection($coll, $rData[$coll]);
                        }
                    }
                    jsonResponse(['success' => true, 'message' => 'Данные успешно восстановлены из резервной копии']);
                }
            }
        }

        if ($sub === 'audit') {
            if ($method === 'GET') {
                $audit = $storage->getCollection('audit');
                usort($audit, fn($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));
                jsonResponse(array_slice($audit, 0, 100));
            }
        }
    }

    // CSV Export Endpoint
    if ($endpoint === 'export') {
        if ($currentUser['role'] !== Auth::ROLE_ADMIN && $currentUser['role'] !== Auth::ROLE_DISPATCHER) {
            jsonError('Недостаточно прав для экспорта', 403);
        }

        $requests = $storage->getCollection('requests');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="requests_' . date('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($out, ['№ Заявки', 'Статус', 'Приоритет', 'Категория', 'Место', 'Описание', 'Автор', 'Служба', 'Исполнитель', 'Дата создания'], ';');

        foreach ($requests as $r) {
            fputcsv($out, [
                $r['number'] ?? '',
                $r['status'] ?? '',
                $r['priority'] ?? '',
                $r['category'] ?? '',
                $r['location_text'] ?? '',
                $r['description'] ?? '',
                $r['author_name'] ?? '',
                $r['service_name'] ?? '',
                $r['executor_name'] ?? '',
                $r['created_at'] ?? ''
            ], ';');
        }

        fclose($out);
        exit;
    }

    jsonError("Неизвестный endpoint: $endpoint", 404);

} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}
