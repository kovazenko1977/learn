<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

Auth::requireLogin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    Auth::requireLogin();
    echo json_encode(Storage::read('users.json'));
    exit;
}

if ($method === 'POST') {
    Auth::requireLogin();
    $user = Auth::getCurrentUser();
    if ($user['role'] !== 'admin' && $user['role'] !== 'head') {
        http_response_code(403);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    if (!isset($data['id'])) {
        $data['id'] = time();
    }

    Storage::saveItem('users.json', $data);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    Auth::requireAdmin();
    $id = $_GET['id'] ?? null;
    if ($id) {
        Storage::deleteItem('users.json', $id);
        echo json_encode(['success' => true]);
    }
    exit;
}
