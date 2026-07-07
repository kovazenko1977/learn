<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';
require_once __DIR__ . '/../includes/Logger.php';

$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$storage = new Storage($settings);
$tokenProvider = new TokenProvider();
$logger = new Logger();

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);
$userData = $tokenProvider->validateToken($token);

if (!$userData || $userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$backupDir = __DIR__ . '/../data/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    file_put_contents($backupDir . '/.htaccess', "Deny from all");
}

$data = [
    'settings' => $settings,
    'tasks' => $storage->getTasks(),
    'users' => $storage->getUsers(),
    'comments' => $storage->getComments()
];

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($backupDir . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$logger->log($userData['id'], "System Backup", "Backup created: $filename");

echo json_encode(['success' => true, 'filename' => $filename]);
