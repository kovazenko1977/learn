<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');
$user = Auth::check();
if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST' && $action == 'create') {
    $data = $_POST;
    if (empty($data['work_type_id']) || empty($data['location']) || empty($data['description'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Missing required fields']));
    }

    $workType = $storage->findOne('work_types', ['id' => $data['work_type_id']]);
    if (!$workType) {
        http_response_code(400);
        exit(json_encode(['message' => 'Invalid work type']));
    }

    $file_path = null;
    $file_name = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            exit(json_encode(['message' => 'Invalid file type']));
        }

        $uploadsDir = __DIR__ . '/../uploads';
        if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0755, true);

        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadsDir . '/' . $fileName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $file_path = 'uploads/' . $fileName;
            $file_name = $_FILES['file']['name'];
        }
    }

    $slaHours = $workType['sla_hours'] ?? 24;
    $deadline = date('c', time() + ($slaHours * 3600));

    $request = [
        'number' => 'ХОП-' . date('Ymd') . '-' . rand(1000, 9999),
        'requester_id' => $user['id'],
        'work_type_id' => (int)$data['work_type_id'],
        'department_id' => $workType['department_id'],
        'assigned_to' => null,
        'priority' => $data['priority'] ?? 'normal',
        'location' => $data['location'],
        'description' => $data['description'],
        'status' => 'new',
        'file_path' => $file_path,
        'file_original_name' => $file_name,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'deadline_at' => $deadline
    ];

    $saved = $storage->insert('requests', $request);
    $storage->insert('status_history', [
        'request_id' => $saved['id'],
        'status' => 'new',
        'changed_by' => $user['id'],
        'changed_at' => date('c'),
        'comment' => 'Заявка создана'
    ]);
    echo json_encode($saved);
} elseif ($action == 'my') {
    echo json_encode($storage->find('requests', ['requester_id' => $user['id']]));
} elseif ($action == 'department') {
    if ($user['role'] == 'admin') {
        echo json_encode($storage->readCollection('requests'));
    } else {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        if (!$userData['department_id']) {
            echo json_encode([]);
        } else {
            echo json_encode($storage->find('requests', ['department_id' => $userData['department_id']]));
        }
    }
} elseif ($action == 'details') {
    $id = $_GET['id'] ?? 0;
    $request = $storage->findOne('requests', ['id' => $id]);
    if (!$request) {
        http_response_code(404);
        exit(json_encode(['message' => 'Request not found']));
    }
    if ($user['role'] != 'admin' && $request['requester_id'] != $user['id'] && $request['department_id'] != $user['department_id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }
    echo json_encode($request);
} elseif ($action == 'history') {
    $id = $_GET['id'] ?? 0;
    $request = $storage->findOne('requests', ['id' => $id]);
    if ($request && $user['role'] != 'admin' && $request['requester_id'] != $user['id'] && $request['department_id'] != $user['department_id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }
    echo json_encode($storage->find('status_history', ['request_id' => $id]));
} elseif ($method == 'POST' && $action == 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['status'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Invalid data']));
    }
    $id = $data['id'];
    $request = $storage->findOne('requests', ['id' => $id]);
    if (!$request) {
        http_response_code(404);
        exit(json_encode(['message' => 'Request not found']));
    }
    if ($user['role'] != 'admin' && $request['department_id'] != $user['department_id'] && ($request['requester_id'] != $user['id'] || $data['status'] != 'closed')) {
         http_response_code(403);
         exit(json_encode(['message' => 'Forbidden']));
    }
    $updateData = [
        'status' => $data['status'],
        'updated_at' => date('c')
    ];
    if ($data['status'] == 'closed' && isset($data['rating'])) {
        $updateData['rating'] = (int)$data['rating'];
    }
    $storage->update('requests', $id, $updateData);
    $storage->insert('status_history', [
        'request_id' => $id,
        'status' => $data['status'],
        'changed_by' => $user['id'],
        'changed_at' => date('c'),
        'comment' => $data['comment'] ?? ''
    ]);
    echo json_encode(['status' => 'ok']);
} elseif ($action == 'export' && in_array($user['role'], ['admin', 'manager'])) {
    $requests = $storage->readCollection('requests');
    $workTypes = $storage->readCollection('work_types');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=requests.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Номер', 'Тип', 'Место', 'Статус', 'Дата'], ';');
    foreach ($requests as $r) {
        $wt = array_filter($workTypes, fn($w) => $w['id'] == $r['work_type_id']);
        $wtName = count($wt) ? reset($wt)['name'] : $r['work_type_id'];
        fputcsv($output, [$r['number'], $wtName, $r['location'], $r['status'], $r['created_at']], ';');
    }
    fclose($output);
}
