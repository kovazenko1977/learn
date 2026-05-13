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
        header('Location: create_booking.php?error=overlap&check_in='.$_POST['check_in'].'&check_out='.$_POST['check_out'].'&gender='.$_POST['guest_gender']);
    }
    exit;
}

$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$selected_room_id = $_GET['room_id'] ?? '';

$rooms = ($check_in && $check_out) ? $roomManager->getAllRooms() : [];

$occupancy = null;
if ($check_in && $check_out && $selected_room_id) {
    $occupancy = $bookingManager->getRoomOccupancy($selected_room_id, $check_in, $check_out);
}

$pageTitle = 'Новое бронирование';
include 'includes/header.php';
?>

<div class="mica-card" style="max-width: 650px;">
    <h2>📅 Создание новой заявки</h2>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'overlap'): ?>
        <div class="error" style="margin-top: 20px; background: rgba(216, 59, 1, 0.1); color: #d83b01; padding: 10px; border-radius: 6px; border: 1px solid rgba(216, 59, 1, 0.2);">
            ⚠️ Ошибка: В номере нет свободных мест выбранного типа, или нарушено правило подселения по полу.
        </div>
    <?php endif; ?>

    <form method="get" style="margin-top: 20px;">
        <div class="grid-2">
            <div>
                <label>Дата заезда</label>
                <input type="date" name="check_in" value="<?php echo $check_in; ?>" required onchange="this.form.submit()">
            </div>
            <div>
                <label>Дата выезда</label>
                <input type="date" name="check_out" value="<?php echo $check_out; ?>" required onchange="this.form.submit()">
            </div>
        </div>
    </form>

    <?php if ($check_in && $check_out): ?>
    <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-color);">

    <form method="post" class="modern-form">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">

        <div class="grid-2">
            <div>
                <label>Объект (Номер)</label>
                <select name="room_id" id="room_id" required onchange="updateAvailability()">
                    <option value="">-- Выберите номер --</option>
                    <?php foreach($rooms as $r):
                        if (!is_array($r)) continue;
                        $sel = ($selected_room_id == $r['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $r['id'] ?? ''; ?>" <?php echo $sel; ?>>
                            №<?php echo $r['room_number'] ?? 'N/A'; ?>
                            (<?php echo ($r['main_seats_count'] ?? 1); ?>+<?php echo ($r['extra_seats_count'] ?? 0); ?> мест)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Пол гостя (для подселения)</label>
                <select name="guest_gender" id="guest_gender" required>
                    <option value="male">👨 Мужской</option>
                    <option value="female">👩 Женский</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
            <input type="checkbox" name="is_family" id="is_family" value="1" onchange="updateAvailability()">
            <label for="is_family" style="margin: 0; cursor: pointer;">💑 Семейная пара (разрешить разный пол в номере)</label>
        </div>

        <?php if ($occupancy): ?>
        <div class="availability-info" style="margin-top: 15px; padding: 12px; background: rgba(0, 120, 212, 0.05); border: 1px solid rgba(0, 120, 212, 0.1); border-radius: 8px;">
            <div style="display: flex; gap: 20px;">
                <span>🛏 Свободно основных: <strong><?php echo $occupancy['main_free']; ?></strong> / <?php echo $occupancy['main_total']; ?></span>
                <span>🛋 Свободно доп. мест: <strong><?php echo $occupancy['extra_free']; ?></strong> / <?php echo $occupancy['extra_total']; ?></span>
            </div>
            <?php if (!empty($occupancy['genders'])): ?>
                <div style="margin-top: 5px; font-size: 0.9em; color: #666;">
                    👥 В номере уже есть: <?php
                        echo implode(', ', array_map(function($g) {
                            return ($g === 'male' ? '👨 Мужчины' : '👩 Женщины');
                        }, $occupancy['genders']));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid-2" style="margin-top: 15px;">
            <div>
                <label>Тип места</label>
                <select name="seat_type" id="seat_type" required onchange="updateAvailability()">
                    <option value="main" <?php echo ($_GET['seat_type'] ?? '') === 'main' ? 'selected' : ''; ?>>🛏 Основное место</option>
                    <option value="extra" <?php echo ($_GET['seat_type'] ?? '') === 'extra' ? 'selected' : ''; ?>>🛋 Дополнительное место</option>
                </select>
            </div>
            <div>
                <label>Количество человек</label>
                <input type="number" name="persons" value="1" min="1" required>
            </div>
        </div>

        <label style="margin-top: 15px;">ФИО Гостя</label>
        <input type="text" name="client_name" required placeholder="Иванов Иван Иванович">

        <label>Контактный телефон</label>
        <input type="tel" name="phone" required placeholder="+7...">

        <label>Заметки администратора</label>
        <textarea name="admin_notes" rows="2" placeholder="Особые пожелания..."></textarea>

        <div style="margin-top: 25px;">
            <?php
            $can_submit = true;
            $error_msg = "";
            if ($occupancy) {
                $st = $_GET['seat_type'] ?? 'main';
                if ($st === 'extra' && $occupancy['extra_free'] <= 0) {
                    $can_submit = false;
                    $error_msg = "Нет свободных дополнительных мест";
                } elseif ($st === 'main' && $occupancy['main_free'] <= 0) {
                    $can_submit = false;
                    $error_msg = "Нет свободных основных мест";
                }
            }
            ?>

            <?php if (!$can_submit): ?>
                <div style="color: #d83b01; margin-bottom: 10px; font-weight: bold; text-align: center;">
                    ⚠️ <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;" <?php echo !$can_submit ? 'disabled' : ''; ?>>
                ✅ Подтвердить бронирование
            </button>
        </div>
    </form>
    <?php else: ?>
        <p style="padding: 40px; text-align: center; color: #888; background: rgba(0,0,0,0.02); border-radius: 12px; margin-top: 20px;">
            Выберите даты заезда и выезда для продолжения
        </p>
    <?php endif; ?>
</div>

<script>
function updateAvailability() {
    const roomId = document.getElementById('room_id').value;
    const seatType = document.getElementById('seat_type').value;
    const isFamily = document.getElementById('is_family').checked ? '1' : '';

    const url = new URL(window.location.href);
    url.searchParams.set('room_id', roomId);
    url.searchParams.set('seat_type', seatType);
    url.searchParams.set('is_family', isFamily);
    window.location.href = url.toString();
}

// Restore family checkbox state on load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('is_family') === '1') {
        document.getElementById('is_family').checked = true;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
