<?php require_once "auth.php";
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;
use App\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

$pre_room_id = $_GET['room_id'] ?? '';
$pre_date = $_GET['date'] ?? '';

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');

$hourlyClassIds = [];
foreach($classes as $c) if(($c['booking_type'] ?? '') === 'hourly') $hourlyClassIds[] = $c['id'];

$hourlyRooms = array_filter($rooms, function($r) use ($hourlyClassIds) {
    return in_array($r['room_class_id'], $hourlyClassIds);
});

$pageTitle = 'Новое почасовое бронирование';
include 'includes/header.php';
?>

<div class="mica-card" style="max-width: 600px; margin: 0 auto;">
    <h2>🆕 Новое почасовое бронирование</h2>
    <form id="hourly-admin-form" class="modern-form">
        <div class="form-group">
            <label>Объект</label>
            <select name="room_id" id="hourly_room_id" required>
                <?php foreach($hourlyRooms as $s): ?>
                    <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['price_per_hour']; ?>" <?php echo ($pre_room_id == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['room_number']); ?> (<?php echo $s['price_per_hour']; ?> ₽/час)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Дата</label>
                <input type="date" id="booking_date" value="<?php echo $pre_date ? substr($pre_date, 0, 10) : date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Время начала</label>
                <select id="start_hour">
                    <?php for($h=8; $h<24; $h++) echo "<option value='".sprintf('%02d:00', $h)."' ".($pre_date && substr($pre_date, 11, 5) == sprintf('%02d:00', $h) ? 'selected':'').">".sprintf('%02d:00', $h)."</option>"; ?>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Кол-во часов</label>
                <input type="number" id="duration" value="1" min="1" max="12">
            </div>
            <div class="form-group">
                <label>Кол-во человек</label>
                <input type="number" name="persons" value="2" min="1">
            </div>
        </div>

        <div class="form-group">
            <label>Имя гостя</label>
            <input type="text" name="client_name" required>
        </div>

        <div class="form-group">
            <label>Телефон</label>
            <input type="tel" name="phone" required>
        </div>

        <div id="admin-price-summary" style="padding: 15px; background: rgba(0,120,212,0.05); border-radius: 8px; margin: 20px 0;">
            Итого: <strong id="admin-total-sum">0</strong> ₽
        </div>

        <div class="form-actions">
            <a href="hourly_grid.php" class="btn btn-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">Создать бронь</button>
        </div>
    </form>
</div>

<script>
    const roomSelect = document.getElementById('hourly_room_id');
    const durationInput = document.getElementById('duration');
    const startSelect = document.getElementById('start_hour');
    const dateInput = document.getElementById('booking_date');
    const totalEl = document.getElementById('admin-total-sum');

    function updatePrice() {
        if(!roomSelect.selectedOptions[0]) return;
        const price = parseFloat(roomSelect.selectedOptions[0].dataset.price);
        const hours = parseInt(durationInput.value);
        totalEl.textContent = (price * hours).toLocaleString();
    }

    roomSelect.onchange = updatePrice;
    durationInput.oninput = updatePrice;
    updatePrice();

    document.getElementById('hourly-admin-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const obj = Object.fromEntries(formData.entries());

        const date = dateInput.value;
        const start = startSelect.value;
        const hours = parseInt(durationInput.value);

        obj.check_in = `${date} ${start}:00`;
        const startH = parseInt(start.split(':')[0]);
        const endH = startH + hours;
        obj.check_out = `${date} ${String(endH).padStart(2, '0')}:00`;
        obj.is_hourly = true;
        obj.status = 'booked';

        const resp = await fetch('../api/v1.php?action=booking/create', {
            method: 'POST',
            body: JSON.stringify(obj)
        });
        const result = await resp.json();
        if(result.success) {
            location.href = 'hourly_grid.php?success=1';
        } else {
            alert('Ошибка: ' + (result.error || 'пересечение времени или ошибка сервера'));
        }
    };
</script>

<?php include 'includes/footer.php'; ?>
