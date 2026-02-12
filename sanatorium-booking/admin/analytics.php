<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');

$totalIncome = 0;
$statusCounts = ['new' => 0, 'confirmed' => 0, 'cancelled' => 0];
$roomPopularity = [];

foreach ($bookings as $b) {
    if ($b['status'] !== 'cancelled') {
        $totalIncome += (float)$b['total_price'];
    }
    $statusCounts[$b['status']] = ($statusCounts[$b['status']] ?? 0) + 1;
    $roomPopularity[$b['room_id']] = ($roomPopularity[$b['room_id']] ?? 0) + 1;
}

arsort($roomPopularity);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="create_booking.php">Новое бронирование</a>
            <a href="rooms.php">Номера</a>
            <a href="room_classes.php">Классы</a>
            <a href="procedures.php">Процедуры</a>
            <a href="services.php">Услуги</a>
            <a href="packages.php">Пакеты</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    <main>
        <h2>Аналитика системы</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
            <div style="background:#fff; padding:20px; border-radius:8px; text-align:center; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3>Общий доход</h3>
                <p style="font-size: 24px; color: #28a745;"><?php echo number_format($totalIncome, 0, ',', ' '); ?> руб.</p>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px; text-align:center; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3>Всего заявок</h3>
                <p style="font-size: 24px;"><?php echo count($bookings); ?></p>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px; text-align:center; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3>Номера в базе</h3>
                <p style="font-size: 24px;"><?php echo count($rooms); ?></p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3>Статусы бронирований</h3>
                <ul>
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <li><strong><?php echo htmlspecialchars($status); ?>:</strong> <?php echo $count; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3>Популярность номеров (ID)</h3>
                <ol>
                    <?php foreach ($roomPopularity as $roomId => $count): ?>
                        <li>Номер ID <?php echo htmlspecialchars($roomId); ?>: <?php echo $count; ?> раз</li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </main>
</body>
</html>
