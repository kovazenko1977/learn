<?php
session_start();
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: application/json');

if (!isset($_SESSION['authenticated'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'change_pin') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newPin = $data['new_pin'] ?? '';

    if (strlen($newPin) !== 6 || !is_numeric($newPin)) {
        http_response_code(400);
        echo json_encode(['error' => 'Пароль должен состоять из 6 цифр']);
        exit;
    }

    $settings = Storage::getSettings();
    $settings['pin_hash'] = password_hash($newPin, PASSWORD_DEFAULT);

    if (Storage::write('settings.json', $settings)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при сохранении']);
    }
    exit;
}
