<?php
session_start();
require_once __DIR__ . '/../includes/AuthManager.php';
AuthManager::check();
require_once __DIR__ . '/../includes/BookingManager.php';
require_once __DIR__ . '/../includes/SanatoriumManager.php';

$rooms = SanatoriumManager::getRooms();
$bookings = BookingManager::getAll();

$days = 14;
$startDate = date('Y-m-d');
$dates = [];
for($i=0; $i<$days; $i++) {
    $dates[] = date('Y-m-d', strtotime("+$i days"));
}

function isBooked($roomId, $date, $bookings) {
    foreach($bookings as $b) {
        if ($b['room_id'] == $roomId && $b['status'] != 'cancelled') {
            $checkIn = $b['check_in'];
            // Simplified duration for demo
            $checkOut = date('Y-m-d', strtotime($checkIn . " + 14 days"));
            if ($date >= $checkIn && $date < $checkOut) return true;
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Календарь загрузки</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; padding: 20px; }
        .calendar-table { width: 100%; border-collapse: collapse; font-size: 0.8em; }
        .calendar-table th, .calendar-table td { border: 1px solid #ddd; padding: 4px; text-align: center; }
        .booked { background: #ff4d4f; color: white; }
        .free { background: #f6ffed; }
    </style>
</head>
<body>
    <a href="index.php">← Назад в панель</a>
    <h1>Календарь загрузки</h1>

    <div class="card">
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Номер</th>
                    <?php foreach($dates as $d): ?>
                        <th><?= date('d.m', strtotime($d)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <?php foreach($dates as $d): ?>
                        <td class="<?= isBooked($r['id'], $d, $bookings) ? 'booked' : 'free' ?>"></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
