<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/storage.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$config_file = __DIR__ . '/../data/config.php';
$config = loadData($config_file);

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    // Don't return the password in GET
    $response = $config;
    unset($response['password']);
    echo json_encode($response);
} elseif ($method === 'POST') {
    if (isset($data['password']) && strlen($data['password']) === 6 && is_numeric($data['password'])) {
        $config['password'] = $data['password'];
    }
    if (isset($data['interval'])) {
        $config['interval'] = (int)$data['interval'];
    }
    saveData($config_file, $config);
    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
