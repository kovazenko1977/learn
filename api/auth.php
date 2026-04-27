<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = Auth::login($data['username'] ?? '', $data['password'] ?? '');
    if ($token) {
        echo json_encode(['success' => true, 'token' => $token]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
    }
    exit;
}

if ($action === 'me') {
    $user = Auth::authenticate();
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'recover') {
    // Simplified recovery
    echo json_encode(['success' => true, 'message' => 'Инструкции отправлены (демо)']);
    exit;
}

http_response_code(404);