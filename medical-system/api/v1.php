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

if ($action === 'get_settings') {
    $settingsStore = new \Medical\Core\JsonStore('settings');
    $s = $settingsStore->getAll();

    // Send only necessary UI settings
    $out = [
        'title' => $s['widget_title'] ?? 'Онлайн-бронирование',
        'success_msg' => $s['widget_success_msg'] ?? 'Заявка принята!',
        'show_email' => $s['widget_show_email'] ?? false,
        'show_birthdate' => $s['widget_show_birthdate'] ?? false,
        'show_guests' => $s['widget_show_guests'] ?? false,
        'show_notes' => $s['widget_show_notes'] ?? false,
        'show_packages' => $s['widget_show_packages'] ?? false,
        'primary_color' => $s['widget_primary_color'] ?? '#0078d4'
    ];

    if ($out['show_packages']) {
        $pm = new \Medical\Core\Managers\PackageManager();
        $out['packages'] = array_map(function($p) {
            return ['id' => $p['id'], 'name' => $p['name']];
        }, $pm->getAll());
    }

    echo json_encode($out);
    exit;

} elseif ($action === 'check_availability') {
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
            'guest_birth_date' => $data['birthdate'] ?? '',
            'guest_email' => $data['email'] ?? '',
            'check_in' => $data['checkin'],
            'check_out' => $data['checkout'],
            'num_guests' => $data['guests'] ?? 1,
            'package_id' => $data['package_id'] ?? '',
            'status' => 'preliminary',
            'comment' => 'С сайта' . (!empty($data['notes']) ? ': ' . $data['notes'] : '')
        ]);
        echo json_encode(['success' => true, 'booking_id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Room is already booked']);
    }
} else {
    echo json_encode(['error' => 'Unknown action']);
}
