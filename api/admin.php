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
$user = Auth::check();
if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';

if ($action == 'users') {
    $users = $storage->readCollection('users');
    foreach ($users as &$u) unset($u['password_hash']);
    echo json_encode($users);
} elseif ($action == 'create_user' && $_SERVER['REQUEST_METHOD'] == 'POST' && $user['role'] == 'admin') {
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
        'created_at' => date('c')
    ];

    $saved = $storage->insert('users', $newUser);
    unset($saved['password_hash']);
    echo json_encode($saved);
} elseif ($action == 'worktypes') {
    echo json_encode($storage->readCollection('work_types'));
} elseif ($action == 'departments') {
    echo json_encode($storage->readCollection('departments'));
} elseif ($action == 'backup' && $user['role'] == 'admin') {
    if (!class_exists('ZipArchive')) {
        exit(json_encode(['message' => 'ZipArchive not available']));
    }
    $zip = new ZipArchive();
    $filename = "backup_" . date('Ymd_His') . ".zip";
    $filepath = __DIR__ . "/../data/" . $filename;
    if ($zip->open($filepath, ZipArchive::CREATE)!==TRUE) {
        exit(json_encode(['message' => 'Cannot create zip']));
    }
    $files = glob(__DIR__ . '/../data/*.json');
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
    echo json_encode(['message' => 'Backup created', 'file' => $filename]);
} elseif ($action == 'restore' && $user['role'] == 'admin') {
    $file = basename($_GET['file']);
    $filepath = __DIR__ . "/../data/" . $file;
    if (!file_exists($filepath)) {
        exit(json_encode(['message' => 'Backup file not found']));
    }
    $zip = new ZipArchive;
    if ($zip->open($filepath) === TRUE) {
        $zip->extractTo(__DIR__ . '/../data/');
        $zip->close();
        echo json_encode(['message' => 'Restore complete']);
    } else {
        echo json_encode(['message' => 'Restore failed']);
    }
} else {
    // For non-admin, only allow lookups
    if ($action == 'worktypes' || $action == 'departments') {
         echo json_encode($storage->readCollection($action == 'worktypes' ? 'work_types' : 'departments'));
    } else {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden']);
    }
}
