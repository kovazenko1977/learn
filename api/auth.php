<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$storage = new Storage(__DIR__ . '/../data');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($action == 'login' && $method == 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    // Debug logging (temporary)
    $debug = [
        'timestamp' => date('c'),
        'raw' => $rawInput,
        'decoded' => $data
    ];

    if (empty($data['login']) || empty($data['password'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Missing credentials', 'debug' => $debug]));
    }

    $result = Auth::login($data['login'], $data['password'], $storage);

    if ($result) {
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials', 'input_received' => $data['login']]);
    }
} elseif ($action == 'me') {
    $user = Auth::check();
    if ($user) {
        $userData = $storage->findOne('users', ['id' => $user['id']]);
        if ($userData) {
            unset($userData['password_hash']);
            echo json_encode($userData);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
    }
} else {
    http_response_code(404);
    echo json_encode(['message' => 'Action not found or Method not allowed']);
}
