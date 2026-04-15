<?php
session_start();
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';

    $settings = Storage::getSettings();
    if ($pin && password_verify($pin, $settings['pin_hash'])) {
        $_SESSION['authenticated'] = true;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Неверный пароль']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check') {
    echo json_encode(['authenticated' => isset($_SESSION['authenticated'])]);
    exit;
}
