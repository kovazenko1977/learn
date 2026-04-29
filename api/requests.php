<?php
header('Content-Type: application/json');
require_once '../includes/Storage.php';
require_once '../includes/Auth.php';

$storage = new Storage('../data');
$user = Auth::check();
if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST' && $action == 'create') {
    $data = $_POST;
    $workType = $storage->findOne('work_types', ['id' => $data['work_type_id']]);

    $file_path = null;
    $file_name = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            exit(json_encode(['message' => 'Invalid file type']));
        }

        $dest = '../uploads/' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $file_path = $dest;
            $file_name = $_FILES['file']['name'];
        }
    }

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
        'updated_at' => date('c')
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
        echo json_encode($storage->find('requests', ['department_id' => $userData['department_id']]));
    }
} elseif ($action == 'details') {
    $id = $_GET['id'];
    echo json_encode($storage->findOne('requests', ['id' => $id]));
} elseif ($action == 'history') {
    $id = $_GET['id'];
    echo json_encode($storage->find('status_history', ['request_id' => $id]));
} elseif ($method == 'POST' && $action == 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $status = $data['status'];
    $comment = $data['comment'] ?? '';

    $storage->update('requests', $id, [
        'status' => $status,
        'updated_at' => date('c')
    ]);
    $storage->insert('status_history', [
        'request_id' => $id,
        'status' => $status,
        'changed_by' => $user['id'],
        'changed_at' => date('c'),
        'comment' => $comment
    ]);
    echo json_encode(['status' => 'ok']);
} elseif ($action == 'export') {
    $requests = $storage->readCollection('requests');
    $workTypes = $storage->readCollection('work_types');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=requests.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($output, ['Номер', 'Тип', 'Место', 'Статус', 'Дата'], ';');

    foreach ($requests as $r) {
        $wt = array_filter($workTypes, fn($w) => $w['id'] == $r['work_type_id']);
        $wtName = count($wt) ? reset($wt)['name'] : $r['work_type_id'];
        fputcsv($output, [$r['number'], $wtName, $r['location'], $r['status'], $r['created_at']], ';');
    }
    fclose($output);
}
