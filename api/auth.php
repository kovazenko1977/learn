<?php
require_once __DIR__ . '/../includes/Security.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pin = $data['pin'] ?? '';
    if (Security::login($pin)) {
        Security::log("Login successful");
        echo json_encode(['success' => true]);
    } else {
        Security::log("Login failed: invalid PIN attempt");
        echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
    }
} elseif ($action === 'logout') {
    Security::log("User logout");
    Security::logout();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    echo json_encode(['authenticated' => isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
