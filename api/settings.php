<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = Storage::get('settings');
    // Hide sensitive info
    unset($settings['jwt_secret']);
    echo json_encode($settings);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);

    // Merge with existing to keep sensitive info
    $existing = Storage::get('settings');
    $newData = array_merge($existing, $data);

    Storage::set('settings', $newData);
    echo json_encode(['success' => true]);
}
