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
$adminOnly = [
    'create_user', 'update_user', 'delete_user', 'reset_password',
    'create_worktype', 'update_worktype', 'delete_worktype',
    'create_department', 'update_department', 'delete_department',
    'update_settings', 'backup', 'restore', 'login_logs',
    'update_config', 'init_mysql', 'get_config', 'list_files', 'delete_file',
    'get_perm_templates', 'save_perm_template', 'delete_perm_template',
    'seed_demo', 'clear_demo'
];
if ($authEnabled && in_array($action, $adminOnly)) {
    $perms = $user['permissions'] ?? [];
    $is_admin = $user['role'] === 'admin';
    $has_sys = $perms['can_manage_system'] ?? false;

    if (!$is_admin && !$has_sys) {
        $allowed = false;
        if (in_array($action, ['create_user', 'update_user', 'delete_user', 'reset_password']) && ($perms['can_manage_users'] ?? false)) $allowed = true;
        if (in_array($action, ['create_worktype', 'update_worktype', 'delete_worktype', 'create_department', 'update_department', 'delete_department']) && ($perms['can_manage_structure'] ?? false)) $allowed = true;
        if ($action == 'update_settings' && ($perms['can_manage_settings'] ?? false)) $allowed = true;
        if (in_array($action, ['backup', 'restore']) && ($perms['can_manage_backups'] ?? false)) $allowed = true;
        if ($action == 'login_logs' && ($perms['can_view_logs'] ?? false)) $allowed = true;
        if (in_array($action, ['list_files', 'delete_file']) && ($perms['can_manage_files'] ?? false)) $allowed = true;
        if (in_array($action, ['get_perm_templates', 'save_perm_template', 'delete_perm_template']) && ($perms['can_manage_users'] ?? false)) $allowed = true;
        if ($action == 'seed_demo' && ($perms['can_seed_demo'] ?? false)) $allowed = true;
        if ($action == 'clear_demo' && ($perms['can_clear_data'] ?? false)) $allowed = true;

        if (!$allowed) {
            http_response_code(403);
            exit(json_encode(['message' => 'Forbidden: Insufficient permissions for ' . $action]));
        }
    }
}

