<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/BookingManager.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['id']) || empty($data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

if (BookingManager::updateStatus($data['id'], $data['status'])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update status']);
}
