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
    // Clear current demo data first to ensure clean state
    $storage->transactional('requests', function(&$items) { $items = []; });
    $storage->transactional('status_history', function(&$items) { $items = []; });
    $storage->transactional('global_chat', function(&$items) { $items = []; });
    $storage->transactional('comments', function(&$items) { $items = []; });
    $storage->transactional('users', function(&$items) { $items = []; });
    $storage->transactional('departments', function(&$items) { $items = []; });
    $storage->transactional('work_types', function(&$items) { $items = []; });

    // 1. Create Default Admin
    $admin = [
        'id' => 'admin_id',
        'login' => 'admin',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'full_name' => 'Главный Администратор',
        'role' => 'admin',
        'is_active' => 1,
        'created_at' => date('c'),
        'permissions' => ['can_manage_system' => true] // Simplified admin perm
    ];
    $storage->insert('users', $admin);

    // Names for random generation
    $lastNames = ['Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Смирнов', 'Попов', 'Васильев', 'Соколов', 'Михайлов', 'Новиков', 'Федоров', 'Морозов', 'Волков', 'Алексеев', 'Лебедев'];
    $firstNames = ['Александр', 'Сергей', 'Дмитрий', 'Андрей', 'Алексей', 'Максим', 'Евгений', 'Иван', 'Михаил', 'Артем', 'Николай', 'Владимир', 'Денис', 'Павел', 'Игорь'];

    // 2. Create 10 Departments and 10 Managers
    $deptNames = [
        'IT-департамент', 'Хозяйственный отдел', 'Энергослужба', 'Сантехнический участок',
        'Транспортный цех', 'Охрана и безопасность', 'Клининговая служба', 'Ремонтно-строительный отдел',
        'Отдел логистики', 'Мебельная мастерская'
    ];

    $departmentIds = [];
    $managers = [];
    $executors = [];
    $requesters = [];

    $storage->transactional('users', function(&$items) use ($admin, $lastNames, $firstNames, $deptNames, &$departmentIds, &$managers, &$executors, &$requesters) {
        $items[] = $admin;

        foreach ($deptNames as $i => $dName) {
            $mId = "manager_" . ($i + 1);
            $dId = "dept_" . ($i + 1);
            $fullName = $lastNames[array_rand($lastNames)] . ' ' . $firstNames[array_rand($firstNames)];
            $items[] = [
                'id' => $mId,
                'login' => "mgr" . ($i + 1),
                'password_hash' => password_hash('manager123', PASSWORD_DEFAULT),
                'full_name' => $fullName . " (Начальник)",
                'role' => 'manager',
                'department_id' => $dId,
                'is_active' => 1,
                'created_at' => date('c'),
                'permissions' => ['can_view_department' => true, 'can_assign' => true, 'can_status' => true]
            ];
            $managers[] = $mId;
            $departmentIds[] = $dId;
        }

        for ($i = 1; $i <= 30; $i++) {
            $eId = "executor_" . $i;
            $dId = $departmentIds[($i - 1) % 10];
            $fullName = $lastNames[array_rand($lastNames)] . ' ' . $firstNames[array_rand($firstNames)];
            $items[] = [
                'id' => $eId,
                'login' => "exec" . $i,
                'password_hash' => password_hash('exec123', PASSWORD_DEFAULT),
                'full_name' => $fullName . " (Исполнитель)",
                'role' => 'executor',
                'department_id' => $dId,
                'is_active' => 1,
                'created_at' => date('c'),
                'permissions' => ['can_status' => true, 'can_comment' => true]
            ];
            $executors[] = $eId;
        }

        for ($i = 1; $i <= 50; $i++) {
            $rId = "user_" . $i;
            $fullName = $lastNames[array_rand($lastNames)] . ' ' . $firstNames[array_rand($firstNames)];
            $items[] = [
                'id' => $rId,
                'login' => "user" . $i,
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'role' => 'user',
                'is_active' => 1,
                'created_at' => date('c'),
                'permissions' => ['can_status' => true]
            ];
            $requesters[] = $rId;
        }
    });

    $storage->transactional('departments', function(&$items) use ($deptNames, $managers) {
        foreach ($deptNames as $i => $dName) {
            $items[] = [
                'id' => "dept_" . ($i + 1),
                'name' => $dName,
                'manager_id' => $managers[$i],
                'description' => "Демонстрационный отдел: $dName"
            ];
        }
    });

    // 5. Create 2-3 Work Types per Department
    $workTypeTemplates = [
        'dept_1' => ['Настройка ПК', 'Проблемы с сетью', 'Установка ПО'],
        'dept_2' => ['Замена замка', 'Починка мебели', 'Ремонт дверей'],
        'dept_3' => ['Замена ламп', 'Розетки/Выключатели', 'Ремонт щитка'],
        'dept_4' => ['Течь смесителя', 'Засор канализации', 'Установка фильтра'],
        'dept_5' => ['Доставка груза', 'Организация переезда', 'Погрузочные работы'],
        'dept_6' => ['Выдача пропуска', 'Проверка датчиков', 'Доступ в помещение'],
        'dept_7' => ['Генеральная уборка', 'Вывоз мусора', 'Мытье окон'],
        'dept_8' => ['Покраска стен', 'Укладка линолеума', 'Шпаклевка'],
        'dept_9' => ['Приемка товара', 'Отгрузка со склада', 'Инвентаризация'],
        'dept_10' => ['Сборка стола', 'Перетяжка кресла', 'Ремонт тумбочки']
    ];

    $allWorkTypes = [];
    $storage->transactional('work_types', function(&$items) use ($workTypeTemplates, &$allWorkTypes) {
        foreach ($workTypeTemplates as $dId => $names) {
            foreach ($names as $idx => $name) {
                $wtId = "wt_" . $dId . "_" . $idx;
                $items[] = [
                    'id' => $wtId,
                    'name' => $name,
                    'department_id' => $dId,
                    'sla_hours' => rand(4, 48),
                    'description' => "Вид работ: $name"
                ];
                $allWorkTypes[] = ['id' => $wtId, 'dept_id' => $dId];
            }
        }
    });

    // 6. Generate 300 Global Chat Messages
    $chatPhrases = [
        'Коллеги, кто свободен по IT?', 'Принял заявку по сантехнике.', 'Нужна помощь в корпусе А.',
        'Заявка ХОП-001 готова.', 'Где найти ключи от склада?', 'Смена началась.', 'Всем продуктивного дня!',
        'Запчасти приехали для лифта.', 'Уточните время прибытия.', 'Проблема решена.', 'Передал смену.',
        'Нужно больше информации по заявке.', 'Кто ответственный за 2 этаж?', 'Проверьте почту.', 'ОК, принято.'
    ];

    $allUserIds = array_merge([$admin['id']], $managers, $executors);
    $storage->transactional('global_chat', function(&$items) use ($allUserIds, $chatPhrases, $storage) {
        for ($i = 0; $i < 300; $i++) {
            $uId = $allUserIds[array_rand($allUserIds)];
            $u = $storage->findOne('users', ['id' => $uId]);
            $items[] = [
                'user_id' => $uId,
                'user_name' => $u['full_name'],
                'message' => $chatPhrases[array_rand($chatPhrases)] . " (#$i)",
                'created_at' => date('c', strtotime("-" . rand(0, 60) . " days - " . rand(0, 23) . " hours"))
            ];
        }
        usort($items, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));
    });

    // 7. Generate 500 Requests
    $locations = ['Корпус А, 1 эт', 'Корпус Б, 3 эт', 'Цех №2', 'Ресепшн', 'Склад №4', 'Серверная', 'Столовая', 'Конференц-зал', 'Гараж', 'Проходная'];
    $priorities = ['normal', 'high', 'low'];
    $statuses = ['new', 'assigned', 'in_progress', 'completed', 'closed', 'rejected'];

    $count = 0;
    for ($i = 1; $i <= 500; $i++) {
        $wt = $allWorkTypes[array_rand($allWorkTypes)];
        $requesterId = $requesters[array_rand($requesters)];

        // Find executors for this department
        $deptExecutors = array_filter($executors, function($eId) use ($storage, $wt) {
            // This is slightly inefficient but okay for a one-time seed
            $u = $storage->findOne('users', ['id' => $eId]);
            return $u && $u['department_id'] === $wt['dept_id'];
        });

        $status = $statuses[array_rand($statuses)];
        $assignedTo = ($status !== 'new' && !empty($deptExecutors)) ? $deptExecutors[array_rand($deptExecutors)] : null;

        $createdAt = date('c', strtotime("-" . rand(1, 60) . " days"));

        $request = [
            'id' => 'req_' . $i,
            'number' => 'ХОП-' . date('Ymd', strtotime($createdAt)) . '-' . sprintf('%04d', $i),
            'requester_id' => $requesterId,
            'work_type_id' => $wt['id'],
            'department_id' => $wt['dept_id'],
            'assigned_to' => $assignedTo,
            'priority' => $priorities[array_rand($priorities)],
            'location' => $locations[array_rand($locations)] . ", каб. " . rand(1, 50),
            'description' => "Демонстрационная заявка №$i. Проблема с " . mb_strtolower($storage->findOne('work_types', ['id' => $wt['id']])['name']),
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => date('c', strtotime($createdAt . " + " . rand(1, 48) . " hours")),
            'deadline_at' => date('c', strtotime($createdAt . " + 24 hours"))
        ];

        $storage->insert('requests', $request);

        // Add some history for 30% of requests
        if (rand(1, 100) > 70) {
            $storage->insert('status_history', [
                'id' => 'hist_' . $i,
                'request_id' => 'req_' . $i,
                'status' => $status,
                'changed_by' => $assignedTo ?: $admin['id'],
                'changed_at' => date('c'),
                'comment' => 'Системная генерация демо-данных'
            ]);
        }

        $count++;
    }

    // Final check for system settings
    $storage->transactional('settings', function(&$items) {
        $found = false;
        foreach ($items as &$item) {
            if ($item['id'] == 'global') {
                $item['auth_enabled'] = true;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = ['id' => 'global', 'auth_enabled' => true, 'org_name' => 'HOP CRM Demo'];
        }
    });

    echo json_encode(['success' => true, 'count' => $count, 'message' => "Создано 500 заявок, 10 отделов, 10 руководителей, 30 исполнителей"]);
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
