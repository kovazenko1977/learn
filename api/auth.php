<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $users = Storage::read('users.json');
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $token = TokenProvider::generate([
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]);
            echo json_encode(['token' => $token, 'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['full_name']
            ]]);
            exit;
        }
    }

    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

if ($action === 'me') {
    $user = Auth::authenticate();
    if ($user) {
        echo json_encode($user);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
    }
    exit;
}

if ($action === 'logout') {
    // Client just needs to discard token, but we can do server-side log if needed
    echo json_encode(['success' => true]);
    exit;
}
