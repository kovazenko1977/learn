<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Rooms\RoomManager;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$roomManager = new RoomManager($store);
$bookingManager = new BookingManager($store);

$classes = $store->findAll('room_classes');
$allRooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');

$checkIn = $_GET['check_in'] ?? date('Y-m-d');
$checkOut = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$persons = (int)($_GET['persons'] ?? 1);

// Calculate status for each room in the selected range
$roomStatuses = [];
foreach ($allRooms as $room) {
    $status = 'free'; // Default
    $bookingId = null;

    foreach ($calendar as $entry) {
        if ($entry['room_id'] == $room['id']) {
            $entryDate = strtotime($entry['date']);
            $start = strtotime($checkIn);
            $end = strtotime($checkOut);

            if ($entryDate >= $start && $entryDate < $end) {
                if ($entry['status'] === 'booked') {
                    $status = 'booked';
                    $bookingId = $entry['booking_id'];
                    break;
                } elseif ($entry['status'] === 'reserved' && $status !== 'booked') {
                    $status = 'reserved';
                }
            }
        }
    }
    $roomStatuses[$room['id']] = [
        'status' => $status,
        'booking_id' => $bookingId
    ];
}

// Group rooms by class
$groupedRooms = [];
foreach ($allRooms as $room) {
    $groupedRooms[$room['room_class']][] = $room;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    $bookingData = [
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['check_in'],
        'check_out' => $_POST['check_out'],
        'persons' => (int)$_POST['persons'],
        'phone' => $_POST['phone'],
        'status' => 'confirmed',
        'client_name' => $_POST['client_name']
    ];
    $bookingId = $bookingManager->createBooking($bookingData);
    if ($bookingId) {
        header('Location: dashboard.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Интерактивное бронирование</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .status-board { margin-top: 20px; }
        .class-group { margin-bottom: 30px; }
        .class-title { font-size: 1.2rem; color: #2c3e50; border-bottom: 2px solid #3498db; display: inline-block; margin-bottom: 15px; padding-bottom: 5px; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }

        .room-visual { padding: 15px; border-radius: 10px; text-align: center; transition: all 0.3s ease; position: relative; border: 2px solid transparent; cursor: pointer; }
        .room-visual.free { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .room-visual.booked { background: #ffebee; color: #c62828; border-color: #ffcdd2; cursor: not-allowed; opacity: 0.8; }
        .room-visual.reserved { background: #fff3e0; color: #ef6c00; border-color: #ffe0b2; }

        .room-visual:hover.free { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(46, 125, 50, 0.2); border-color: #2e7d32; }
        .room-visual.selected { border-color: #1a73e8; background: #e8f0fe; color: #1a73e8; transform: scale(1.05); z-index: 10; }

        .room-num { font-size: 1.4rem; font-weight: bold; display: block; }
        .room-info { font-size: 0.85rem; }
        .room-status-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; display: block; }

        #booking-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; width: 400px; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .btn-blue { background: #1a73e8; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-cancel { background: #ccc; color: #333; margin-right: 10px; }
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
            <h2>Визуальный выбор номеров</h2>
            <form method="get" id="filter-form">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div>
                        <label>Дата заезда</label>
                        <input type="date" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" onchange="this.form.submit()">
                    </div>
                    <div>
                        <label>Дата выезда</label>
                        <input type="date" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" onchange="this.form.submit()">
                    </div>
                    <div>
                        <label>Кол-во человек</label>
                        <input type="number" name="persons" value="<?php echo $persons; ?>" min="1" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>

        <div class="status-board">
            <?php foreach ($groupedRooms as $className => $rooms): ?>
                <div class="class-group">
                    <h3 class="class-title"><?php echo htmlspecialchars($className); ?></h3>
                    <div class="rooms-grid">
                        <?php foreach ($rooms as $room):
                            $info = $roomStatuses[$room['id']];
                            $status = $info['status'];
                            $isTooSmall = ($persons > 0 && $room['capacity'] < $persons);
                            if ($isTooSmall && $status === 'free') {
                                $statusLabel = 'Мало места';
                                $statusClass = 'reserved';
                            } else {
                                $statusLabels = ['free' => 'Свободен', 'booked' => 'Занят', 'reserved' => 'Резерв'];
                                $statusLabel = $statusLabels[$status];
                                $statusClass = $status;
                            }
                        ?>
                            <div class="room-visual <?php echo $statusClass; ?>"
                                 onclick="<?php echo ($status === 'free' && !$isTooSmall) ? "openBooking({$room['id']}, '{$room['room_number']}', '{$room['room_class']}')" : ""; ?>">
                                <span class="room-num">№<?php echo htmlspecialchars($room['room_number']); ?></span>
                                <span class="room-info" style="display:block; font-style:italic;"><?php echo htmlspecialchars($room['room_class']); ?></span>
                                <span class="room-info"><?php echo $room['capacity']; ?>-местный</span>
                                <span class="room-status-label"><?php echo $statusLabel; ?></span>
                                <?php if ($status === 'free'): ?>
                                    <div style="margin-top:5px; font-weight:bold;"><?php echo $room['price_per_day']; ?> ₽</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Модальное окно бронирования -->
        <div id="booking-modal">
            <div class="modal-content">
                <h3>Бронирование номера <span id="m-room-num"></span></h3>
                <p id="m-room-class" style="color:#666; margin-bottom:20px;"></p>
                <form method="post">
                    <input type="hidden" name="action" value="create_booking">
                    <input type="hidden" name="room_id" id="m-room-id">
                    <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>">
                    <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>">
                    <input type="hidden" name="persons" value="<?php echo $persons; ?>">

                    <div class="form-group">
                        <label>ФИО Гостя</label>
                        <input type="text" name="client_name" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" required>
                    </div>

                    <div style="margin-top: 30px; text-align: right;">
                        <button type="button" class="btn btn-cancel" onclick="closeBooking()">Отмена</button>
                        <button type="submit" class="btn btn-blue">Подтвердить</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openBooking(id, num, className) {
            document.getElementById('m-room-id').value = id;
            document.getElementById('m-room-num').textContent = num;
            document.getElementById('m-room-class').textContent = className;
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
