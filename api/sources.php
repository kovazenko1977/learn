<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'marketing']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(Storage::list('sources'));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;
    Storage::write('sources', $id, $input);
    echo json_encode(['success' => true, 'id' => $id]);
}
