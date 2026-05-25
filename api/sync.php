<?php
require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../core/autoload.php';

use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$action = $_GET['action'] ?? '';

// Check API Key for remote requests
if ($action === 'push' || $action === 'pull') {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $users = $store->findAll('users');
    $authorized = false;
    foreach ($users as $u) {
        if (!empty($u['api_token']) && $u['api_token'] === $apiKey) {
            $authorized = true;
            break;
        }
    }

    if (!$authorized) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid API Key']);
        exit;
    }
}

header('Content-Type: application/json');

if ($action === 'pull') {
    $collections = ['bookings', 'guests', 'rooms', 'room_classes', 'procedures', 'extra_services', 'packages', 'plans', 'expenses', 'inventory', 'tasks'];
    $payload = [];
    foreach ($collections as $col) {
        $payload[$col] = $store->findAll($col);
    }
    echo json_encode(['success' => true, 'payload' => $payload]);
} elseif ($action === 'push') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && is_array($data)) {
        foreach ($data as $col => $items) {
            if (is_array($items)) {
                file_put_contents(__DIR__ . '/../data/' . $col . '.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        echo json_encode(['success' => true, 'message' => 'Data merged successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data received']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
