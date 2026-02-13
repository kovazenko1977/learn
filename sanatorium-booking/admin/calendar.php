<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_booking') {
    $bookingData = [
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['date'],
        'check_out' => date('Y-m-d', strtotime($_POST['date'] . ' +1 day')),
        'persons' => 1,
        'phone' => $_POST['phone'],
        'status' => 'confirmed',
        'client_name' => $_POST['client_name']
    ];
    $bookingId = $bookingManager->createBooking($bookingData);
    if ($bookingId) {
        header('Location: calendar.php?success=1');
        exit;
    }
}

$rooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');

$currentMonth = date('m');
$currentYear = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

$monthsRu = [
    '01' => 'Январь', '02' => 'Февраль', '03' => 'Март', '04' => 'Апрель',
    '05' => 'Май', '06' => 'Июнь', '07' => 'Июль', '08' => 'Август',
    '09' => 'Сентябрь', '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
];

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
        .calendar-table th, .calendar-table td { border: 1px solid #ddd; padding: 5px; text-align: center; font-size: 12px; transition: background 0.2s; }
        .status-booked { background: #f8d7da; color: #721c24; }
        .status-reserved { background: #fff3cd; color: #856404; }
        .status-free { background: #d4edda; color: #155724; cursor: pointer; }
        .status-free:hover { background: #c3e6cb; }

        #booking-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; width: 400px; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .btn-blue { background: #1a73e8; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-cancel { background: #ccc; color: #333; margin-right: 10px; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
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
        <div class="mica-card">
        <h2 style="margin-top:0;">📅 Календарь занятости (<?php echo $monthsRu[$currentMonth] . ' ' . $currentYear; ?>)</h2>
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
                            $onclick = ($status === 'free') ? "openQuickBooking({$room['id']}, '{$room['room_number']}', '{$date}')" : "";
                        ?>
                            <td class="<?php echo $class; ?>" title="<?php echo $date; ?>" onclick="<?php echo $onclick; ?>">
                                <?php echo ($status !== 'free' ? 'X' : ''); ?>
                            </td>
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
        </div>

        <!-- Модальное окно быстрого бронирования -->
        <div id="booking-modal">
            <div class="modal-content">
                <h3>Быстрое бронирование</h3>
                <p style="color:#666; margin-bottom:20px;">
                    Номер: <span id="m-room-num" style="font-weight:bold;"></span><br>
                    Дата: <span id="m-date" style="font-weight:bold;"></span>
                </p>
                <form method="post">
                    <input type="hidden" name="action" value="quick_booking">
                    <input type="hidden" name="room_id" id="m-room-id">
                    <input type="hidden" name="date" id="m-input-date">

                    <div class="form-group">
                        <label>ФИО Гостя</label>
                        <input type="text" name="client_name" required style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" required style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn-cancel" onclick="closeBooking()">Отмена</button>
                        <button type="submit" class="btn-blue">Забронировать</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openQuickBooking(id, num, date) {
            document.getElementById('m-room-id').value = id;
            document.getElementById('m-input-date').value = date;
            document.getElementById('m-room-num').textContent = num;
            document.getElementById('m-date').textContent = date;
            document.getElementById('booking-modal').style.display = 'flex';
        }

        function closeBooking() {
            document.getElementById('booking-modal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('booking-modal')) {
                closeBooking();
            }
        }
    </script>
</body>
</html>
