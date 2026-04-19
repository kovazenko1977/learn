<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor', 'patient']);

$action = $_GET["action"] ?? "";
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
    if ($action === 'get_free_slots') {
        $doctor_id = $_GET['doctor_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $service_id = $_GET['service_id'] ?? null;

        if (!$doctor_id || !$date || !$service_id) {
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }

        $service = Storage::read('services', $service_id);
        $duration = $service ? (int)$service['duration_minutes'] : 30;

        $settings = Storage::read('settings', 'system');
        $work_start = $settings['work_hours']['start'] ?? '09:00';
        $work_end = $settings['work_hours']['end'] ?? '20:00';

        // Check for doctor-specific schedule template
        $doctor = Storage::read('doctors', $doctor_id);
        if ($doctor && !empty($doctor['schedule_template'])) {
            $dayOfWeek = strtolower(date('l', strtotime($date))); // monday, tuesday...
            if (isset($doctor['schedule_template'][$dayOfWeek])) {
                $template = $doctor['schedule_template'][$dayOfWeek];
                if (!$template['active']) {
                    echo json_encode([]); // Doctor doesn't work this day
                    exit;
                }
                $work_start = $template['start'] ?? $work_start;
                $work_end = $template['end'] ?? $work_end;
            }
        }

        $appointments = Storage::list('appointments');
        $busy_slots = [];
        foreach ($appointments as $app) {
            if ($app['date'] === $date && $app['doctor_id'] === $doctor_id && $app['status'] !== 'cancelled') {
                $busy_slots[] = [
                    'start' => strtotime($date . ' ' . $app['time_start']),
                    'end' => strtotime($date . ' ' . $app['time_end'])
                ];
            }
        }

        $free_slots = [];
        $current = strtotime($date . ' ' . $work_start);
        $end_limit = strtotime($date . ' ' . $work_end);

        while ($current + ($duration * 60) <= $end_limit) {
            $slot_start = $current;
            $slot_end = $current + ($duration * 60);
            $is_free = true;

            foreach ($busy_slots as $busy) {
                if ($slot_start < $busy['end'] && $slot_end > $busy['start']) {
                    $is_free = false;
                    break;
                }
            }

            if ($is_free) {
                $free_slots[] = date('H:i', $slot_start);
            }
            $current += 30 * 60; // 30 min step
        }

        echo json_encode($free_slots);
        exit;
    }

    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        $app = Storage::read('appointments', $id);
        echo json_encode($app ?: ['error' => 'Appointment not found']);
    } else {
        $all = Storage::list('appointments');
        if ($_SESSION['role'] === 'patient') {
            $all = array_filter($all, fn($a) => $a['patient_id'] === $_SESSION['user_id']);
        }
        echo json_encode(array_values($all));
    }
} elseif ($method === 'POST') {
    Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor']);
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $input = Security::sanitize($input);

    if (empty($input)) {
        echo json_encode(['error' => 'Empty data']);
        exit;
    }

    // Auto-calculate time_end if missing but service_id present
    if (empty($input['time_end']) && !empty($input['service_id']) && !empty($input['time_start'])) {
        $service = Storage::read('services', $input['service_id']);
        if ($service && !empty($service['duration_minutes'])) {
            $start = strtotime($input['date'] . ' ' . $input['time_start']);
            $end = $start + ($service['duration_minutes'] * 60);
            $input['time_end'] = date('H:i', $end);
        }
    }

    $id = isset($input['id']) ? $input['id'] : uniqid();

    $existingRecord = Storage::read('appointments', $id);
    if ($existingRecord) {
        Storage::saveVersion('appointments', $id, $existingRecord);
    }

    $input['id'] = $id;

    $all = Storage::list('appointments');
    $conflict = checkConflicts($input, $all);

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
