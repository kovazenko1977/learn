<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Some settings might be public (e.g. form fields for index.php)
    $settings = Storage::read('settings.json');

    // Filter sensitive info if not admin
    if (!Auth::authenticate() || !Auth::isAdmin()) {
        unset($settings['admin_password']);
        unset($settings['admin_password_hash']);
        unset($settings['JWT_SECRET']);
        unset($settings['telegram_bot_token']);
    }

    echo json_encode($settings);
    exit;
}

if ($method === 'POST') {
    Auth::requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    $settings = Storage::read('settings.json');
    $newSettings = array_merge($settings, $data);
    Storage::write('settings.json', $newSettings);
    echo json_encode(['success' => true]);
    exit;
}
