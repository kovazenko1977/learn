<?php
Auth::requireRole(['admin', 'senior_admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $service = Storage::read('services', $id);
        echo json_encode($service ?: ['error' => 'Service not found']);
    } else {
        $items = Storage::list('services');
        echo json_encode(array_values($items));
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;
    Storage::write('services', $id, $input);
    Logger::log("Service saved: " . $input['name'], "info", "system.log");
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    Auth::requireRole(['admin']);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('services', $id);
        echo json_encode(['success' => true]);
    }
}
