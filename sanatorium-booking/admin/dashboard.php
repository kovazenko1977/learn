<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');

// Create room map for easy lookup
$roomMap = [];
foreach ($rooms as $r) { $roomMap[$r['id']] = $r['room_number']; }
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Админ-панель</title><link rel="stylesheet" href="../public/assets/css/admin.css"></head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="rooms.php">Номера</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
        </nav>
    </header>
    <main>
        <h2>📋 Список заявок</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Гость</th>
                    <th>Заезд</th>
                    <th>Выезд</th>
                    <th>Номер</th>
                    <th>Телефон</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo $booking['id']; ?></td>
                    <td><?php echo htmlspecialchars($booking['client_name'] ?? ''); ?></td>
                    <td><?php echo $booking['check_in']; ?></td>
                    <td><?php echo $booking['check_out']; ?></td>
                    <td><?php echo $roomMap[$booking['room_id']] ?? "ID ".$booking['room_id']; ?></td>
                    <td><?php echo $booking['phone']; ?></td>
                    <td><?php echo $booking['status']; ?></td>
                    <td><?php echo number_format($booking['total_price'], 0, ',', ' '); ?> руб.</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
