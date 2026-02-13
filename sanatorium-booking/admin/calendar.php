<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_booking') {
    $duration = (int)($_POST['duration'] ?? 1);
    $bookingData = [
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['date'],
        'check_out' => date('Y-m-d', strtotime($_POST['date'] . " +$duration days")),
        'persons' => 1,
        'phone' => $_POST['phone'],
        'status' => 'confirmed',
        'client_name' => $_POST['client_name'],
        'citizenship' => $_POST['citizenship'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];
    $bookingManager->createBooking($bookingData);
    header('Location: calendar.php?success=1');
    exit;
}

$rooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');
$bookings = $store->findAll('bookings');

$bookingMap = [];
foreach ($bookings as $b) {
    $bookingMap[$b['id']] = $b;
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$period = new DatePeriod(new DateTime($startDate), new DateInterval('P1D'), (new DateTime($endDate))->modify('+1 day'));
$dates = [];
foreach ($period as $date) { $dates[] = $date->format('Y-m-d'); }

$occupancy = [];
foreach ($calendar as $entry) {
    $occupancy[$entry['room_id']][$entry['date']] = [
        'status' => $entry['status'],
        'booking_id' => $entry['booking_id'] ?? null
    ];
}

$pageTitle = 'Календарь занятости';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📅 Сетка занятости номеров</h2>
        <form method="get" style="display:flex; gap:10px; align-items: center;">
            <input type="date" name="start_date" value="<?php echo $startDate; ?>" style="width:auto; margin-bottom:0;">
            <span>—</span>
            <input type="date" name="end_date" value="<?php echo $endDate; ?>" style="width:auto; margin-bottom:0;">
            <button type="submit" class="btn">Показать</button>
        </form>
    </div>

    <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
        <table style="margin-top: 0;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02);">
                    <th style="position: sticky; left: 0; background: #fff; z-index: 10;">Номер</th>
                    <?php foreach ($dates as $date): ?>
                        <th style="text-align: center; min-width: 45px;"><?php echo date('d.m', strtotime($date)); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td style="position: sticky; left: 0; background: #fff; z-index: 10; font-weight: 600;">
                        <?php echo htmlspecialchars($room['room_number']); ?>
                    </td>
                    <?php foreach ($dates as $date):
                        $occ = $occupancy[$room['id']][$date] ?? ['status' => 'free'];
                        $status = $occ['status'];
                        $bookingId = $occ['booking_id'] ?? null;
                        $clientInfo = "";
                        if ($bookingId && isset($bookingMap[$bookingId])) {
                            $b = $bookingMap[$bookingId];
                            $clientInfo = htmlspecialchars(($b['client_name'] ?? 'N/A') . " (" . $b['phone'] . ")");
                        }
                        $onclick = ($status === 'free') ? "openModal({$room['id']}, '{$room['room_number']}', '{$date}')" : "";
                    ?>
                        <td class="cal-status-<?php echo $status; ?>"
                            onclick="<?php echo $onclick; ?>"
                            title="<?php echo $clientInfo; ?>"
                            style="text-align: center; padding: 12px 4px; border-left: 1px solid rgba(0,0,0,0.02); transition: background 0.2s; cursor: <?php echo $status === 'free' ? 'pointer' : 'default'; ?>;">
                            <?php if ($status !== 'free'): ?>
                                <div style="width: 10px; height: 10px; background: currentColor; border-radius: 50%; margin: 0 auto; opacity: 0.6;"></div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 0.85rem; color: #666;">
        <div style="display: flex; align-items: center; gap: 6px;">
            <div style="width: 12px; height: 12px; border: 1px solid var(--border-color); border-radius: 2px;"></div> Свободно
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <div style="width: 12px; height: 12px; background: #cfe2ff; border-radius: 2px;"></div> Забронировано
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); backdrop-filter: blur(4px); z-index:1000; align-items:center; justify-content:center;">
    <div class="mica-card" style="width: 400px; margin-bottom: 0;">
        <h3>🆕 Быстрое бронирование</h3>
        <p style="font-size: 0.9rem; color: #666; margin-bottom: 20px;">
            Номер: <strong id="m-num"></strong> | Дата: <strong id="m-date"></strong>
        </p>
        <form method="post">
            <input type="hidden" name="action" value="quick_booking">
            <input type="hidden" name="room_id" id="m-id">
            <input type="hidden" name="date" id="m-input-date">

            <label>Имя гостя</label>
            <input type="text" name="client_name" required placeholder="Иванов Иван">

            <label>Телефон</label>
            <input type="tel" name="phone" required placeholder="+...">

            <div class="grid-2">
                <div>
                    <label>Гражданство</label>
                    <input type="text" name="citizenship">
                </div>
                <div>
                    <label>Количество дней</label>
                    <input type="number" name="duration" value="1" min="1" required>
                </div>
            </div>

            <label>Адрес</label>
            <input type="text" name="address">

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                <button type="submit" class="btn">Забронировать</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id, num, date) {
        document.getElementById('m-id').value = id;
        document.getElementById('m-input-date').value = date;
        document.getElementById('m-num').textContent = num;
        document.getElementById('m-date').textContent = date;
        document.getElementById('modal-overlay').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('modal-overlay').style.display = 'none';
    }
    // Close modal on click outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('modal-overlay')) {
            closeModal();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
