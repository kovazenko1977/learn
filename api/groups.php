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
        echo json_encode($storage->getById('groups', $id));
    } else {
        echo json_encode($storage->getAll('groups'));
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid group data']);
        exit;
    }

    $groupId = $storage->save('groups', $data);
    echo json_encode(['success' => true, 'id' => $groupId]);
} elseif ($method === 'DELETE') {
    if ($id) {
        $storage->delete('groups', $id);
        echo json_encode(['success' => true]);
    }
}
