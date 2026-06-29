<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$storage = new Storage();
$currentUser = TokenProvider::getCurrentUser();

if (!$currentUser || $currentUser['role'] !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

if ($method === 'GET') {
    if ($id) {
        echo json_encode($storage->getById('users', $id));
    } else {
        $users = $storage->getAll('users');
        foreach ($users as &$u) unset($u['password']);
        echo json_encode($users);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    } elseif ($id) {
        $existing = $storage->getById('users', $id);
        $data['password'] = $existing['password'];
    }
    $userId = $storage->save('users', $data);
    echo json_encode(['success' => true, 'id' => $userId]);
} elseif ($method === 'DELETE') {
    if ($id) {
        $storage->delete('users', $id);
        echo json_encode(['success' => true]);
    }
}
