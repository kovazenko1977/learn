<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/TokenProvider.php';

$currentUser = TokenProvider::getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($currentUser['role'] !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$settingsFile = __DIR__ . '/../data/settings.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_get_contents($settingsFile);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');
    // Basic validation
    if (json_decode($data)) {
        file_put_contents($settingsFile, $data);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    }
}
