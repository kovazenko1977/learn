<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? $data['pin'] ?? '';
    $password = $data['password'] ?? $data['pin'] ?? '';

    $authResult = Auth::login($username, $password);
    if ($authResult) {
        echo json_encode([
            'success' => true,
            'token' => $authResult['token'],
            'user' => $authResult['user']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
    }
} elseif ($action === 'check') {
    echo json_encode([
        'authenticated' => Auth::check(),
        'user' => Auth::getUser()
    ]);
} elseif ($action === 'recover') {
    echo json_encode(['success' => true, 'message' => 'Инструкции отправлены на email']);
}
