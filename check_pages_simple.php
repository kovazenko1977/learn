<?php
session_start();
$_SESSION['admin_logged_in'] = true;
require_once 'sanatorium-booking/core/autoload.php';

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
    echo "\n--- Checking $page ---\n";
    $_SERVER['PHP_SELF'] = basename($page);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include $page;
}
