<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = Auth::authenticate($data['username'] ?? '', $data['password'] ?? '');
    if ($result) {
        echo json_encode(['success' => true, 'token' => $result['token'], 'user' => $result['user']]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
    exit;
}

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($action === 'me') {
    echo json_encode($user);
    exit;
}
