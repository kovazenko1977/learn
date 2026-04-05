<?php
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $access_code = $data['access_code'] ?? '';

    if (strlen($access_code) !== 6 || !ctype_digit($access_code)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid code format. Must be 6 digits.']);
        exit;
    }

    $settings_file = __DIR__ . '/../data/settings.json';
    $settings = ['access_code' => $access_code];

    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);

    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
}
