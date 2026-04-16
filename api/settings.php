<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newPin = $data['new_pin'] ?? '';

    if (preg_match('/^\d{6}$/', $newPin)) {
        $settings = Storage::read('settings.json');
        $settings['pin_hash'] = password_hash($newPin, PASSWORD_DEFAULT);
        if (Storage::write('settings.json', $settings)) {
            echo json_encode(['success' => true]);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => 'Failed to save settings']);
        }
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'ПИН-код должен состоять из 6 цифр']);
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
}
