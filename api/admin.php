<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');

// Special check for settings toggle: allow if auth is disabled OR if admin
$settings = $storage->findOne('settings', ['id' => 'global']) ?: ['auth_enabled' => false];
$authEnabled = isset($settings['auth_enabled']) ? (bool)$settings['auth_enabled'] : false;

$user = null;
if ($authEnabled) {
    $user = Auth::check();
    if (!$user) {
        http_response_code(401);
        exit(json_encode(['message' => 'Unauthorized']));
    }
}

$action = $_GET['action'] ?? '';

// Role-based access for administrative actions
$adminOnly = ['create_user', 'update_settings', 'backup', 'restore', 'login_logs', 'delete_department', 'delete_worktype'];
if (in_array($action, $adminOnly) && $user['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['message' => 'Forbidden']));
}

if ($action == 'users') {
    $users = $storage->readCollection('users');
    foreach ($users as &$u) unset($u['password_hash']);
    echo json_encode($users);
} elseif ($action == 'create_user' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['login']) || empty($data['password']) || empty($data['role'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Missing data']));
    }
    $newUser = [
        'login' => $data['login'],
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'full_name' => $data['full_name'] ?? $data['login'],
        'role' => $data['role'],
        'department_id' => $data['department_id'] ?? null,
        'is_active' => 1,
        'created_at' => date('c'),
        'permissions' => [
            'can_delete' => (bool)($data['permissions']['can_delete'] ?? false),
            'can_status' => (bool)($data['permissions']['can_status'] ?? true),
            'can_assign' => (bool)($data['permissions']['can_assign'] ?? false)
        ]
    ];
    $saved = $storage->insert('users', $newUser);
    unset($saved['password_hash']);
    echo json_encode($saved);
} elseif ($action == 'worktypes') {
    echo json_encode($storage->readCollection('work_types'));
} elseif ($action == 'create_worktype' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['name']) || empty($data['department_id'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Name and Department ID required']));
    }
    $newWT = [
        'name' => $data['name'],
        'department_id' => $data['department_id'],
        'sla_hours' => (int)($data['sla_hours'] ?? 24),
        'description' => $data['description'] ?? ''
    ];
    echo json_encode($storage->insert('work_types', $newWT));
} elseif ($action == 'update_worktype' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$id || empty($data['name']) || empty($data['department_id'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID, Name, and Department ID required']));
    }
    $update = [
        'name' => $data['name'],
        'department_id' => $data['department_id'],
        'sla_hours' => (int)($data['sla_hours'] ?? 24),
        'description' => $data['description'] ?? ''
    ];
    echo json_encode($storage->update('work_types', $id, $update));
} elseif ($action == 'delete_worktype' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID required']));
    }
    echo json_encode(['success' => $storage->delete('work_types', $id)]);
} elseif ($action == 'departments') {
    echo json_encode($storage->readCollection('departments'));
} elseif ($action == 'create_department' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['name'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Name is required']));
    }
    $newDept = [
        'name' => $data['name'],
        'manager_id' => $data['manager_id'] ?? null,
        'description' => $data['description'] ?? ''
    ];
    echo json_encode($storage->insert('departments', $newDept));
} elseif ($action == 'update_department' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$id || empty($data['name'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID and Name required']));
    }
    $update = [
        'name' => $data['name'],
        'manager_id' => $data['manager_id'] ?? null,
        'description' => $data['description'] ?? ''
    ];
    echo json_encode($storage->update('departments', $id, $update));
} elseif ($action == 'delete_department' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID required']));
    }
    echo json_encode(['success' => $storage->delete('departments', $id)]);
} elseif ($action == 'update_settings' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newSettings = [
        'id' => 'global',
        'auth_enabled' => (bool)$data['auth_enabled']
    ];
    // Atomic update
    $existing = $storage->findOne('settings', ['id' => 'global']);
    if ($existing) {
        $storage->update('settings', 'global', $newSettings);
    } else {
        $storage->insert('settings', $newSettings);
    }
    echo json_encode(['message' => 'Settings updated', 'auth_enabled' => $newSettings['auth_enabled']]);
} elseif ($action == 'backup') {
    if (!class_exists('ZipArchive')) exit(json_encode(['message' => 'ZipArchive missing']));
    $zip = new ZipArchive();
    $filename = "backup_" . date('Ymd_His') . ".zip";
    $filepath = __DIR__ . "/../data/" . $filename;
    if ($zip->open($filepath, ZipArchive::CREATE)!==TRUE) exit(json_encode(['message' => 'Zip failed']));
    foreach (glob(__DIR__ . '/../data/*.json') as $file) $zip->addFile($file, basename($file));
    $zip->close();
    echo json_encode(['message' => 'Backup created', 'file' => $filename]);
} elseif ($action == 'restore') {
    $file = basename($_GET['file']);
    $filepath = __DIR__ . "/../data/" . $file;
    if (!file_exists($filepath)) exit(json_encode(['message' => 'File missing']));
    $zip = new ZipArchive;
    if ($zip->open($filepath) === TRUE) {
        $zip->extractTo(__DIR__ . '/../data/');
        $zip->close();
        echo json_encode(['message' => 'Restore complete']);
    } else echo json_encode(['message' => 'Restore failed']);
} elseif ($action == 'login_logs') {
    $logs = $storage->readCollection('login_logs');
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $user_id = $_GET['user_id'] ?? '';

    $filtered = array_filter($logs, function($l) use ($from, $to, $user_id) {
        if ($user_id && $l['user_id'] != $user_id) return false;
        if ($from && substr($l['timestamp'], 0, 10) < $from) return false;
        if ($to && substr($l['timestamp'], 0, 10) > $to) return false;
        return true;
    });

    usort($filtered, function($a, $b) {
        return strcmp($b['timestamp'], $a['timestamp']);
    });

    echo json_encode(array_values($filtered));
} else {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
}
