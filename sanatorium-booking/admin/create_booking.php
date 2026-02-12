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
$availableRooms = [];
$searchDone = false;

$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$classFilter = $_GET['room_class'] ?? '';
$persons = (int)($_GET['persons'] ?? 1);

if ($checkIn && $checkOut) {
    $rooms = $roomManager->getAvailableRooms($checkIn, $checkOut, $persons);
    foreach ($rooms as $room) {
        if ($classFilter && $room['room_class'] !== $classFilter) {
            continue;
        }
        $availableRooms[] = $room;
    }
    $searchDone = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    $bookingData = [
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['check_in'],
        'check_out' => $_POST['check_out'],
        'persons' => (int)$_POST['persons'],
        'phone' => $_POST['phone'],
        'status' => 'confirmed', // Admin bookings are confirmed by default
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
    <title>Создание бронирования</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .search-box { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .room-card { background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ddd; }
        .room-card h4 { margin-top: 0; }
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
        <h2>Административное бронирование</h2>

        <div class="search-box">
            <form method="get">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; align-items: end;">
                    <div>
                        <label>Дата заезда</label>
                        <input type="date" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Дата выезда</label>
                        <input type="date" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Класс номера</label>
                        <select name="room_class" style="width:100%; padding:8px;">
                            <option value="">Все классы</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $classFilter === $c['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Человек</label>
                        <input type="number" name="persons" value="<?php echo $persons; ?>" min="1" style="width:100%; padding:8px;">
                    </div>
                    <div style="grid-column: span 4; text-align: right;">
                        <button type="submit" class="btn">Поиск свободных номеров</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($searchDone): ?>
            <h3>Доступные номера</h3>
            <?php if (empty($availableRooms)): ?>
                <p>Нет подходящих свободных номеров.</p>
            <?php else: ?>
                <div class="results-grid">
                    <?php foreach ($availableRooms as $room): ?>
                        <div class="room-card">
                            <h4>Номер <?php echo htmlspecialchars($room['room_number']); ?></h4>
                            <p>Класс: <?php echo htmlspecialchars($room['room_class']); ?></p>
                            <p>Вместимость: <?php echo htmlspecialchars($room['capacity']); ?> чел.</p>
                            <p>Цена: <strong><?php echo htmlspecialchars($room['price_per_day']); ?> руб/сут</strong></p>

                            <hr>
                            <form method="post">
                                <input type="hidden" name="action" value="create_booking">
                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>">
                                <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>">
                                <input type="hidden" name="persons" value="<?php echo $persons; ?>">

                                <label>ФИО Клиента</label>
                                <input type="text" name="client_name" required style="width:100%; padding:5px; margin-bottom:10px;">

                                <label>Телефон</label>
                                <input type="tel" name="phone" required style="width:100%; padding:5px; margin-bottom:10px;">

                                <button type="submit" class="btn" style="width:100%; background:#007bff;">Забронировать</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
