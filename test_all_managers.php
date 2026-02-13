<?php
require_once 'sanatorium-booking/core/autoload.php';

$managers = [
    'Sanatorium\Core\Guests\GuestManager',
    'Sanatorium\Core\Rooms\RoomManager',
    'Sanatorium\Core\Booking\BookingManager',
    'Sanatorium\Core\Procedures\ProcedureManager',
    'Sanatorium\Core\Packages\PackageManager',
    'Sanatorium\Core\Services\ServiceManager',
    'Sanatorium\Core\Helpers\TextBlockManager'
];

$store = new Sanatorium\Core\Database\JsonStore('sanatorium-booking/data');

foreach ($managers as $m) {
    echo "Testing $m... ";
    try {
        $inst = new $m($store);
        if (method_exists($inst, 'getAll')) {
            $inst->getAll();
        }
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }
}
