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
    $perms = $user['permissions'] ?? [];
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        if ($user['role'] !== 'admin' && !($perms['can_upload_files'] ?? true)) {
            http_response_code(403);
            exit(json_encode(['message' => 'Forbidden (can_upload_files)']));
        }

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
        'number' => 'HOP-' . date('Ymd') . '-' . rand(1000, 9999),
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
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $requests = $storage->find('requests', ['requester_id' => $user['id']]);
    // Maximally expanded filters
    $statusFilter = $_GET['status'] ?? null;
    $priorityFilter = $_GET['priority'] ?? null;
    $wtFilter = $_GET['work_type_id'] ?? null;
    $reqFilter = $_GET['requester_id'] ?? null;
    $execFilter = $_GET['assigned_to'] ?? null;
    $locFilter = $_GET['location'] ?? null;
    $qFilter = $_GET['q'] ?? null;

    $requests = array_filter($requests, function($item) use ($from, $to, $statusFilter, $priorityFilter, $wtFilter, $reqFilter, $execFilter, $locFilter, $qFilter) {
        if ($from && strtotime($item['created_at']) < strtotime($from)) return false;
        if ($to && strtotime($item['created_at']) > strtotime($to . ' 23:59:59')) return false;
        if ($statusFilter && $item['status'] !== $statusFilter) return false;
        if ($priorityFilter && $item['priority'] !== $priorityFilter) return false;
        if ($wtFilter && $item['work_type_id'] != $wtFilter) return false;
        if ($reqFilter && $item['requester_id'] != $reqFilter) return false;
        if ($execFilter && ($item['assigned_to'] ?? '') != $execFilter) return false;
        if ($locFilter && stripos($item['location'] ?? '', $locFilter) === false) return false;
        if ($qFilter) {
            $searchStr = ($item['number'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . ($item['location'] ?? '');
            if (stripos($searchStr, $qFilter) === false) return false;
        }
        return true;
    });
    // Filter for unassigned if user has specific permission but not department/all access
    $perms = $user['permissions'] ?? [];
    if (!($perms['can_view_all_tasks'] ?? false) && ($perms['can_view_unassigned'] ?? false)) {
        $unassigned = $storage->find('requests', ['assigned_to' => null]);
        $requests = array_merge($requests, $unassigned);
        // De-duplicate by ID
        $temp = [];
        foreach($requests as $r) $temp[$r['id']] = $r;
        $requests = array_values($temp);
    }
    echo json_encode(array_values($requests));
} elseif ($action == 'my_tasks') {
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $requests = $storage->find('requests', ['assigned_to' => $user['id']]);
    // Maximally expanded filters
    $statusFilter = $_GET['status'] ?? null;
    $priorityFilter = $_GET['priority'] ?? null;
    $wtFilter = $_GET['work_type_id'] ?? null;
    $reqFilter = $_GET['requester_id'] ?? null;
    $execFilter = $_GET['assigned_to'] ?? null;
    $locFilter = $_GET['location'] ?? null;
    $qFilter = $_GET['q'] ?? null;

    $requests = array_filter($requests, function($item) use ($from, $to, $statusFilter, $priorityFilter, $wtFilter, $reqFilter, $execFilter, $locFilter, $qFilter) {
        if ($from && strtotime($item['created_at']) < strtotime($from)) return false;
        if ($to && strtotime($item['created_at']) > strtotime($to . ' 23:59:59')) return false;
        if ($statusFilter && $item['status'] !== $statusFilter) return false;
        if ($priorityFilter && $item['priority'] !== $priorityFilter) return false;
        if ($wtFilter && $item['work_type_id'] != $wtFilter) return false;
        if ($reqFilter && $item['requester_id'] != $reqFilter) return false;
        if ($execFilter && ($item['assigned_to'] ?? '') != $execFilter) return false;
        if ($locFilter && stripos($item['location'] ?? '', $locFilter) === false) return false;
        if ($qFilter) {
            $searchStr = ($item['number'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . ($item['location'] ?? '');
            if (stripos($searchStr, $qFilter) === false) return false;
        }
        return true;
    });
    echo json_encode(array_values($requests));
} elseif ($action == 'department') {
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $requests = [];
    $perms = $user['permissions'] ?? [];

    if ($user['role'] !== 'admin' && !($perms['can_view_department'] ?? ($user['role'] != 'user')) && !($perms['can_view_all_tasks'] ?? false)) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden (can_view_department)']));
    }

    if ($user['role'] == 'admin' || ($perms['can_view_all_tasks'] ?? false) || ($perms['can_edit_all'] ?? false)) {
        $requests = $storage->readCollection('requests');
    } else {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        if ($userData['department_id']) {
            $requests = $storage->find('requests', ['department_id' => $userData['department_id']]);
        }
    }
    // Maximally expanded filters
    $statusFilter = $_GET['status'] ?? null;
    $priorityFilter = $_GET['priority'] ?? null;
    $wtFilter = $_GET['work_type_id'] ?? null;
    $reqFilter = $_GET['requester_id'] ?? null;
    $execFilter = $_GET['assigned_to'] ?? null;
    $locFilter = $_GET['location'] ?? null;
    $qFilter = $_GET['q'] ?? null;

    $requests = array_filter($requests, function($item) use ($from, $to, $statusFilter, $priorityFilter, $wtFilter, $reqFilter, $execFilter, $locFilter, $qFilter) {
        if ($from && strtotime($item['created_at']) < strtotime($from)) return false;
        if ($to && strtotime($item['created_at']) > strtotime($to . ' 23:59:59')) return false;
        if ($statusFilter && $item['status'] !== $statusFilter) return false;
        if ($priorityFilter && $item['priority'] !== $priorityFilter) return false;
        if ($wtFilter && $item['work_type_id'] != $wtFilter) return false;
        if ($reqFilter && $item['requester_id'] != $reqFilter) return false;
        if ($execFilter && ($item['assigned_to'] ?? '') != $execFilter) return false;
        if ($locFilter && stripos($item['location'] ?? '', $locFilter) === false) return false;
        if ($qFilter) {
            $searchStr = ($item['number'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . ($item['location'] ?? '');
            if (stripos($searchStr, $qFilter) === false) return false;
        }
        return true;
    });
    echo json_encode(array_values($requests));
} elseif ($action == 'all') {
    $perms = $user['permissions'] ?? [];
    if ($user['role'] !== 'admin' && !($perms['can_view_all_tasks'] ?? false)) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $requests = $storage->readCollection('requests');
    // Maximally expanded filters
    $statusFilter = $_GET['status'] ?? null;
    $priorityFilter = $_GET['priority'] ?? null;
    $wtFilter = $_GET['work_type_id'] ?? null;
    $reqFilter = $_GET['requester_id'] ?? null;
    $execFilter = $_GET['assigned_to'] ?? null;
    $locFilter = $_GET['location'] ?? null;
    $qFilter = $_GET['q'] ?? null;

    $requests = array_filter($requests, function($item) use ($from, $to, $statusFilter, $priorityFilter, $wtFilter, $reqFilter, $execFilter, $locFilter, $qFilter) {
        if ($from && strtotime($item['created_at']) < strtotime($from)) return false;
        if ($to && strtotime($item['created_at']) > strtotime($to . ' 23:59:59')) return false;
        if ($statusFilter && $item['status'] !== $statusFilter) return false;
        if ($priorityFilter && $item['priority'] !== $priorityFilter) return false;
        if ($wtFilter && $item['work_type_id'] != $wtFilter) return false;
        if ($reqFilter && $item['requester_id'] != $reqFilter) return false;
        if ($execFilter && ($item['assigned_to'] ?? '') != $execFilter) return false;
        if ($locFilter && stripos($item['location'] ?? '', $locFilter) === false) return false;
        if ($qFilter) {
            $searchStr = ($item['number'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . ($item['location'] ?? '');
            if (stripos($searchStr, $qFilter) === false) return false;
        }
        return true;
    });
    echo json_encode(array_values($requests));
} elseif ($action == 'details') {
    $id = $_GET['id'] ?? 0;
    $request = $storage->findOne('requests', ['id' => $id]);
    if (!$request) {
        http_response_code(404);
        exit(json_encode(['message' => 'Request not found']));
    }
    $perms = $user['permissions'] ?? [];
    if ($user['role'] != 'admin' && !($perms['can_edit_all'] ?? false) && !($perms['can_view_all_tasks'] ?? false) && $request['requester_id'] != $user['id'] && $request['department_id'] != $user['department_id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }

    if ($user['role'] !== 'admin' && $request['requester_id'] == $user['id'] && !($perms['can_edit_own'] ?? true)) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden (can_edit_own disabled)']));
    }
    echo json_encode($request);
} elseif ($action == 'history') {
    $id = $_GET['id'] ?? 0;
    $request = $storage->findOne('requests', ['id' => $id]);
    $perms = $user['permissions'] ?? [];

    if ($user['role'] !== 'admin' && !($perms['can_view_history'] ?? true)) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden (can_view_history)']));
    }

    if ($request && $user['role'] != 'admin' && !($perms['can_edit_all'] ?? false) && !($perms['can_view_all_tasks'] ?? false) && $request['requester_id'] != $user['id'] && $request['department_id'] != $user['department_id']) {
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

    // Check granular permission for status change
    $perms = $user['permissions'] ?? ['can_status' => true];
    if (!$perms['can_status'] && $user['role'] != 'admin') {
        http_response_code(403);
        exit(json_encode(['message' => 'Permission denied (can_status)']));
    }

    if ($user['role'] != 'admin' && !($perms['can_edit_all'] ?? false) && $request['department_id'] != $user['department_id'] && ($request['requester_id'] != $user['id'] || !in_array($data['status'], ['closed', 'rejected']))) {
         // Special case for re-opening
         if ($data['status'] == 'in_progress' && $request['status'] == 'closed' && !($perms['can_reopen_requests'] ?? false)) {
             http_response_code(403);
             exit(json_encode(['message' => 'Permission denied (can_reopen_requests)']));
         }

         http_response_code(403);
         exit(json_encode(['message' => 'Forbidden']));
    }

    if ($user['role'] !== 'admin' && isset($data['priority']) && !($perms['can_change_priority'] ?? false)) {
         http_response_code(403);
         exit(json_encode(['message' => 'Forbidden (can_change_priority)']));
    }

    $updateData = [
        'status' => $data['status'],
        'updated_at' => date('c')
    ];
    if (isset($data['priority'])) $updateData['priority'] = $data['priority'];
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
} elseif ($method == 'POST' && $action == 'assign') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['assigned_to'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Invalid data']));
    }

    $perms = $user['permissions'] ?? ['can_assign' => false];
    if (!$perms['can_assign'] && $user['role'] != 'admin' && $user['role'] != 'manager') {
        http_response_code(403);
        exit(json_encode(['message' => 'Permission denied (can_assign)']));
    }

    $request = $storage->findOne('requests', ['id' => $data['id']]);
    if ($user['role'] != 'admin' && !($perms['can_assign_any'] ?? false) && $request['department_id'] != $user['department_id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }

    $storage->update('requests', $data['id'], ['assigned_to' => $data['assigned_to'], 'status' => 'assigned']);
    $storage->insert('status_history', [
        'request_id' => $data['id'],
        'status' => 'assigned',
        'changed_by' => $user['id'],
        'changed_at' => date('c'),
        'comment' => 'Назначен исполнитель'
    ]);
    echo json_encode(['status' => 'ok']);
} elseif ($method == 'POST' && $action == 'delete') {
    $id = $_GET['id'] ?? 0;
    $perms = $user['permissions'] ?? ['can_delete' => false];
    if (!$perms['can_delete'] && $user['role'] != 'admin') {
        http_response_code(403);
        exit(json_encode(['message' => 'Permission denied (can_delete)']));
    }

    $request = $storage->findOne('requests', ['id' => $id]);
    if (!$request) {
        http_response_code(404);
        exit(json_encode(['message' => 'Request not found']));
    }

    if ($user['role'] != 'admin' && $request['requester_id'] != $user['id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }

    $storage->delete('requests', $id);
    echo json_encode(['status' => 'ok']);
} elseif ($action == 'get_comments') {
    $id = $_GET['id'] ?? 0;
    $request = $storage->findOne('requests', ['id' => $id]);
    if (!$request) {
        http_response_code(404);
        exit(json_encode(['message' => 'Request not found']));
    }
    if ($user['role'] != 'admin' && !($perms['can_view_all_tasks'] ?? false) && !($perms['can_edit_all'] ?? false) && $request['requester_id'] != $user['id'] && $request['department_id'] != $user['department_id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }
    $comments = $storage->find('comments', ['request_id' => $id]);

    // Enrich comments with user names
    $users = $storage->readCollection('users');
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u['full_name'];
    }

    foreach ($comments as &$c) {
        $c['user_name'] = $userMap[$c['user_id']] ?? 'Неизвестный пользователь';
    }

    echo json_encode(array_values($comments));
} elseif ($method == 'POST' && $action == 'add_comment') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['request_id']) || empty($data['message'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Invalid data']));
    }
    $request = $storage->findOne('requests', ['id' => $data['request_id']]);
    if (!$request) {
        http_response_code(404);
        exit(json_encode(['message' => 'Request not found']));
    }
    if ($user['role'] != 'admin' && $request['requester_id'] != $user['id'] && $request['department_id'] != $user['department_id']) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }

    $perms = $user['permissions'] ?? [];
    if ($user['role'] !== 'admin' && !($perms['can_comment'] ?? true)) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden (can_comment)']));
    }

    $comment = [
        'request_id' => (int)$data['request_id'],
        'user_id' => $user['id'],
        'message' => $data['message'],
        'created_at' => date('c')
    ];
    $saved = $storage->insert('comments', $comment);

    // Also log in history that a comment was added?
    // Maybe not strictly necessary if we have a separate chat view,
    // but useful for "last updated" logic.
    $storage->update('requests', $data['request_id'], ['updated_at' => date('c')]);

    echo json_encode($saved);
} elseif ($action == 'export') {
    $perms = $user['permissions'] ?? [];
    if (!in_array($user['role'], ['admin', 'manager']) && !($perms['can_export_data'] ?? false)) {
        http_response_code(403);
        exit(json_encode(['message' => 'Forbidden']));
    }
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $allRequests = $storage->readCollection('requests');

    $requests = $allRequests;
    if ($from || $to) {
        $requests = array_filter($allRequests, function($item) use ($from, $to) {
            $date = strtotime($item['created_at']);
            if ($from && $date < strtotime($from)) return false;
            if ($to && $date > strtotime($to . ' 23:59:59')) return false;
            return true;
        });
    }

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
