<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (Auth::login($data['username'] ?? '', $data['password'] ?? '')) {
        echo json_encode(['success' => true, 'user' => Auth::user()]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    exit;
}

if ($action === 'logout') {
    Auth::logout();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'status') {
    echo json_encode(['logged_in' => Auth::check(), 'user' => Auth::user()]);
    exit;
}
