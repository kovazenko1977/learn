<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = Storage::getSettings();
    unset($settings['jwt_secret']);
    unset($settings['mysql_config']);
    echo json_encode($settings);
    exit;
}

$user = Auth::authenticate();
if (!$user || !Auth::isAdmin()) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $current = Storage::getSettings();

    // Preserve sensitive keys if not provided
    if (!isset($data['jwt_secret']) && isset($current['jwt_secret'])) {
        $data['jwt_secret'] = $current['jwt_secret'];
    }
    if (!isset($data['mysql_config']) && isset($current['mysql_config'])) {
         $data['mysql_config'] = $current['mysql_config'];
    }

    if (isset($data['storage_mode']) && $data['storage_mode'] === 'mysql') {
        // Simple migration if switching to MySQL
        Storage::saveSettings($data);
        Storage::initializeMySQL();
        // Seed users if empty in MySQL
        if (empty(Storage::getData('users'))) {
            Storage::saveData('users', json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true));
            Storage::saveData('tasks', json_decode(file_get_contents(__DIR__ . '/../data/tasks.json'), true));
        }
    } else {
        Storage::saveSettings($data);
    }

    echo json_encode(['success' => true]);
}