<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

$id = (int)($_GET['id'] ?? 0);
$booking = $store->findOne('bookings', $id);

if (!$booking) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'client_name' => $_POST['client_name'],
        'phone' => $_POST['phone'],
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['check_in'],
        'check_out' => $_POST['check_out'],
        'status' => $_POST['status'],
        'persons' => (int)$_POST['persons'],
        'admin_notes' => $_POST['admin_notes']
    ];

    if ($bookingManager->updateBooking($id, $data)) {
        $message = "Бронирование успешно обновлено!";
        $booking = $store->findOne('bookings', $id);
    } else {
        $error = "Ошибка: Номер занят на эти даты или неверные данные.";
    }
}

$rooms = $store->findAll('rooms');
$pageTitle = 'Редактирование бронирования';
include 'includes/header.php';
?>

<div class="mica-card" style="max-width: 700px; margin: 0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📝 Редактировать бронь #<?php echo $id; ?></h2>
        <a href="dashboard.php" class="btn btn-secondary">Назад к списку</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="modern-form">
        <div class="grid-2">
            <div class="form-group">
                <label>Имя гостя</label>
                <input type="text" name="client_name" value="<?php echo htmlspecialchars($booking['client_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($booking['phone']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Номер / Ресурс</label>
            <select name="room_id" required>
                <?php foreach($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo ($r['id'] == $booking['room_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['room_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Заезд</label>
                <input type="datetime-local" name="check_in" value="<?php echo str_replace(' ', 'T', $booking['check_in']); ?>" required>
            </div>
            <div class="form-group">
                <label>Выезд</label>
                <input type="datetime-local" name="check_out" value="<?php echo str_replace(' ', 'T', $booking['check_out']); ?>" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Кол-во человек</label>
                <input type="number" name="persons" value="<?php echo $booking['persons']; ?>" min="1">
            </div>
            <div class="form-group">
                <label>Статус</label>
                <select name="status">
                    <option value="new" <?php echo ($booking['status'] == 'new') ? 'selected' : ''; ?>>Новое</option>
                    <option value="reserved" <?php echo ($booking['status'] == 'reserved') ? 'selected' : ''; ?>>Зарезервировано</option>
                    <option value="booked" <?php echo ($booking['status'] == 'booked') ? 'selected' : ''; ?>>Занято (заехали)</option>
                    <option value="confirmed" <?php echo ($booking['status'] == 'confirmed') ? 'selected' : ''; ?>>Подтверждено</option>
                    <option value="cancelled" <?php echo ($booking['status'] == 'cancelled') ? 'selected' : ''; ?>>Отменено</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Заметки администратора</label>
            <textarea name="admin_notes" style="width:100%; height:100px;"><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions" style="margin-top: 30px; display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Сохранить все изменения</button>
            <button type="button" class="btn btn-danger" onclick="if(confirm('Удалить бронь полностью?')) { document.getElementById('del-form').submit(); }">Удалить бронь</button>
        </div>
    </form>

    <form id="del-form" method="post" action="dashboard.php" style="display:none;">
        <input type="hidden" name="action" value="delete_booking">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
    </form>
</div>

<?php include 'includes/footer.php'; ?>
