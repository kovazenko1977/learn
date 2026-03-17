<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

if (!Auth::check()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$storage = new Storage('settings.json');
$action = $_GET['action'] ?? 'read';

if ($action === 'read') {
    $settings = $storage->read();
    $user = Auth::user();
    $userSettings = $settings[$user['id']] ?? [
        'theme' => 'light',
        'accentColor' => '#3b82f6',
        'font' => 'Segoe UI',
        'fontSize' => '16px'
    ];
    echo json_encode($userSettings);
    exit;
}

if ($action === 'save') {
    $user = Auth::user();
    $newSettings = json_decode(file_get_contents('php://input'), true);

    $settings = $storage->read();
    $settings[$user['id']] = $newSettings;
    $storage->write($settings);
    echo json_encode(['success' => true]);
    exit;
}
