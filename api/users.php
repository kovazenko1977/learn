<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$currentUser = Auth::requireUser();
$storage = Storage::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Users management requires Admin or Manager role
if ($currentUser['role'] !== 'admin' && $currentUser['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}

if ($method === 'GET') {
    $users = $storage->get('users');
    foreach ($users as &$u) {
        unset($u['password']);
    }
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

if ($method === 'POST') {
    // Only Admin can create or update users
    if ($currentUser['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Только администратор может управлять пользователями']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = $input['id'] ?? null;

    if ($id) {
        // Update user
        $existing = $storage->getById('users', $id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
            exit;
        }

        $updateData = [
            'full_name' => trim($input['full_name'] ?? $existing['full_name']),
            'email' => trim($input['email'] ?? $existing['email']),
            'phone' => trim($input['phone'] ?? $existing['phone']),
            'role' => trim($input['role'] ?? $existing['role']),
            'department' => trim($input['department'] ?? $existing['department']),
            'position' => trim($input['position'] ?? $existing['position']),
            'active' => isset($input['active']) ? (bool)$input['active'] : ($existing['active'] ?? true)
        ];

        if (!empty($input['password'])) {
            $updateData['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        $storage->update('users', $id, $updateData);
        $updated = $storage->getById('users', $id);
        unset($updated['password']);

        echo json_encode(['success' => true, 'message' => 'Пользователь обновлён', 'user' => $updated]);
        exit;
    } else {
        // Create user
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $fullName = trim($input['full_name'] ?? '');
        $role = trim($input['role'] ?? 'responsible');

        if (!$username || !$password || !$fullName) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Заполните все обязательные поля (логин, пароль, ФИО)']);
            exit;
        }

        // Check if username already exists
        $existing = $storage->get('users', ['username' => $username]);
        if (!empty($existing)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Пользователь с таким логином уже существует']);
            exit;
        }

        $newUser = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => $fullName,
            'email' => trim($input['email'] ?? ''),
            'phone' => trim($input['phone'] ?? ''),
            'role' => $role,
            'department' => trim($input['department'] ?? ''),
            'position' => trim($input['position'] ?? ''),
            'active' => true,
            'avatar' => '',
            'notif_email' => true,
            'notif_push' => true,
            'notif_telegram' => false,
            'telegram_chat_id' => ''
        ];

        $created = $storage->insert('users', $newUser);
        unset($created['password']);

        echo json_encode(['success' => true, 'message' => 'Пользователь создан', 'user' => $created]);
        exit;
    }
}

if ($method === 'DELETE') {
    if ($currentUser['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
        exit;
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Укажите ID пользователя']);
        exit;
    }

    if ($id == $currentUser['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Нельзя удалить собственный аккаунт']);
        exit;
    }

    $storage->delete('users', $id);
    echo json_encode(['success' => true, 'message' => 'Пользователь удалён']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
