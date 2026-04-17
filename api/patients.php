<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor', 'patient']);

$method = $_SERVER['REQUEST_METHOD'];


if ($method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    // Patient can only see their own record
    if ($_SESSION['role'] === 'patient' && $id !== $_SESSION['user_id']) {
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    if ($id) {
        $patient = Storage::read('patients', $id);
        if ($patient) {
            echo json_encode($patient);
        } else {
            echo json_encode(['error' => 'Patient not found']);
        }
    } else {
        if ($_SESSION['role'] === 'patient') {
            echo json_encode([]);
            exit;
        }
        $patients = Storage::list('patients');
        echo json_encode(array_values($patients));
    }
} elseif ($method === 'POST') {
    if ($_SESSION['role'] === 'patient') {
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);

    $id = isset($input['id']) ? $input['id'] : uniqid();
    $existing = Storage::read('patients', $id);

    // Duplicate check (by phone or email)
    $all = Storage::list('patients');
    foreach ($all as $p) {
        if ($p['id'] === $id) continue;
        if ((!empty($input['phone']) && $p['phone'] === $input['phone']) ||
            (!empty($input['email']) && $p['email'] === $input['email'])) {
            echo json_encode(['error' => 'Patient with this phone or email already exists']);
            exit;
        }
    }

    if ($existing) {
        Storage::saveVersion('patients', $id, $existing);
    }

    $input['id'] = $id;
    $input['updated_at'] = date('c');
    $input['updated_by'] = $_SESSION['user_id'];

    if (!$existing) {
        $input['created_at'] = date('c');
        $input['created_by'] = $_SESSION['user_id'];
    }

    Storage::write('patients', $id, $input);
    Logger::log("Patient saved: " . $input['full_name'], "info", "system.log");
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    Auth::requireRole(['admin']);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('patients', $id);
        Logger::log("Patient deleted: $id", "warning", "system.log");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'ID required']);
    }
}
