<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;
use Sanatorium\Core\Calendar\CalendarManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$calendarManager = new CalendarManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'quick_booking') {
        $bookingId = $bookingManager->createBooking($_POST);
        if ($bookingId) {
            header('Location: calendar.php?success=1');
        } else {
            header('Location: calendar.php?error=overlap');
        }
        exit;
    } elseif ($_POST['action'] === 'update_booking') {
        if ($bookingManager->updateBooking((int)$_POST['booking_id'], $_POST)) {
            header('Location: calendar.php?success=updated');
        } else {
            header('Location: calendar.php?error=overlap');
        }
        exit;
    } elseif ($_POST['action'] === 'delete_booking') {
        $bookingManager->deleteBooking((int)$_POST['booking_id']);
        header('Location: calendar.php?success=deleted');
        exit;
    }
}

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
$dailyClassIds = [];
foreach($classes as $c) if(($c['booking_type'] ?? 'daily') === 'daily') $dailyClassIds[] = $c['id'];
$rooms = array_filter($rooms, function($r) use ($dailyClassIds) {
    return in_array($r['room_class_id'], $dailyClassIds);
});

$bookings = $store->findAll('bookings');
$bookingMap = [];
if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (is_array($b) && isset($b['id'])) $bookingMap[$b['id']] = $b;
    }
}

$startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$dates = $calendarManager->getDateRange($startDate, $endDate);

$pageTitle = 'Шахматка (посуточно)';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📅 График занятости мест</h2>
        <form method="get" style="display:flex; gap:10px; align-items: center;">
            <input type="date" name="start_date" onchange="this.form.submit()" value="<?php echo $startDate; ?>" style="width:auto; margin-bottom:0;">
            <input type="date" name="end_date" onchange="this.form.submit()" value="<?php echo $endDate; ?>" style="width:auto; margin-bottom:0;">
        </form>
    </div>

    <div style="overflow-x: auto; border-radius: 12px; border: 1px solid var(--border-color);">
        <table class="chess-table">
            <thead>
                <tr>
                    <th style="position: sticky; left: 0; background: #f8fafc; z-index: 20; width: 100px;">Номер</th>
                    <?php foreach ($dates as $date): ?>
                        <th style="text-align: center; min-width: 45px;"><?php echo date('d.m', strtotime($date)); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room):
                    $rid = $room['id'];
                    $totalCapacity = (int)($room['main_seats_count'] ?? 1) + (int)($room['extra_seats_count'] ?? 0);
                ?>
                <tr>
                    <td style="position: sticky; left: 0; background: #fff; z-index: 10; font-weight: 700; border-right: 2px solid #eee;">
                        <?php echo htmlspecialchars($room['room_number']); ?>
                        <div style="font-size: 0.6rem; color: #999; font-weight: 400;"><?php echo $totalCapacity; ?> мест</div>
                    </td>
                    <?php foreach ($dates as $date):
                        $dayBookings = [];
                        foreach($bookingMap as $b) {
                            if($b['room_id'] == $rid && $b['status'] != 'cancelled') {
                                if($date >= substr($b['check_in'],0,10) && $date < substr($b['check_out'],0,10)) {
                                    $dayBookings[] = $b;
                                }
                            }
                        }

                        $count = count($dayBookings);
                        $gender = null;
                        if($count > 0) $gender = $dayBookings[0]['guest_gender'] ?? 'male';

                        $status = 'free';
                        if($count > 0) {
                            $status = ($count >= $totalCapacity) ? 'full' : 'partial';
                            if($dayBookings[0]['status'] === 'reserved') $status = 'reserved';
                        }

                        $cellClass = "cal-cell status-$status";
                        if($status === 'partial') $cellClass .= " gender-$gender";

                        $tooltip = "";
                        foreach($dayBookings as $db) $tooltip .= ($db['client_name'] ?? 'Гость') . " (".($db['guest_gender']=='male'?'М':'Ж').")\n";
                    ?>
                        <td class="<?php echo $cellClass; ?>"
                            onclick="handleCellClick(<?php echo $rid; ?>, '<?php echo $date; ?>', <?php echo json_encode($dayBookings); ?>)"
                            title="<?php echo htmlspecialchars($tooltip); ?>">
                            <?php if($count > 0): ?>
                                <div class="cell-info">
                                    <span class="gender-icon"><?php echo $gender == 'male' ? '♂' : '♀'; ?></span>
                                    <span class="occupancy-ratio"><?php echo $count; ?>/<?php echo $totalCapacity; ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="calendar-legend" style="margin-top: 25px; display: flex; gap: 20px; font-size: 0.8rem; color: #666; flex-wrap: wrap;">
        <div class="legend-item"><span class="box free"></span> Свободно</div>
        <div class="legend-item"><span class="box reserved"></span> Резерв</div>
        <div class="legend-item"><span class="box partial-male"></span> Частично (Муж)</div>
        <div class="legend-item"><span class="box partial-female"></span> Частично (Жен)</div>
        <div class="legend-item"><span class="box full"></span> Занято полностью</div>
    </div>
