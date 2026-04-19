<?php
Auth::requireRole(['admin', 'senior_admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $room = Storage::read('rooms', $id);
        echo json_encode($room ?: ['error' => 'Room not found']);
    } else {
        echo json_encode(Storage::list('rooms'));
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;
    Storage::write('rooms', $id, $input);
    Logger::log("Room saved: " . $input['name'], "info", "system.log");
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    Auth::requireRole(['admin']);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('rooms', $id);
        echo json_encode(['success' => true]);
    }
}
