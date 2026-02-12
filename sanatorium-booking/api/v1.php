<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/autoload.php';

use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Rooms\RoomManager;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Procedures\ProcedureManager;
use Sanatorium\Core\Packages\PackageManager;
use Sanatorium\Core\Services\ServiceManager;

$dataDir = __DIR__ . '/../data';
$store = new JsonStore($dataDir);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'rooms/available':
        $roomManager = new RoomManager($store);
        $checkIn = $_GET['check_in'] ?? '';
        $checkOut = $_GET['check_out'] ?? '';
        $persons = (int)($_GET['persons'] ?? 0);

        if (!$checkIn || !$checkOut) {
            echo json_encode(['error' => 'Missing dates']);
            exit;
        }

        echo json_encode($roomManager->getAvailableRooms($checkIn, $checkOut, $persons));
        break;

    case 'procedures':
        $manager = new ProcedureManager($store);
        echo json_encode($manager->getAll());
        break;

    case 'packages':
        $manager = new PackageManager($store);
        echo json_encode($manager->getAll());
        break;

    case 'services':
        $manager = new ServiceManager($store);
        echo json_encode($manager->getAll());
        break;

    case 'calculate':
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        if (!$postData) {
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        echo json_encode(['total_price' => $bookingManager->calculatePrice($postData)]);
        break;

    case 'booking/create':
        if ($method !== 'POST') {
            echo json_encode(['error' => 'POST method required']);
            exit;
        }
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        $bookingId = $bookingManager->createBooking($postData);
        echo json_encode(['success' => (bool)$bookingId, 'booking_id' => $bookingId]);
        break;

    default:
        echo json_encode(['error' => 'Action not found']);
        break;
}
