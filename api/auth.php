<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');
require_once '../includes/Storage.php';
require_once '../includes/Auth.php';

$storage = new Storage('../data');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($action == 'login' && $method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['login']) || empty($data['password'])) {
        http_response_code(400);
        exit(json_encode(['message' => 'Missing credentials']));
    }
    $result = Auth::login($data['login'], $data['password'], $storage);
    if ($result) {
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
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
}
