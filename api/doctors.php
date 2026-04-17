<?php
Auth::requireRole(['admin', 'senior_admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $doctor = Storage::read('doctors', $id);
        echo json_encode($doctor ?: ['error' => 'Doctor not found']);
    } else {
        $doctors = Storage::list('doctors');
        echo json_encode(array_values($doctors));
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;
    Storage::write('doctors', $id, $input);
    Logger::log("Doctor saved: " . $input['full_name'], "info", "system.log");
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    Auth::requireRole(['admin']);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('doctors', $id);
        echo json_encode(['success' => true]);
    }
}
