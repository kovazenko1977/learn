<?php
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = $data['code'] ?? '';
    if (Auth::login($code)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid code']);
    }
} elseif ($action === 'logout') {
    Auth::logout();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    echo json_encode(['authenticated' => Auth::check()]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