// Read actions allow managers/executors for lookups
$authenticatedOnly = ['users', 'worktypes', 'departments'];
if ($authEnabled && in_array($action, $authenticatedOnly) && $user === null) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
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
            'can_status' => (bool)($data['permissions']['can_status'] ?? true),
            'can_delete' => (bool)($data['permissions']['can_delete'] ?? false),
            'can_delete_any' => (bool)($data['permissions']['can_delete_any'] ?? false),
            'can_assign' => (bool)($data['permissions']['can_assign'] ?? false),
            'can_assign_any' => (bool)($data['permissions']['can_assign_any'] ?? false),
            'can_view_all_tasks' => (bool)($data['permissions']['can_view_all_tasks'] ?? false),
            'can_view_department' => (bool)($data['permissions']['can_view_department'] ?? ($data['role'] != 'user')),
            'can_view_reports' => (bool)($data['permissions']['can_view_reports'] ?? false),
            'can_manage_system' => (bool)($data['permissions']['can_manage_system'] ?? false),
            'can_manage_users' => (bool)($data['permissions']['can_manage_users'] ?? false),
            'can_manage_structure' => (bool)($data['permissions']['can_manage_structure'] ?? false),
            'can_manage_settings' => (bool)($data['permissions']['can_manage_settings'] ?? false),
            'can_manage_backups' => (bool)($data['permissions']['can_manage_backups'] ?? false),
            'can_view_logs' => (bool)($data['permissions']['can_view_logs'] ?? false),
            'can_manage_files' => (bool)($data['permissions']['can_manage_files'] ?? false),
            'can_export_data' => (bool)($data['permissions']['can_export_data'] ?? false),
            'can_access_chat' => (bool)($data['permissions']['can_access_chat'] ?? true),
            'can_view_chat' => (bool)($data['permissions']['can_view_chat'] ?? true),
            'can_edit_requests' => (bool)($data['permissions']['can_edit_requests'] ?? false),
            'can_edit_all' => (bool)($data['permissions']['can_edit_all'] ?? false),
            'can_reopen_requests' => (bool)($data['permissions']['can_reopen_requests'] ?? false),
            'can_seed_demo' => (bool)($data['permissions']['can_seed_demo'] ?? false),
            'can_clear_data' => (bool)($data['permissions']['can_clear_data'] ?? false),
            'can_view_history' => (bool)($data['permissions']['can_view_history'] ?? true),
            'can_change_priority' => (bool)($data['permissions']['can_change_priority'] ?? false),
            'can_comment' => (bool)($data['permissions']['can_comment'] ?? true),
            'can_edit_own' => (bool)($data['permissions']['can_edit_own'] ?? true),
            'can_upload_files' => (bool)($data['permissions']['can_upload_files'] ?? true),
            'can_delete_comments' => (bool)($data['permissions']['can_delete_comments'] ?? false),
            'can_view_unassigned' => (bool)($data['permissions']['can_view_unassigned'] ?? false)
        ]
    ];
    $saved = $storage->insert('users', $newUser);
    unset($saved['password_hash']);
    echo json_encode($saved);
} elseif ($action == 'update_user' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$id || empty($data['login']) || empty($data['role'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID, Login and Role required']));
    }
    $update = [
        'login' => $data['login'],
        'full_name' => $data['full_name'] ?? $data['login'],
        'role' => $data['role'],
        'department_id' => $data['department_id'] ?? null,
        'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        'permissions' => [
            'can_status' => (bool)($data['permissions']['can_status'] ?? true),
            'can_delete' => (bool)($data['permissions']['can_delete'] ?? false),
            'can_delete_any' => (bool)($data['permissions']['can_delete_any'] ?? false),
            'can_assign' => (bool)($data['permissions']['can_assign'] ?? false),
            'can_assign_any' => (bool)($data['permissions']['can_assign_any'] ?? false),
            'can_view_all_tasks' => (bool)($data['permissions']['can_view_all_tasks'] ?? false),
            'can_view_department' => (bool)($data['permissions']['can_view_department'] ?? ($data['role'] != 'user')),
            'can_view_reports' => (bool)($data['permissions']['can_view_reports'] ?? false),
            'can_manage_system' => (bool)($data['permissions']['can_manage_system'] ?? false),
            'can_manage_users' => (bool)($data['permissions']['can_manage_users'] ?? false),
            'can_manage_structure' => (bool)($data['permissions']['can_manage_structure'] ?? false),
            'can_manage_settings' => (bool)($data['permissions']['can_manage_settings'] ?? false),
            'can_manage_backups' => (bool)($data['permissions']['can_manage_backups'] ?? false),
            'can_view_logs' => (bool)($data['permissions']['can_view_logs'] ?? false),
            'can_manage_files' => (bool)($data['permissions']['can_manage_files'] ?? false),
            'can_export_data' => (bool)($data['permissions']['can_export_data'] ?? false),
            'can_access_chat' => (bool)($data['permissions']['can_access_chat'] ?? true),
            'can_view_chat' => (bool)($data['permissions']['can_view_chat'] ?? true),
            'can_edit_requests' => (bool)($data['permissions']['can_edit_requests'] ?? false),
            'can_edit_all' => (bool)($data['permissions']['can_edit_all'] ?? false),
            'can_reopen_requests' => (bool)($data['permissions']['can_reopen_requests'] ?? false),
            'can_seed_demo' => (bool)($data['permissions']['can_seed_demo'] ?? false),
            'can_clear_data' => (bool)($data['permissions']['can_clear_data'] ?? false),
            'can_view_history' => (bool)($data['permissions']['can_view_history'] ?? true),
            'can_change_priority' => (bool)($data['permissions']['can_change_priority'] ?? false),
            'can_comment' => (bool)($data['permissions']['can_comment'] ?? true),
            'can_edit_own' => (bool)($data['permissions']['can_edit_own'] ?? true),
            'can_upload_files' => (bool)($data['permissions']['can_upload_files'] ?? true),
            'can_delete_comments' => (bool)($data['permissions']['can_delete_comments'] ?? false),
            'can_view_unassigned' => (bool)($data['permissions']['can_view_unassigned'] ?? false)
        ]
    ];
    if (!empty($data['password'])) {
        $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    echo json_encode($storage->update('users', $id, $update));
} elseif ($action == 'delete_user' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID required']));
    }
    // Safety: don't delete self
    if ($authEnabled && $user && $user['id'] == $id) {
        http_response_code(400);
        exit(json_encode(['message' => 'Cannot delete yourself']));
    }
    echo json_encode(['success' => $storage->delete('users', $id)]);
} elseif ($action == 'reset_password' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['user_id']) || empty($data['password'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'User ID and password required']));
    }
    $success = $storage->update('users', $data['user_id'], [
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)
    ]);
    echo json_encode(['success' => $success]);
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

    $storage->transactional('settings', function(&$items) use ($data) {
        $found = false;
        foreach ($items as &$item) {
            if ($item['id'] == 'global') {
                if (isset($data['auth_enabled'])) $item['auth_enabled'] = (bool)$data['auth_enabled'];
                if (isset($data['announcement'])) $item['announcement'] = (string)$data['announcement'];
                if (isset($data['notify_sound'])) $item['notify_sound'] = (bool)$data['notify_sound'];
                if (isset($data['notify_browser'])) $item['notify_browser'] = (bool)$data['notify_browser'];
                if (isset($data['notify_new_text'])) $item['notify_new_text'] = (string)$data['notify_new_text'];
                if (isset($data['org_name'])) $item['org_name'] = (string)$data['org_name'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = [
                'id' => 'global',
                'auth_enabled' => (bool)($data['auth_enabled'] ?? false),
                'announcement' => (string)($data['announcement'] ?? ''),
                'notify_sound' => (bool)($data['notify_sound'] ?? false),
                'notify_browser' => (bool)($data['notify_browser'] ?? false),
                'notify_new_text' => (string)($data['notify_new_text'] ?? ''),
                'org_name' => (string)($data['org_name'] ?? 'HOP CRM')
            ];
        }
    });

    echo json_encode(['message' => 'Settings updated']);
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
} elseif ($action == 'update_config' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['mode'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Mode required']));
    }
    $storage->setConfig($data);
    echo json_encode(['message' => 'Configuration updated']);
} elseif ($action == 'init_mysql') {
    if ($storage->getMode() !== 'mysql') {
        http_response_code(400);
        exit(json_encode(['message' => 'Switch to MySQL mode first']));
    }
    $success = $storage->initMySQL();
    echo json_encode(['success' => $success]);
} elseif ($action == 'list_files') {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $files = [];
    foreach (scandir($uploadDir) as $f) {
        if ($f == '.' || $f == '..') continue;
        $files[] = [
            'name' => $f,
            'size' => filesize($uploadDir . $f),
            'date' => date('c', filemtime($uploadDir . $f))
        ];
    }
    echo json_encode($files);
} elseif ($action == 'delete_file') {
    $file = basename($_GET['file']);
    $uploadDir = __DIR__ . '/../uploads/';
    $path = $uploadDir . $file;
    if (file_exists($path)) {
        unlink($path);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'File not found']);
    }
} elseif ($action == 'get_perm_templates') {
    echo json_encode($storage->readCollection('permission_templates'));
} elseif ($action == 'save_perm_template' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['name']) || empty($data['permissions'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Name and Permissions required']));
    }

    // Check if exists
    $existing = $storage->findOne('permission_templates', ['name' => $data['name']]);
    if ($existing) {
        $storage->update('permission_templates', $existing['id'], ['permissions' => $data['permissions']]);
        echo json_encode(['success' => true, 'id' => $existing['id']]);
    } else {
        $new = $storage->insert('permission_templates', [
            'name' => $data['name'],
            'permissions' => $data['permissions']
        ]);
        echo json_encode($new);
    }
} elseif ($action == 'seed_demo') {
    $depts = $storage->readCollection('departments');
    $wtypes = $storage->readCollection('work_types');

    // Auto-create departments and work types if missing
    if (empty($depts)) {
        $storage->insert('departments', ['id' => 'd1', 'name' => 'IT отдел', 'description' => 'Поддержка техники']);
        $storage->insert('departments', ['id' => 'd2', 'name' => 'Хозяйственный отдел', 'description' => 'Ремонт и уборка']);
        $storage->insert('departments', ['id' => 'd3', 'name' => 'Энергетики', 'description' => 'Электрика и освещение']);
        $depts = $storage->readCollection('departments');
    }

    if (empty($wtypes)) {
        $storage->insert('work_types', ['id' => 'w1', 'name' => 'Компьютерная помощь', 'department_id' => 'd1', 'sla_hours' => 8]);
        $storage->insert('work_types', ['id' => 'w2', 'name' => 'Сантехника', 'department_id' => 'd2', 'sla_hours' => 24]);
        $storage->insert('work_types', ['id' => 'w3', 'name' => 'Электрика', 'department_id' => 'd3', 'sla_hours' => 12]);
        $storage->insert('work_types', ['id' => 'w4', 'name' => 'Мебель', 'department_id' => 'd2', 'sla_hours' => 48]);
        $wtypes = $storage->readCollection('work_types');
    }

    $allUsers = $storage->readCollection('users');
    $users = array_values(array_filter($allUsers, fn($u) => in_array($u['role'], ['user', 'executor', 'admin'])));

    if (empty($users)) {
        // Create a default admin if somehow missing
        $storage->insert('users', [
            'id' => 'u1', 'login' => 'admin', 'full_name' => 'Администратор (Демо)',
            'role' => 'admin', 'password_hash' => password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        $users = $storage->readCollection('users');
    }

    $locations = ['Корпус А, 1 этаж, 101', 'Корпус Б, 3 этаж, 305', 'Цех №2, участок сборки', 'Офис, ресепшн', 'Склад №4', 'Серверная', 'Столовая'];
    $descs = [
        'Нужен ремонт смесителя, течет вода',
        'Не горит свет в коридоре, моргает лампа',
        'Просьба починить стул (сломана ножка)',
        'Компьютер не включается, черный экран',
        'Забилась раковина на кухне',
        'Розетка искрит при включении чайника',
        'Нужно перенести шкаф из 101 в 102',
        'Плохо работает кондиционер, дует теплым',
        'Принтер жует бумагу',
        'Нужна влажная уборка после ремонта'
    ];
    $priorities = ['normal', 'high', 'low'];
    $statuses = ['new', 'assigned', 'in_progress', 'completed', 'closed', 'rejected'];

    $count = 0;
    for ($i = 0; $i < 300; $i++) {
        $wt = $wtypes[array_rand($wtypes)];
        $requester = $users[array_rand($users)];
        $executor = ($i % 2 == 0) ? $users[array_rand($users)]['id'] : null;

        $request = [
            'number' => 'DEMO-' . date('Ymd') . '-' . sprintf('%04d', $i),
            'requester_id' => $requester['id'],
            'work_type_id' => $wt['id'],
            'department_id' => $wt['department_id'],
            'assigned_to' => $executor,
            'priority' => $priorities[array_rand($priorities)],
            'location' => $locations[array_rand($locations)],
            'description' => $descs[array_rand($descs)] . " (Демо запись #$i)",
            'status' => $statuses[array_rand($statuses)],
            'created_at' => date('c', strtotime("-" . rand(1, 30) . " days")),
            'updated_at' => date('c'),
            'deadline_at' => date('c', time() + 86400)
        ];
        $storage->insert('requests', $request);
        $count++;
    }
    echo json_encode(['success' => true, 'count' => $count]);
} elseif ($action == 'clear_demo') {
    $storage->writeCollection('requests', []);
    $storage->writeCollection('status_history', []);
    $storage->writeCollection('comments', []);
    echo json_encode(['success' => true]);
} elseif ($action == 'delete_perm_template' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        exit(json_encode(['message' => 'ID required']));
    }
    echo json_encode(['success' => $storage->delete('permission_templates', $id)]);
} elseif ($action == 'get_config') {
    $configFile = __DIR__ . '/../data/config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if (isset($config['mysql']['password'])) $config['mysql']['password'] = '********';
        echo json_encode($config);
    } else {
        echo json_encode(['mode' => 'json']);
    }
} else {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
}
