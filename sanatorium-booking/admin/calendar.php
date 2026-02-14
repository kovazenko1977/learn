<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Calendar\CalendarManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$calendarManager = new CalendarManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_booking') {
    $duration = (int)($_POST['duration'] ?? 1);
    $bookingData = [
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['date'],
        'check_out' => date('Y-m-d', strtotime($_POST['date'] . " +$duration days")),
        'persons' => 1,
        'phone' => $_POST['phone'],
        'status' => $_POST['status'] ?? 'reserved',
        'client_name' => $_POST['client_name'],
        'citizenship' => $_POST['citizenship'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];
    $bookingId = $bookingManager->createBooking($bookingData);
    if ($bookingId) {
        header('Location: calendar.php?success=1');
    } else {
        header('Location: calendar.php?error=overlap');
    }
    exit;
}

$rooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');
$bookings = $store->findAll('bookings');

$bookingMap = [];
if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (is_array($b) && isset($b['id'])) {
            $bookingMap[$b['id']] = $b;
        }
    }
}

$startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$dates = $calendarManager->getDateRange($startDate, $endDate);
$occupancy = $calendarManager->getOccupancyData($startDate, $endDate);

$pageTitle = 'Календарь занятости';
include 'includes/header.php';
?>

<div class="mica-card">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'overlap'): ?>
        <div class="error" style="margin-bottom: 20px; background: rgba(216, 59, 1, 0.1); color: #d83b01; padding: 10px; border-radius: 6px; border: 1px solid rgba(216, 59, 1, 0.2);">
            ⚠️ Ошибка: Номер уже забронирован на некоторые из выбранных дат!
        </div>
    <?php endif; ?>

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
                <?php foreach ($rooms as $room):
                    if (!is_array($room)) continue; ?>
                <tr>
                    <td style="position: sticky; left: 0; background: #fff; z-index: 10; font-weight: 600;">
                        <?php echo htmlspecialchars($room['room_number']); ?>
                    </td>
                    <?php foreach ($dates as $date):
                        $rid = $room['id'] ?? 0;
                        $occ = $occupancy[$rid][$date] ?? ['status' => 'free'];
                        $status = $occ['status'];
                        $bookingId = $occ['booking_id'] ?? null;
                        $clientInfo = "";
                        if ($bookingId && isset($bookingMap[$bookingId])) {
                            $b = $bookingMap[$bookingId];
                            $cname = $b['client_name'] ?? 'N/A';
                            $cphone = $b['phone'] ?? 'N/A';
                            $clientInfo = htmlspecialchars($cname . " (" . $cphone . ")", ENT_QUOTES, 'UTF-8');
                        }
                        if ($status === 'free' && $rid) {
                            $onclick = "openModal({$rid}, '" . addslashes($room['room_number'] ?? '') . "', '{$date}')";
                        } elseif ($bookingId) {
                            $onclick = "viewBookingDetails({$bookingId}, '" . addslashes($room['room_number'] ?? '') . "')";
                        } else {
                            $onclick = "";
                        }
                    ?>
                        <td class="cal-status-<?php echo $status; ?>"
                            onclick="<?php echo $onclick; ?>"
                            title="<?php echo $clientInfo; ?>"
                            style="text-align: center; padding: 12px 4px; border-left: 1px solid rgba(0,0,0,0.02); transition: background 0.2s; cursor: pointer;">
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
            <div style="width: 12px; height: 12px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 2px;"></div> Зарезервировано
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <div style="width: 12px; height: 12px; background: #cfe2ff; border: 1px solid #b6d4fe; border-radius: 2px;"></div> Занято (заехали)
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="details-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); backdrop-filter: blur(4px); z-index:1100; align-items:center; justify-content:center;">
    <div class="mica-card" style="width: 450px; margin-bottom: 0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3>ℹ️ Детали бронирования</h3>
            <button type="button" onclick="closeDetails()" style="background:none; border:none; cursor:pointer; font-size:1.5rem; line-height:1;">&times;</button>
        </div>
        <div id="details-content" style="font-size: 0.95rem;">
            <div style="margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eee;">
                <p style="margin:5px 0;"><span style="color:#666;">Гость:</span> <strong id="d-guest"></strong></p>
                <p style="margin:5px 0;"><span style="color:#666;">Телефон:</span> <strong id="d-phone"></strong></p>
            </div>
            <div style="margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eee;">
                <p style="margin:5px 0;"><span style="color:#666;">Номер:</span> <strong id="d-room"></strong></p>
                <p style="margin:5px 0;"><span style="color:#666;">Период:</span> <strong id="d-period"></strong></p>
                <p style="margin:5px 0;"><span style="color:#666;">Статус:</span> <span id="d-status" class="status-badge"></span></p>
            </div>
            <div style="margin-bottom:10px;">
                <p style="margin:5px 0; color:#666;">Заметки администратора:</p>
                <div id="d-notes" style="background:rgba(0,0,0,0.03); padding:10px; border-radius:4px; font-style:italic; min-height:40px;"></div>
            </div>
        </div>
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn" onclick="closeDetails()">Закрыть</button>
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

            <label>Статус</label>
            <select name="status">
                <option value="reserved">Зарезервировано</option>
                <option value="booked">Занято (заехали)</option>
            </select>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                <button type="submit" class="btn">Забронировать</button>
            </div>
        </form>
    </div>
</div>

<script>
    const bookingData = <?php echo json_encode($bookingMap); ?>;
    const statusLabels = {
        'new': 'Новое',
        'reserved': 'Зарезервировано',
        'booked': 'Занято (заехали)',
        'confirmed': 'Подтверждено',
        'cancelled': 'Отменено'
    };

    function viewBookingDetails(id, roomNum) {
        const b = bookingData[id];
        if (!b) return;

        document.getElementById('d-guest').textContent = b.client_name || 'N/A';
        document.getElementById('d-phone').textContent = b.phone || 'N/A';
        document.getElementById('d-room').textContent = roomNum;
        document.getElementById('d-period').textContent = (b.check_in || '') + ' — ' + (b.check_out || '');

        const statusEl = document.getElementById('d-status');
        statusEl.textContent = statusLabels[b.status] || b.status;
        statusEl.className = 'status-badge status-' + (b.status || 'new');

        document.getElementById('d-notes').textContent = b.admin_notes || 'Нет заметок';

        document.getElementById('details-overlay').style.display = 'flex';
    }

    function closeDetails() {
        document.getElementById('details-overlay').style.display = 'none';
    }

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
        if (event.target == document.getElementById('details-overlay')) {
            closeDetails();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
