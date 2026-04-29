<?php
header('Content-Type: application/json');
require_once '../includes/Storage.php';
require_once '../includes/Auth.php';

$storage = new Storage('../data');
$user = Auth::check(['admin']);
if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';

if ($action == 'users') {
    $users = $storage->readCollection('users');
    foreach ($users as &$u) unset($u['password_hash']);
    echo json_encode($users);
} elseif ($action == 'worktypes') {
    echo json_encode($storage->readCollection('work_types'));
} elseif ($action == 'departments') {
    echo json_encode($storage->readCollection('departments'));
} elseif ($action == 'backup') {
    if (!class_exists('ZipArchive')) {
        exit(json_encode(['message' => 'ZipArchive not available']));
    }
    $zip = new ZipArchive();
    $filename = "backup_" . date('Ymd_His') . ".zip";
    $filepath = "../data/" . $filename;
    if ($zip->open($filepath, ZipArchive::CREATE)!==TRUE) {
        exit(json_encode(['message' => 'Cannot create zip']));
    }
    $files = glob('../data/*.json');
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
    echo json_encode(['message' => 'Backup created', 'file' => $filename]);
} elseif ($action == 'restore') {
    $file = basename($_GET['file']);
    $filepath = "../data/" . $file;
    if (!file_exists($filepath)) {
        exit(json_encode(['message' => 'Backup file not found']));
    }
    $zip = new ZipArchive;
    if ($zip->open($filepath) === TRUE) {
        $zip->extractTo('../data/');
        $zip->close();
        echo json_encode(['message' => 'Restore complete']);
    } else {
        echo json_encode(['message' => 'Restore failed']);
    }
}
