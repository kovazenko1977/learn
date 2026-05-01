<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$storage = new Storage();
$user = Auth::checkRole(['admin', 'head', 'executor', 'employee']);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'list') {
    $requests = $storage->getAll('requests');

    // Filtering based on role
    if ($user['role'] === 'employee') {
        $requests = array_values(array_filter($requests, function($r) use ($user) {
            return $r['creator_id'] === $user['id'];
        }));
    } elseif ($user['role'] === 'head') {
        $requests = array_values(array_filter($requests, function($r) use ($user) {
            return $r['department_id'] === $user['department_id'];
        }));
    } elseif ($user['role'] === 'executor') {
        $requests = array_values(array_filter($requests, function($r) use ($user) {
            return $r['executor_id'] === $user['id'] || $r['status'] === 'new';
        }));
    }

    echo json_encode($requests);
} elseif ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);

    $newRequest = [
        'id' => 'req_' . uniqid(),
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'category' => $data['category'] ?? '',
        'priority' => $data['priority'] ?? 'Средний',
        'status' => 'new',
        'creator_id' => $user['id'],
        'creator_name' => $user['name'],
        'executor_id' => null,
        'department_id' => $user['department_id'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'custom_fields' => $data['custom_fields'] ?? [],
        'attachments' => []
    ];

    // SLA calculation
    $settings = $storage->getById('settings', 'sla');
    $slaHours = $settings['value'][$newRequest['priority']] ?? 24;
    $newRequest['deadline'] = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

    $storage->save('requests', $newRequest);
    echo json_encode($newRequest);
} elseif ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? '';
    $request = $storage->getById('requests', $id);
    if ($request) {
        $comments = $storage->getAll('comments');
        $request['comments'] = array_values(array_filter($comments, function($c) use ($id) {
            return $c['request_id'] === $id;
        }));
        echo json_encode($request);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['error' => 'Request not found']);
    }
} elseif ($method === 'POST' && $action === 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $status = $data['status'] ?? '';

    $request = $storage->getById('requests', $id);
    if ($request) {
        // RBAC for status change
        $canChange = false;
        if ($user['role'] === 'admin' || $user['role'] === 'head') $canChange = true;
        if ($user['role'] === 'executor' && $request['executor_id'] === $user['id']) $canChange = true;

        if ($canChange) {
            $request['status'] = $status;
            $request['updated_at'] = date('Y-m-d H:i:s');
            $storage->save('requests', $request);
            echo json_encode(['success' => true]);
        } else {
            header('HTTP/1.0 403 Forbidden');
            echo json_encode(['error' => 'Permission denied']);
        }
    }
} elseif ($method === 'POST' && $action === 'assign') {
    Auth::checkRole(['admin', 'head']);
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $executor_id = $data['executor_id'] ?? null;

    $request = $storage->getById('requests', $id);
    if ($request) {
        $request['executor_id'] = $executor_id;
        if ($executor_id) {
            $request['status'] = 'assigned';
        }
        $request['updated_at'] = date('Y-m-d H:i:s');
        $storage->save('requests', $request);
        echo json_encode(['success' => true]);
    }
} elseif ($method === 'GET' && $action === 'export') {
    Auth::checkRole(['admin', 'head']);
    $requests = $storage->getAll('requests');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=requests.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Category', 'Priority', 'Status', 'Creator', 'Created At', 'Deadline']);

    foreach ($requests as $r) {
        fputcsv($output, [
            $r['id'],
            $r['title'],
            $r['category'],
            $r['priority'],
            $r['status'],
            $r['creator_name'],
            $r['created_at'],
            $r['deadline']
        ]);
    }
    fclose($output);
    exit;
} elseif ($method === 'POST' && $action === 'add_comment') {
    $data = json_decode(file_get_contents('php://input'), true);
    $request_id = $data['request_id'] ?? '';
    $text = $data['text'] ?? '';

    if ($request_id && $text) {
        $comment = [
            'id' => 'com_' . uniqid(),
            'request_id' => $request_id,
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s'),
            'files' => $data['files'] ?? []
        ];
        $storage->save('comments', $comment);
        echo json_encode($comment);
    }
}
