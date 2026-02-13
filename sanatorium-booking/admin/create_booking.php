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

$pageTitle = 'Новое бронирование';
include 'includes/header.php';
?>

<div class="mica-card" style="max-width: 600px;">
    <h2>📅 Создание новой заявки</h2>
    <form method="get" style="margin-top: 20px;">
        <div class="grid-2">
            <div>
                <label>Дата заезда</label>
                <input type="date" name="check_in" value="<?php echo $check_in; ?>" required>
            </div>
            <div>
                <label>Дата выезда</label>
                <input type="date" name="check_out" value="<?php echo $check_out; ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-secondary">🔍 Найти свободные номера</button>
    </form>

    <?php if ($_GET && !$rooms && $check_in && $check_out): ?>
        <p style="color: #d83b01; margin-top: 20px;">Нет свободных номеров на выбранные даты.</p>
    <?php endif; ?>

    <?php if ($rooms): ?>
    <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-color);">

    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">

        <label>Доступный номер</label>
        <select name="room_id" required>
            <?php foreach($rooms as $r): ?>
                <option value="<?php echo $r['id']; ?>">Номер <?php echo $r['room_number']; ?> (<?php echo number_format($r['price_per_day'], 0, ',', ' '); ?> ₽/сут)</option>
            <?php endforeach; ?>
        </select>

        <label>Имя гостя</label>
        <input type="text" name="client_name" required placeholder="Иванов Иван">

        <label>Контактный телефон</label>
        <input type="tel" name="phone" required placeholder="+...">

        <label>Количество человек</label>
        <input type="number" name="persons" value="1" min="1" required>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn" style="width: 100%;">✅ Подтвердить бронирование</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
