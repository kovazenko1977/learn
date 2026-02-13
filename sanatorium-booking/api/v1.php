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
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'rooms/available':
        $roomManager = new RoomManager($store);
        echo json_encode($roomManager->getAvailableRooms($_GET['check_in'] ?? '', $_GET['check_out'] ?? '', (int)($_GET['persons'] ?? 0)));
        break;
    case 'procedures':
        echo json_encode((new ProcedureManager($store))->getAll());
        break;
    case 'packages':
        echo json_encode((new PackageManager($store))->getAll());
        break;
    case 'services':
        echo json_encode((new ServiceManager($store))->getAll());
        break;
    case 'calculate':
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['total_price' => $bookingManager->calculatePrice($postData)]);
        break;
    case 'booking/create':
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        $bookingId = $bookingManager->createBooking($postData);
        echo json_encode(['success' => (bool)$bookingId, 'booking_id' => $bookingId]);
        break;
    case 'booking/cancel':
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        $bookingId = $postData['booking_id'] ?? $_GET['booking_id'] ?? null;
        $success = $bookingId ? $bookingManager->cancelBooking($bookingId) : false;
        echo json_encode(['success' => $success]);
        break;
    default:
        echo json_encode(['error' => 'Not found']);
}
