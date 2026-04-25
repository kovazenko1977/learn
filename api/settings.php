<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'storage_mode' => 'json',
    'form_fields' => [],
    'categories' => ['IT', 'Repair', 'Cleaning'],
    'priorities' => ['low', 'medium', 'high', 'urgent']
];

$storage = new Storage($settings);
$auth = new Auth($storage, $settings);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Unset sensitive data before sending
    $publicSettings = $settings;
    unset($publicSettings['admin_password']);
    unset($publicSettings['admin_password_hash']);
    unset($publicSettings['mysql']);
    unset($publicSettings['JWT_SECRET']);

    echo json_encode($publicSettings);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->requireRole('admin');
    $input = json_decode(file_get_contents('php://input'), true);

    // Merge new settings
    $settings = array_merge($settings, $input);

    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true]);
}
