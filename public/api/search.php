<?php
require_once __DIR__ . '/../../src/autoload.php';
use App\Database\JsonStore;

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
    exit(json_encode([]));
}

$store = new JsonStore(__DIR__ . '/../../data');
$results = [];

$guests = $store->findAll('guests');
foreach ($guests as $g) {
    if (stripos($g['name'] ?? '', $q) !== false || stripos($g['phone'] ?? '', $q) !== false) {
        $results[] = [
            'type' => 'guest',
            'title' => $g['name'],
            'subtitle' => 'Гость: ' . ($g['phone'] ?? ''),
            'url' => 'guests.php?id=' . $g['id']
        ];
    }
}

$bookings = $store->findAll('bookings');
foreach ($bookings as $b) {
    if (stripos($b['client_name'] ?? '', $q) !== false || stripos($b['phone'] ?? '', $q) !== false || ($b['id'] == $q)) {
        $results[] = [
            'type' => 'booking',
            'title' => 'Бронь #' . $b['id'] . ': ' . ($b['client_name'] ?? 'Без имени'),
            'subtitle' => ($b['check_in'] ?? '') . ' - ' . ($b['check_out'] ?? ''),
            'url' => 'edit_booking.php?id=' . $b['id']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode(array_slice($results, 0, 10));
