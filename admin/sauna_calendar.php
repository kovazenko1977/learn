<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

$selectedDate = $_GET['date'] ?? date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "update_booking") {
        $bookingId = (int)$_POST["booking_id"];
        $data = [
            "room_id" => (int)$_POST["room_id"],
            "check_in" => $_POST["check_in"],
            "check_out" => $_POST["check_out"],
            "status" => $_POST["status"]
        ];
        if ($bookingManager->updateBooking($bookingId, $data)) {
            header("Location: sauna_calendar.php?date=$selectedDate&success=updated");
        } else {
            header("Location: sauna_calendar.php?date=$selectedDate&error=overlap");
        }
        exit;
    } elseif ($_POST["action"] === "delete_booking") {
        $bookingManager->deleteBooking((int)$_POST["booking_id"]);
        header("Location: sauna_calendar.php?date=$selectedDate&success=deleted");
        exit;
    }
}

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
$saunaClassId = null;
foreach($classes as $c) if(stripos($c['name'], 'Сауна') !== false) $saunaClassId = $c['id'];

$saunas = array_filter($rooms, function($r) use ($saunaClassId) {
    return ($r['room_class_id'] == $saunaClassId);
});

$bookings = $store->findAll('bookings');
$bookingMap = [];
$hourlyBookings = [];
if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
        if (isset($b['id'])) $bookingMap[$b['id']] = $b;
        $isForSauna = false;
        foreach($saunas as $s) if($s['id'] == $b['room_id']) $isForSauna = true;
        if (!$isForSauna) continue;
        if (substr($b['check_in'], 0, 10) === $selectedDate) $hourlyBookings[] = $b;
    }
}

$pageTitle = 'График Сауны (по часам)';
include 'includes/header.php';
?>

