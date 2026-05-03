<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');
$user = Auth::check();

if (!$user) {
    http_response_code(401);
    exit(json_encode(['message' => 'Unauthorized']));
}

$perms = $user['permissions'] ?? [];
if ($user['role'] !== 'admin' && !($perms['can_access_chat'] ?? true)) {
    http_response_code(403);
    exit(json_encode(['message' => 'Forbidden']));
}

$action = $_GET['action'] ?? '';

if ($action == 'list') {
    $messages = $storage->readCollection('global_chat');
    // Keep last 100
    if (count($messages) > 100) $messages = array_slice($messages, -100);
    echo json_encode(array_values($messages));
} elseif ($action == 'send' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['message'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Empty message']));
    }

    $msg = [
        'user_id' => $user['id'],
        'user_name' => $user['full_name'],
        'message' => (string)$data['message'],
        'created_at' => date('c')
    ];

    echo json_encode($storage->insert('global_chat', $msg));
} else {
    http_response_code(404);
}
