<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$rooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');

$currentMonth = date('m');
$currentYear = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Map calendar entries to [room_id][date] = status
$occupancy = [];
foreach ($calendar as $entry) {
    $occupancy[$entry['room_id']][$entry['date']] = $entry['status'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Календарь занятости</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .calendar-grid { overflow-x: auto; }
        .calendar-table { border-collapse: collapse; min-width: 100%; }
        .calendar-table th, .calendar-table td { border: 1px solid #ddd; padding: 5px; text-align: center; font-size: 12px; }
        .status-booked { background: #f8d7da; color: #721c24; }
        .status-reserved { background: #fff3cd; color: #856404; }
        .status-free { background: #d4edda; color: #155724; }
    </style>
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
        <h2>Календарь занятость номеров (<?php echo date('F Y'); ?>)</h2>
        <div class="calendar-grid">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th>Номер</th>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                            <th><?php echo $d; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td style="background:#f8f9fa; font-weight:bold;"><?php echo htmlspecialchars($room['room_number']); ?></td>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++):
                            $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $d);
                            $status = $occupancy[$room['id']][$date] ?? 'free';
                            $class = 'status-' . $status;
                        ?>
                            <td class="<?php echo $class; ?>" title="<?php echo $date; ?>"><?php echo ($status !== 'free' ? 'X' : ''); ?></td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 20px;">
            <span class="status-free" style="padding: 5px 10px; border-radius: 4px;">Свободно</span>
            <span class="status-booked" style="padding: 5px 10px; border-radius: 4px; margin-left: 10px;">Занято</span>
            <span class="status-reserved" style="padding: 5px 10px; border-radius: 4px; margin-left: 10px;">Резерв</span>
        </div>
    </main>
</body>
</html>