<style>
    .sauna-grid-wrapper { overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; position: relative; }
    .sauna-grid-table { border-collapse: collapse; min-width: 100%; table-layout: fixed; }
    .sauna-grid-table th, .sauna-grid-table td { border: 1px solid #e2e8f0; text-align: center; }
    .sauna-grid-table th { background: #f8fafc; padding: 12px 8px; font-size: 0.9rem; }
    .time-cell { width: 60px; font-weight: 800; color: #64748b; background: #f8fafc !important; position: sticky; left: 0; z-index: 10; font-size: 0.8rem; }
    .booking-bar { background: var(--primary-color); color: white; padding: 4px; border-radius: 4px; font-size: 0.7rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 2px; cursor: pointer; }
    .booking-bar.status-booked { background: #d83b01; }
    .booking-bar.status-confirmed { background: #107c10; }

    @media (max-width: 768px) {
        .sauna-grid-table { width: <?php echo (count($saunas) * 120 + 60); ?>px; }
        .booking-bar { font-size: 0.65rem; padding: 2px; }
    }
</style>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="margin:0;">🧖‍♀️ График Сауны</h2>
        <form method="get" style="display:flex; gap:8px;">
            <input type="date" name="date" onchange="this.form.submit()" value="<?php echo $selectedDate; ?>" style="margin-bottom:0; width: auto;">
            <button type="submit" class="btn btn-primary btn-sm">OK</button>
        </form>
    </div>

    <div class="sauna-grid-wrapper">
        <table class="sauna-grid-table">
            <thead>
                <tr>
                    <th class="time-cell">час</th>
                    <?php foreach($saunas as $s): ?>
                        <th style="width: 120px;"><?php echo htmlspecialchars($s['room_number']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for($h=8; $h<24; $h++):
                    $t = sprintf('%02d:00', $h);
                    $isNow = (date('Y-m-d')===$selectedDate && date('H')==$h);
                ?>
                <tr <?php echo $isNow ? 'style="background:rgba(0,120,212,0.05)"':''; ?>>
                    <td class="time-cell"><?php echo $t; ?></td>
                    <?php foreach($saunas as $s): ?>
                        <td style="height: 44px; vertical-align: middle;" onclick="location.href='sauna_create_booking.php?room_id=<?php echo $s['id']; ?>&date=<?php echo $selectedDate; ?>T<?php echo $t; ?>'">
                            <?php foreach($hourlyBookings as $b):
                                if($b['room_id'] == $s['id'] && date('H', strtotime($b['check_in'])) == $h):
                            ?>
                                <div class="booking-bar status-<?php echo $b['status']; ?>" onclick="event.stopPropagation(); viewDetails(<?php echo $b['id']; ?>)">
                                    <?php echo htmlspecialchars($b['client_name']); ?>
                                </div>
                            <?php endif; endforeach; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Simple Details Modal -->
<div id="sauna-details-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); backdrop-filter: blur(4px); z-index:1100; align-items:center; justify-content:center;">
    <div class="mica-card" style="width: 400px; margin-bottom: 0;">
        <div id="s-view-mode">
            <h3>ℹ️ Бронирование</h3>
            <p>Гость: <strong id="s-guest"></strong></p>
            <p>Время: <strong id="s-time"></strong></p>
            <p>Статус: <span id="s-status" class="status-badge"></span></p>
            <div style="display:flex; gap:10px; margin-top:20px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="openSEdit()">Изменить/Перенести</button>
                <button class="btn" onclick="closeSDetails()">Закрыть</button>
            </div>
        </div>
        <div id="s-edit-mode" style="display:none;">
            <h3>📝 Редактирование</h3>
            <form method="post">
                <input type="hidden" name="action" value="update_booking">
                <input type="hidden" name="booking_id" id="se-id">

                <label>Сауна</label>
                <select name="room_id" id="se-room" style="width:100%;">
                    <?php foreach($saunas as $s) echo "<option value='{$s['id']}'>{$s['room_number']}</option>"; ?>
                </select>

                <label>Начало</label>
                <input type="datetime-local" name="check_in" id="se-start" style="width:100%;">

                <label>Конец</label>
                <input type="datetime-local" name="check_out" id="se-end" style="width:100%;">

                <label>Статус</label>
                <select name="status" id="se-status" style="width:100%;">
                    <option value="booked">Ожидается</option>
                    <option value="confirmed">Завершено</option>
                    <option value="cancelled">Отменено</option>
                </select>

                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeSEdit()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
            <form method="post" onsubmit="return confirm('Удалить?')">
                <input type="hidden" name="action" value="delete_booking">
                <input type="hidden" name="booking_id" id="se-del-id">
                <button type="submit" class="btn btn-danger" style="width:100%; margin-top:10px;">❌ Удалить полностью</button>
            </form>
        </div>
    </div>
</div>

<script>
const bookingMap = <?php echo json_encode($bookingMap); ?>;
function viewDetails(id) {
    const b = bookingMap[id];
    document.getElementById('s-guest').textContent = b.client_name;
    document.getElementById('s-time').textContent = b.check_in + ' - ' + b.check_out;
    document.getElementById('s-status').textContent = b.status;

    document.getElementById('se-id').value = id;
    document.getElementById('se-del-id').value = id;
    document.getElementById('se-room').value = b.room_id;
    document.getElementById('se-start').value = b.check_in.replace(' ', 'T');
    document.getElementById('se-end').value = b.check_out.replace(' ', 'T');
    document.getElementById('se-status').value = b.status;

    document.getElementById('sauna-details-overlay').style.display = 'flex';
}
function closeSDetails() { document.getElementById('sauna-details-overlay').style.display = 'none'; closeSEdit(); }
function openSEdit() { document.getElementById('s-view-mode').style.display='none'; document.getElementById('s-edit-mode').style.display='block'; }
function closeSEdit() { document.getElementById('s-view-mode').style.display='block'; document.getElementById('s-edit-mode').style.display='none'; }
</script>

<?php include 'includes/footer.php'; ?>
