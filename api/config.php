<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

$settings = Storage::read('settings');
$valid_key = $settings['api_key'] ?? 'DEFAULT_SECRET';

$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';

if ($api_key !== $valid_key) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sync_users':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['success' => true, 'synced' => count($data)]);
        break;

    case 'sync_docs':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['success' => true, 'synced' => count($data)]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
