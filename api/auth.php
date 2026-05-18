<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $pin = $data['pin'] ?? '';
        if (Auth::login($pin)) {
            echo json_encode(['success' => true]);
        } else {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['success' => false, 'error' => 'Неверный ПИН-код']);
        }
        break;

    case 'logout':
        Auth::logout();
        echo json_encode(['success' => true]);
        break;

    case 'check':
        echo json_encode(['authenticated' => Auth::check()]);
        break;

    default:
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid action']);
        break;
}
