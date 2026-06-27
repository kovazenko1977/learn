<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$storage = new Storage();
$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $users = $storage->getAll('users');
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            unset($user['password']);
            $token = TokenProvider::generateToken($user);
            echo json_encode(['success' => true, 'token' => $token, 'user' => $user]);
            exit;
        }
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
    exit;
}

if ($action === 'recover' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $data['email'] ?? '';
    $users = $storage->getAll('users');
    $userFound = null;
    foreach ($users as $u) {
        if (($u['email'] ?? '') === $email) {
            $userFound = $u;
            break;
        }
    }

    if ($userFound) {
        $token = bin2hex(random_bytes(16));
        $recovery = [
            'id' => $token,
            'user_id' => $userFound['id'],
            'expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];
        $storage->save('recovery', $recovery);
        // In real app, send email with link: ?action=reset&token=$token
        echo json_encode(['success' => true, 'message' => 'Инструкции по восстановлению отправлены на ' . $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким email не найден']);
    }
    exit;
}

if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $data['token'] ?? '';
    $password = $data['password'] ?? '';
    $recovery = $storage->getById('recovery', $token);

    if ($recovery && strtotime($recovery['expires']) > time()) {
        $user = $storage->getById('users', $recovery['user_id']);
        if ($user) {
            $user['password'] = password_hash($password, PASSWORD_BCRYPT);
            $storage->save('users', $user);
            $storage->delete('recovery', $token);
            echo json_encode(['success' => true, 'message' => 'Пароль успешно изменен']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверный или просроченный токен']);
    }
    exit;
}

// Protected routes
$currentUser = TokenProvider::getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $storage->getById('users', $currentUser['id']);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if (!empty($data['full_name'])) $user['full_name'] = $data['full_name'];
    if (!empty($data['email'])) $user['email'] = $data['email'];
    if (!empty($data['password'])) $user['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    if (!empty($data['telegram_id'])) $user['telegram_id'] = $data['telegram_id'];

    $storage->save('users', $user);
    unset($user['password']);
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = $storage->getById('users', $currentUser['id']);
    unset($user['password']);
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}
