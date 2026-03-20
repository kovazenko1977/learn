<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/BookingManager.php';
require_once __DIR__ . '/../includes/NotificationManager.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Basic validation
if (empty($data['program_id']) || empty($data['room_id']) || empty($data['guest']['email'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$id = BookingManager::create($data);

if ($id) {
    $booking = BookingManager::getById($id);
    NotificationManager::notifyBookingConfirmed($booking);
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save booking']);
}
