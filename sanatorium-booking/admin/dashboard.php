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
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✅ Бронирование успешно создано!
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card">
                <span class="stat-label">Всего заявок</span>
                <span class="stat-value"><?php echo count($bookings); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Свободно номеров</span>
                <span class="stat-value"><?php
                    $freeCount = 0;
                    foreach($rooms as $r) if($r['status'] == 'free') $freeCount++;
                    echo $freeCount;
                ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Новые (New)</span>
                <span class="stat-value" style="color: #007bff;"><?php
                    $newCount = 0;
                    foreach($bookings as $b) if($b['status'] == 'new') $newCount++;
                    echo $newCount;
                ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Доход</span>
                <span class="stat-value" style="color: #28a745;"><?php
                    $income = 0;
                    foreach($bookings as $b) if($b['status'] != 'cancelled') $income += $b['total_price'];
                    echo number_format($income, 0, ',', ' ');
                ?> ₽</span>
            </div>
        </div>

        <section class="mica-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin:0;">📋 Список заявок</h2>
                <div>
                    <a href="create_booking.php" class="btn" style="background: #007bff; margin-right: 10px;">✨ Новое бронирование</a>
                    <a href="export_csv.php" class="btn">📥 Экспорт в CSV</a>
                </div>
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
