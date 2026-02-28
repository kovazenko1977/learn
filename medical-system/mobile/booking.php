<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$sys = (new \Medical\Core\JsonStore('settings'))->getAll();
if (!($sys['is_booking_enabled'] ?? false)) {
    header('Location: index.php');
    exit;
}

$rm = new \Medical\Core\Managers\RoomManager();
$bm = new \Medical\Core\Managers\BookingManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_booking') {
            $bm->create([
                'room_id' => $_POST['room_id'],
                'guest_name' => $_POST['guest_name'],
                'guest_phone' => $_POST['guest_phone'],
                'guest_birth_date' => $_POST['guest_birth_date'] ?? '',
                'check_in' => $_POST['check_in'],
                'check_out' => $_POST['check_out'],
                'status' => 'confirmed'
            ]);
        } elseif ($action === 'update_status') {
            $bm->update($_POST['id'], ['status' => $_POST['status']]);
        }
        header("Location: booking.php?success=1");
        exit;
    }
}

$rooms = $rm->getAll();
$bookings = $bm->getAll();
$view = $_GET['view'] ?? 'list';

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; display: flex; align-items: center; justify-content: space-between; background: #F3EDF7;">
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Бронирование</h2>
    <div style="display: flex; gap: 8px;">
        <button onclick="document.getElementById('newBookingModal').style.display='flex'" class="md-btn md-btn-primary" style="width: 40px; height: 40px; padding: 0; border-radius: 50%;">
            <i data-lucide="plus"></i>
        </button>
    </div>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <?php if (empty($bookings)): ?>
        <div style="text-align: center; padding: 48px; color: var(--md-secondary);">Бронирований нет</div>
    <?php else: ?>
        <?php foreach (array_reverse($bookings) as $b):
            $room = $rm->getById($b['room_id']);
            $statusMap = [
                'preliminary' => ['bg' => '#fff8e1', 'text' => '#b7791f', 'label' => 'Предв.'],
                'confirmed' => ['bg' => '#fde7e9', 'text' => '#d13438', 'label' => 'Подтв.'],
                'checked_in' => ['bg' => '#dff6dd', 'text' => '#107c10', 'label' => 'Проживает'],
                'checked_out' => ['bg' => '#f3f2f1', 'text' => '#605e5c', 'label' => 'Выехал'],
                'cancelled' => ['bg' => '#f3f2f1', 'text' => '#a19f9d', 'label' => 'Отмена']
            ];
            $s = $statusMap[$b['status']] ?? $statusMap['preliminary'];
        ?>
            <div class="md-card" style="margin: 0 0 12px 0; padding: 12px;" onclick='showDetails(<?php echo json_encode($b); ?>)'>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                    <span style="background: <?php echo $s['bg']; ?>; color: <?php echo $s['text']; ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?php echo $s['label']; ?></span>
                </div>
                <div style="font-size: 13px; color: var(--md-secondary);">
                    <div>Номер: <strong><?php echo htmlspecialchars($room['name'] ?? 'Удален'); ?></strong></div>
                    <div>Период: <?php echo date('d.m', strtotime($b['check_in'])); ?> — <?php echo date('d.m.Y', strtotime($b['check_out'])); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Details Modal -->
<div id="detailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
    <div class="md-card" style="width: 100%; margin: 0;">
        <h3 id="det_name" style="margin-top: 0;">Детали</h3>
        <div style="font-size: 14px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px;">
            <div id="det_room"></div>
            <div id="det_period"></div>
            <div id="det_phone"></div>
            <div id="det_cost"></div>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="det_id">
            <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Сменить статус</label>
            <select name="status" id="det_status" class="md-input">
                <option value="preliminary">Предварительно</option>
                <option value="confirmed">Подтверждено</option>
                <option value="checked_in">Заселение</option>
                <option value="checked_out">Выезд</option>
                <option value="cancelled">Отмена</option>
            </select>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="button" onclick="document.getElementById('detailsModal').style.display='none'" class="md-btn" style="flex: 1; background: #eee;">Закрыть</button>
                <button type="submit" class="md-btn md-btn-primary" style="flex: 1;">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- New Booking Modal -->
<div id="newBookingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
    <div class="md-card" style="width: 100%; margin: 0; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-top: 0;">Новая бронь</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="create_booking">

            <label style="display: block; font-size: 12px; margin-bottom: 4px;">Номер</label>
            <select name="room_id" class="md-input" required>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?> (<?php echo $r['type']; ?>)</option>
                <?php endforeach; ?>
            </select>

            <label style="display: block; font-size: 12px; margin-bottom: 4px;">Гость (ФИО)</label>
            <input type="text" name="guest_name" class="md-input" required>

            <label style="display: block; font-size: 12px; margin-bottom: 4px;">Телефон</label>
            <input type="tel" name="guest_phone" class="md-input" value="+375 " required>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; margin-bottom: 4px;">Заезд</label>
                    <input type="date" name="check_in" class="md-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; margin-bottom: 4px;">Выезд</label>
                    <input type="date" name="check_out" class="md-input" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
            </div>

            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="button" onclick="document.getElementById('newBookingModal').style.display='none'" class="md-btn" style="flex: 1; background: #eee;">Отмена</button>
                <button type="submit" class="md-btn md-btn-primary" style="flex: 1;">Забронировать</button>
            </div>
        </form>
    </div>
</div>

<script>
function showDetails(b) {
    document.getElementById('det_id').value = b.id;
    document.getElementById('det_name').innerText = b.guest_name;
    document.getElementById('det_room').innerHTML = 'Номер: <strong>' + (b.room_name || '...') + '</strong>';
    document.getElementById('det_period').innerText = 'Период: ' + b.check_in + ' — ' + b.check_out;
    document.getElementById('det_phone').innerText = 'Тел: ' + b.guest_phone;
    document.getElementById('det_cost').innerText = 'Сумма: ' + (b.total_cost || 0).toLocaleString() + ' ₽';
    document.getElementById('det_status').value = b.status;
    document.getElementById('detailsModal').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
