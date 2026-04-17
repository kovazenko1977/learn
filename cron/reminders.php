<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Logger.php';

// Logic to send reminders for appointments (24h and 2h before)
$appointments = Storage::list('appointments');
$now = time();

foreach ($appointments as $a) {
    if ($a['status'] !== 'planned') continue;

    $startTime = strtotime($a['date'] . ' ' . $a['time_start']);
    $diff = $startTime - $now;

    if ($diff > 0 && $diff <= 86400 && $diff > 82800) {
        // Send 24h reminder
        Logger::log("Sending 24h reminder for appointment " . $a['id'], "info", "notifications.log");
    }

    if ($diff > 0 && $diff <= 7200 && $diff > 3600) {
        // Send 2h reminder
        Logger::log("Sending 2h reminder for appointment " . $a['id'], "info", "notifications.log");
    }
}
echo "Reminders processed\n";
