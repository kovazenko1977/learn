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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель Санатория</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="rooms.php">Номера</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    <main>
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Список заявок</h2>
                <a href="export_csv.php" class="btn">Экспорт в CSV</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата заезда</th>
                        <th>Дата выезда</th>
                        <th>Номер</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_id']); ?></td>
                        <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                        <td><?php echo htmlspecialchars($booking['status']); ?></td>
                        <td><?php echo htmlspecialchars($booking['total_price']); ?> руб.</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
