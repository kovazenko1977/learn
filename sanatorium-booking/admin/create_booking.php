<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Rooms\RoomManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$roomManager = new RoomManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $bookingId = $bookingManager->createBooking($_POST);
    if ($bookingId) {
        header('Location: dashboard.php?success=1');
    } else {
        header('Location: create_booking.php?error=overlap&check_in='.$_POST['check_in'].'&check_out='.$_POST['check_out']);
    }
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
    <?php if (isset($_GET['error']) && $_GET['error'] === 'overlap'): ?>
        <div class="error" style="margin-top: 20px; background: rgba(216, 59, 1, 0.1); color: #d83b01; padding: 10px; border-radius: 6px; border: 1px solid rgba(216, 59, 1, 0.2);">
            ⚠️ Ошибка: Этот номер уже забронирован на выбранные даты другими гостями! Пожалуйста, выберите другой номер.
        </div>
    <?php endif; ?>

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
            <?php foreach($rooms as $r):
                if (!is_array($r)) continue; ?>
                <option value="<?php echo $r['id'] ?? ''; ?>">Номер <?php echo $r['room_number'] ?? 'N/A'; ?> (<?php echo number_format($r['price_per_day'] ?? 0, 0, ',', ' '); ?> ₽/сут)</option>
            <?php endforeach; ?>
        </select>

        <label>Имя гостя</label>
        <input type="text" name="client_name" required placeholder="Иванов Иван">

        <label>Контактный телефон</label>
        <input type="tel" name="phone" required placeholder="+...">

        <label>Количество человек</label>
        <input type="number" name="persons" value="1" min="1" required>

        <label>Заметки администратора</label>
        <textarea name="admin_notes" rows="3" placeholder="Дополнительная информация..."></textarea>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn" style="width: 100%;">✅ Подтвердить бронирование</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
