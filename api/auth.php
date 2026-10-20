<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = Storage::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'login';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST' && $action === 'login') {
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите логин и пароль']);
        exit;
    }

    $users = $storage->get('users', ['username' => $username]);
    if (empty($users)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
        exit;
    }

    $user = $users[0];
    if (!($user['active'] ?? true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Аккаунт заблокирован']);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
        exit;
    }

    $token = Auth::generateToken($user);
    unset($user['password']);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => $user
    ]);
    exit;
}

if ($action === 'me') {
    $user = Auth::requireUser();
    unset($user['password']);
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

if ($method === 'POST' && $action === 'profile') {
    $user = Auth::requireUser();

    $updateData = [];
    if (isset($input['full_name'])) $updateData['full_name'] = trim($input['full_name']);
    if (isset($input['email'])) $updateData['email'] = trim($input['email']);
    if (isset($input['phone'])) $updateData['phone'] = trim($input['phone']);
    if (isset($input['department'])) $updateData['department'] = trim($input['department']);
    if (isset($input['position'])) $updateData['position'] = trim($input['position']);
    if (isset($input['notif_email'])) $updateData['notif_email'] = (bool)$input['notif_email'];
    if (isset($input['notif_push'])) $updateData['notif_push'] = (bool)$input['notif_push'];
    if (isset($input['notif_telegram'])) $updateData['notif_telegram'] = (bool)$input['notif_telegram'];
    if (isset($input['telegram_chat_id'])) $updateData['telegram_chat_id'] = trim($input['telegram_chat_id']);

    if (!empty($input['new_password'])) {
        if (!empty($input['current_password'])) {
            if (!password_verify($input['current_password'], $user['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Текущий пароль указан неверно']);
                exit;
            }
        }
        $updateData['password'] = password_hash($input['new_password'], PASSWORD_BCRYPT);
    }

    $storage->update('users', $user['id'], $updateData);
    $updatedUser = $storage->getById('users', $user['id']);
    unset($updatedUser['password']);

    echo json_encode([
        'success' => true,
        'message' => 'Профиль успешно обновлён',
        'user' => $updatedUser
    ]);
    exit;
}

if ($method === 'POST' && $action === 'reset-password') {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');

    if (!$username || !$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите логин и email']);
        exit;
    }

    $users = $storage->get('users', ['username' => $username, 'email' => $email]);
    if (empty($users)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Пользователь с указанными данными не найден']);
        exit;
    }

    $user = $users[0];
    // Generate temp password
    $tempPass = 'pass' . rand(100000, 999999);
    $storage->update('users', $user['id'], ['password' => password_hash($tempPass, PASSWORD_BCRYPT)]);

    echo json_encode([
        'success' => true,
        'message' => "Временный пароль для входа сгенерирован: {$tempPass}. Смените его в настройках профиля."
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Неизвестный запрос']);
