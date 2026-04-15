<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    if (Auth::login($pin)) {
        echo json_encode(['success' => true, 'user' => Auth::getUser()]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
    }
} elseif ($action === 'logout') {
    Auth::logout();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    $user = Auth::getUser();
    echo json_encode(['authenticated' => (bool)$user, 'user' => $user]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
