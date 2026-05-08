<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/storage.php';

$config_file = __DIR__ . '/../data/config.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Config file missing']);
    exit;
}
$config = loadData($config_file);

$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $pin = $data['pin'] ?? '';
    if ($pin === $config['password']) {
        $_SESSION['logged_in'] = true;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid PIN']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check') {
    echo json_encode(['logged_in' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
