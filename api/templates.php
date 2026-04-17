<?php
Auth::requireRole(['admin', 'senior_admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(array_values(Storage::list('templates')));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;
    Storage::write('templates', $id, $input);
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('templates', $id);
        echo json_encode(['success' => true]);
    }
}
