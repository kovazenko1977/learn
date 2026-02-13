<?php
session_start();
$_SESSION['admin_logged_in'] = true;
require_once 'sanatorium-booking/core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore('sanatorium-booking/data');

$pages = [
    'sanatorium-booking/admin/dashboard.php',
    'sanatorium-booking/admin/guests.php',
    'sanatorium-booking/admin/analytics.php',
    'sanatorium-booking/admin/calendar.php',
    'sanatorium-booking/admin/rooms.php',
    'sanatorium-booking/admin/room_classes.php',
    'sanatorium-booking/admin/procedures.php',
    'sanatorium-booking/admin/services.php',
    'sanatorium-booking/admin/packages.php',
    'sanatorium-booking/admin/text_blocks.php',
];

foreach ($pages as $page) {
    echo "Checking $page... ";
    ob_start();
    try {
        // Mocking some globals that pages might use
        $_SERVER['PHP_SELF'] = basename($page);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // Intercepting redirects
        include $page;
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAILED: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    ob_end_clean();
}