</div>

<!-- Modal logic simplified for the overhaul -->
<div id="booking-modal" class="modal-overlay" style="display:none;">
    <div class="mica-card" style="width: 500px;">
        <h3 id="modal-title">Детали места</h3>
        <div id="modal-content"></div>
        <div style="margin-top: 20px; text-align: right;">
            <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
            <button id="btn-add-booking" class="btn btn-primary" onclick="openCreateForm()">+ Добавить гостя</button>
        </div>
    </div>
</div>

<script>
    let currentRoomId, currentDate;

    function handleCellClick(rid, date, bookings) {
        currentRoomId = rid;
        currentDate = date;
        const modal = document.getElementById('booking-modal');
        const content = document.getElementById('modal-content');

        let html = `<p>Дата: <strong>${date}</strong></p>`;
        if(bookings.length > 0) {
            html += '<div style="margin-top:15px;">';
            bookings.forEach(b => {
                html += `
                <div style="padding:10px; background:rgba(0,0,0,0.03); border-radius:8px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong>${b.client_name}</strong> (${b.guest_gender == 'male' ? '👨' : '👩'})<br>
                        <small>${b.seat_type == 'extra' ? 'Доп. место' : 'Основное'}</small>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="location.href='edit_booking.php?id=${b.id}'">Изм.</button>
                </div>`;
            });
            html += '</div>';
        } else {
            html += '<p style="color:#888;">Места полностью свободны</p>';
        }

        content.innerHTML = html;
        modal.style.display = 'flex';
    }

    function openCreateForm() {
        location.href = `create_booking.php?room_id=${currentRoomId}&check_in=${currentDate}`;
    }

    function closeModal() {
        document.getElementById('booking-modal').style.display = 'none';
    }
</script>

<style>
    .chess-table { border-collapse: separate; border-spacing: 0; width: 100%; }
    .chess-table th, .chess-table td { border: 1px solid #f0f0f0; padding: 0; height: 50px; text-align: center; }
    .chess-table th { background: #f8fafc; font-size: 0.75rem; color: #64748b; font-weight: 600; padding: 10px 5px; }

    .cal-cell { cursor: pointer; transition: 0.2s; position: relative; }
    .cal-cell:hover { filter: brightness(0.95); }

    .status-free { background: #fff; }
    .status-reserved { background: #fff3cd; color: #856404; }
    .status-full { background: #fee2e2; color: #991b1b; }

    .status-partial.gender-male { background: #e0f2fe; color: #0369a1; }
    .status-partial.gender-female { background: #fce7f3; color: #be185d; }

    .cell-info { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; }
    .gender-icon { font-size: 1.1rem; line-height: 1; margin-bottom: 2px; }
    .occupancy-ratio { font-size: 0.65rem; font-weight: 700; }

    .modal-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); backdrop-filter: blur(5px); z-index:2000; display:flex; align-items:center; justify-content:center; }

    .legend-item { display: flex; align-items: center; gap: 8px; }
    .legend-item .box { width: 14px; height: 14px; border-radius: 3px; border: 1px solid #ddd; }
    .box.free { background: #fff; }
    .box.reserved { background: #fff3cd; }
    .box.partial-male { background: #e0f2fe; }
    .box.partial-female { background: #fce7f3; }
    .box.full { background: #fee2e2; }
</style>

<?php include 'includes/footer.php'; ?>
