<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, X-API-Token");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../core/autoload.php';

use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Rooms\RoomManager;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Procedures\ProcedureManager;
use Sanatorium\Core\Packages\PackageManager;
use Sanatorium\Core\Services\ServiceManager;
use Sanatorium\Core\Users\UserManager;
use Sanatorium\Core\Analytics\AnalyticsManager;

$dataDir = __DIR__ . '/../data';
$store = new JsonStore($dataDir);
$userManager = new UserManager($store);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

// Helper to get user from token
$apiToken = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['api_token'] ?? '';
$currentUser = $apiToken ? $userManager->getUserByToken($apiToken) : null;

// Auth endpoints don't need token
if ($action === 'auth/login' && $method === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true);
    $user = $userManager->authenticate($postData['username'] ?? '', $postData['password'] ?? '');
    if ($user) {
        echo json_encode([
            'success' => true,
            'token' => $user['api_token'],
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'permissions' => $user['permissions'] ?? []
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }
    exit;
}

// Protected endpoints check
function requireAuth($user) {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

switch ($action) {
    case 'auth/me':
        requireAuth($currentUser);
        echo json_encode($currentUser);
        break;

    case 'dashboard/stats':
        requireAuth($currentUser);
        $analytics = new AnalyticsManager($store);
        echo json_encode($analytics->getBasicStats());
        break;

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
        echo json_encode(['total_price' => $bookingManager->calculatePrice($postData ?? [])]);
        break;

    case 'booking/create':
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        $bookingId = $bookingManager->createBooking($postData ?? []);
        echo json_encode(['success' => (bool)$bookingId, 'booking_id' => $bookingId]);
        break;

    case 'booking/list':
        requireAuth($currentUser);
        echo json_encode($store->findAll('bookings'));
        break;

    case 'booking/cancel':
        requireAuth($currentUser);
        $bookingManager = new BookingManager($store);
        $postData = json_decode(file_get_contents('php://input'), true);
        $bookingId = $postData['booking_id'] ?? $_GET['booking_id'] ?? null;
        $success = $bookingId ? $bookingManager->cancelBooking($bookingId) : false;
        echo json_encode(['success' => $success]);
        break;

    case 'sauna/slots':
        $roomId = (int)($_GET['room_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');
        $bookingManager = new BookingManager($store);
        $slots = [];
        for($h = 8; $h < 24; $h++) {
            $time = sprintf('%02d:00', $h);
            $start = "$date $time:00";
            $end = "$date " . sprintf('%02d:00', $h+1) . ":00";
            $busy = !$bookingManager->isAvailable($roomId, $start, $end);
            $slots[] = ['time' => $time, 'busy' => $busy];
        }
        echo json_encode(['slots' => $slots]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}
