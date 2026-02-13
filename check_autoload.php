<?php
try {
    require_once 'sanatorium-booking/core/autoload.php';
    $store = new Sanatorium\Core\Database\JsonStore('sanatorium-booking/data');
    $bm = new Sanatorium\Core\Booking\BookingManager($store);
    $am = new Sanatorium\Core\Analytics\AnalyticsManager($store);
    $cm = new Sanatorium\Core\Calendar\CalendarManager($store);
    $rm = new Sanatorium\Core\Rooms\RoomManager($store);
    $gm = new Sanatorium\Core\Guests\GuestManager($store);
    echo "Autoload and instantiation: OK\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
