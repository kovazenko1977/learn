<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $passcode = $input['passcode'] ?? '';

    // Default passcode is '123456' - could be changed in settings.json
    if ($passcode === '123456') {
        $_SESSION['authenticated'] = true;
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid passcode']);
    }
} elseif ($method === 'GET' && isset($_GET['logout'])) {
    session_destroy();
    echo json_encode(['status' => 'success']);
} elseif ($method === 'GET') {
    echo json_encode(['authenticated' => is_authenticated()]);
}
