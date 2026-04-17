<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor']);

$method = $_SERVER['REQUEST_METHOD'];

function checkConflicts($newAppointment, $existingAppointments) {
    foreach ($existingAppointments as $app) {
        if ($app['id'] === $newAppointment['id']) continue;
        if ($app['status'] === 'cancelled') continue;
        if ($app['date'] !== $newAppointment['date']) continue;

        $start1 = strtotime($newAppointment['date'] . ' ' . $newAppointment['time_start']);
        $end1 = strtotime($newAppointment['date'] . ' ' . $newAppointment['time_end']);
        $start2 = strtotime($app['date'] . ' ' . $app['time_start']);
        $end2 = strtotime($app['date'] . ' ' . $app['time_end']);

        if ($start1 < $end2 && $end1 > $start2) {
            if ($app['doctor_id'] === $newAppointment['doctor_id']) {
                return "Doctor is busy at this time";
            }
            if ($app['room_id'] === $newAppointment['room_id']) {
                return "Room is occupied at this time";
            }
        }
    }
    return null;
}

if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $app = Storage::read('appointments', $id);
        echo json_encode($app ?: ['error' => 'Appointment not found']);
    } else {
        echo json_encode(Storage::list('appointments'));
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);
    $id = isset($input['id']) ? $input['id'] : uniqid();
    $input['id'] = $id;

    $existing = Storage::list('appointments');
    $conflict = checkConflicts($input, $existing);

    if ($conflict) {
        echo json_encode(['error' => $conflict]);
        exit;
    }

    Storage::write('appointments', $id, $input);
    Logger::log("Appointment saved for patient: " . $input['patient_id'], "info", "system.log");
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    Auth::requireRole(['admin', 'senior_admin']);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('appointments', $id);
        echo json_encode(['success' => true]);
    }
}
