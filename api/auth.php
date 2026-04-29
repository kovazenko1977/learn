<?php
header('Content-Type: application/json');
require_once '../includes/Storage.php';
require_once '../includes/Auth.php';

$storage = new Storage('../data');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($action == 'login' && $method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = Auth::login($data['login'], $data['password'], $storage);
    if ($result) {
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
    }
} elseif ($action == 'me') {
    $user = Auth::check();
    if ($user) {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        unset($userData['password_hash']);
        echo json_encode($userData);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
    }
}
