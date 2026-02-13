<?php
require_once 'sanatorium-booking/core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Rooms\RoomManager;
use Sanatorium\Core\Guests\GuestManager;
use Sanatorium\Core\Analytics\AnalyticsManager;

$store = new JsonStore('sanatorium-booking/data');

echo "Testing RoomManager...\n";
$rm = new RoomManager($store);
$available = $rm->getAvailableRooms('2026-03-01', '2026-03-05');
echo "Found " . count($available) . " rooms.\n";

echo "Testing BookingManager...\n";
$bm = new BookingManager($store);
$bid = $bm->createBooking([
    'room_id' => 1,
    'check_in' => '2026-03-01',
    'check_out' => '2026-03-05',
    'phone' => '+375291112233',
    'client_name' => 'Petrov Petr'
]);
echo "Booking ID: $bid\n";

echo "Testing GuestManager...\n";
$gm = new GuestManager($store);
$guests = $gm->getAll();
echo "Total guests: " . count($guests) . "\n";

echo "Testing AnalyticsManager...\n";
$am = new AnalyticsManager($store);
$stats = $am->getStats();
echo "Total income: " . $stats['totalIncome'] . "\n";

echo "Done.\n";
