<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();

$action = $_GET['action'] ?? '';

if ($action === 'check_availability') {
    $in = $_GET['in'] ?? '';
    $out = $_GET['out'] ?? '';

    if (!$in || !$out) {
        echo json_encode(['error' => 'Missing dates']);
        exit;
    }

    $rm = new \Medical\Core\Managers\RoomManager();
    $bm = new \Medical\Core\Managers\BookingManager();

    $rooms = $rm->getAll();
    $available = [];

    foreach ($rooms as $r) {
        if ($bm->isAvailable($r['id'], $in, $out)) {
            $price = $rm->getBasePrice($r['id'], $in); // Simplification: use check-in price
            $available[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'type' => $r['type'],
                'capacity' => $r['capacity'],
                'price' => $price
            ];
        }
    }

    echo json_encode(['rooms' => $available]);

} elseif ($action === 'create_web_booking') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !$data['room_id'] || !$data['name'] || !$data['phone']) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $bm = new \Medical\Core\Managers\BookingManager();
    if ($bm->isAvailable($data['room_id'], $data['checkin'], $data['checkout'])) {
        $id = $bm->create([
            'room_id' => $data['room_id'],
            'guest_name' => $data['name'],
            'guest_phone' => $data['phone'],
            'check_in' => $data['checkin'],
            'check_out' => $data['checkout'],
            'status' => 'preliminary',
            'comment' => 'С сайта'
        ]);
        echo json_encode(['success' => true, 'booking_id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Room is already booked']);
    }
} else {
    echo json_encode(['error' => 'Unknown action']);
}
