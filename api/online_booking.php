<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Logger.php';

// Public endpoint for online booking
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET') {
    if ($action === 'get_services') {
        echo json_encode(array_values(Storage::list('services')));
    } elseif ($action === 'get_doctors') {
        echo json_encode(array_values(Storage::list('doctors')));
    } else {
        echo json_encode([
            'doctors' => array_values(Storage::list('doctors')),
            'services' => array_values(Storage::list('services'))
        ]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);

    // Simple logic to create a patient if doesn't exist, or find by phone
    $phone = isset($input['phone']) ? $input['phone'] : '';
    if (empty($phone)) {
        echo json_encode(['error' => 'Phone is required']);
        exit;
    }

    $patients = Storage::list('patients');
    $patientId = null;
    foreach ($patients as $p) {
        if ($p['phone'] === $phone) {
            $patientId = $p['id'];
            break;
        }
    }

    if (!$patientId) {
        $patientId = uniqid();
        Storage::write('patients', $patientId, [
            'id' => $patientId,
            'full_name' => $input['full_name'],
            'phone' => $input['phone'],
            'email' => isset($input['email']) ? $input['email'] : '',
            'created_at' => date('c'),
            'created_by' => 'online_booking'
        ]);
    }

    $appointmentId = uniqid();
    $appointment = [
        'id' => $appointmentId,
        'patient_id' => $patientId,
        'doctor_id' => !empty($input['doctor_id']) ? $input['doctor_id'] : 'any',
        'service_id' => $input['service_id'],
        'room_id' => isset($input['room_id']) ? $input['room_id'] : 'default',
        'date' => $input['date'],
        'time_start' => $input['time_start'],
        'status' => 'planned',
        'source_id' => 'online',
        'created_at' => date('c')
    ];

    // Auto-calculate end time
    $service = Storage::read('services', $input['service_id']);
    if ($service && !empty($service['duration_minutes'])) {
        $start = strtotime($input['date'] . ' ' . $input['time_start']);
        $end = $start + ($service['duration_minutes'] * 60);
        $appointment['time_end'] = date('H:i', $end);
    } else {
        $appointment['time_end'] = date('H:i', strtotime($input['time_start']) + 1800); // 30m default
    }

    // Conflict detection
    $all = Storage::list('appointments');
    foreach ($all as $app) {
        if ($app['status'] === 'cancelled') continue;
        if ($app['date'] !== $appointment['date']) continue;

        $start1 = strtotime($appointment['date'] . ' ' . $appointment['time_start']);
        $end1 = strtotime($appointment['date'] . ' ' . $appointment['time_end']);
        $start2 = strtotime($app['date'] . ' ' . $app['time_start']);
        $end2 = strtotime($app['date'] . ' ' . $app['time_end']);

        if ($start1 < $end2 && $end1 > $start2) {
            if ($appointment['doctor_id'] !== 'any' && $app['doctor_id'] === $appointment['doctor_id']) {
                 echo json_encode(['error' => 'Doctor is busy at this time.']);
                 exit;
            }
            if ($app['room_id'] === $appointment['room_id']) {
                echo json_encode(['error' => 'This slot is already booked. Please choose another time.']);
                exit;
            }
        }
    }

    Storage::write('appointments', $appointmentId, $appointment);
    Logger::log("Online booking created for $phone", "info", "system.log");
    echo json_encode(['success' => true, 'id' => $appointmentId]);
}
