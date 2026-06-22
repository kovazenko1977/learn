<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';

    if (Auth::login($pin)) {
        echo json_encode(['success' => true, 'user' => Auth::getUser()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Неверный код доступа']);
    }
} elseif ($action === 'logout') {
    Auth::logout();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    echo json_encode([
        'authenticated' => Auth::check(),
        'user' => Auth::getUser()
    ]);
}
