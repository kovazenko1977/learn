<?php require_once "auth.php";

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Rooms\RoomManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$roomManager = new RoomManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $bookingManager->createBooking($_POST);
    header('Location: dashboard.php?success=1');
    exit;
}

$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$rooms = ($check_in && $check_out) ? $roomManager->getAvailableRooms($check_in, $check_out) : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><link rel="stylesheet" href="../public/assets/css/admin.css"></head>
<body>
<div class="mica-card">
    <h2>Новое бронирование</h2>
    <form method="get">
        <p>Заезд: <input type="date" name="check_in" value="<?php echo $check_in; ?>"></p>
        <p>Выезд: <input type="date" name="check_out" value="<?php echo $check_out; ?>"></p>
        <button type="submit">Найти номера</button>
    </form>
    <?php if ($rooms): ?>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
        <p>Выберите номер:
        <select name="room_id">
            <?php foreach($rooms as $r): ?>
                <option value="<?php echo $r['id']; ?>"><?php echo $r['room_number']; ?></option>
            <?php endforeach; ?>
        </select></p>
        <p>Телефон: <input type="tel" name="phone" required></p>
        <button type="submit">Забронировать</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
