<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$storage = new Storage('recipients');
$data = $storage->read();

if ($method === 'GET') {
    echo json_encode($data);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['email'])) {
        $input['id'] = uniqid();
        $input['group'] = $input['group'] ?? 'General';
        $data[] = $input;
        $storage->write($data);
        echo json_encode(['status' => 'success', 'item' => $input]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $data = array_filter($data, function($item) use ($id) {
            return $item['id'] !== $id;
        });
        $storage->write(array_values($data));
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    }
}
