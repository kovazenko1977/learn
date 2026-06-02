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
$classMap = [];
foreach($classes as $c) $classMap[$c['id']] = $c['name'];

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

$settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true);
$colors = $settings['calendar_colors'] ?? [
    'free' => '#ffffff',
    'reserved' => '#fff3cd',
    'partial_male' => '#e0f2fe',
    'partial_female' => '#fce7f3',
    'full' => '#fee2e2'
];

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
                        if($count > 0) $gender = $dayBookings[0]['guest_gender'] ?? 'unknown';

                        $status = 'free';
                        if($count > 0) {
                            $status = ($count >= $totalCapacity) ? 'full' : 'partial';
                            if($dayBookings[0]['status'] === 'reserved') $status = 'reserved';
                        }

                        $cellClass = "cal-cell status-$status";
                        if($status === 'partial') $cellClass .= " gender-$gender";

                        $tooltip = "";
                        foreach($dayBookings as $db) {
                            $gChar = ($db['guest_gender'] ?? '') == 'male' ? 'М' : (($db['guest_gender'] ?? '') == 'female' ? 'Ж' : '?');
                            $tooltip .= ($db['client_name'] ?? 'Гость') . " ($gChar)\n";
                        }
                    ?>
                        <td class="<?php echo $cellClass; ?>"
                            onclick='handleCellClick(<?php echo $rid; ?>, "<?php echo $date; ?>", <?php echo htmlspecialchars(json_encode($dayBookings), ENT_QUOTES, "UTF-8"); ?>)'
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
    const roomDetails = <?php
        $rd = [];
        foreach($rooms as $r) {
            $rd[$r['id']] = [
                'number' => $r['room_number'],
                'class' => $classMap[$r['room_class_id']] ?? 'N/A',
                'main_seats' => $r['main_seats_count'] ?? 1,
                'extra_seats' => $r['extra_seats_count'] ?? 0,
                'price_main' => number_format($r['price_main'] ?? $r['price_per_day'] ?? 0, 0, ',', ' '),
                'price_extra' => number_format($r['price_extra'] ?? 0, 0, ',', ' '),
                'status' => $r['status'] ?? 'free'
            ];
        }
        echo json_encode($rd);
    ?>;
    const statusLabels = {
        'new': 'Новое',
        'reserved': 'Резерв',
        'booked': 'В номере',
        'confirmed': 'Завершено',
        'cancelled': 'Отменено'
    };

    let currentRoomId, currentDate;

    function handleCellClick(rid, date, bookings) {
        currentRoomId = rid;
        currentDate = date;
        const room = roomDetails[rid];
        const modal = document.getElementById('booking-modal');
        const content = document.getElementById('modal-content');

        let html = `
            <div style="background: rgba(0,120,212,0.05); padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(0,120,212,0.1);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0; color:var(--primary-color);">№${room.number} — ${room.class}</h4>
                    <span class="status-badge">${room.status}</span>
                </div>
                <div style="font-size:0.85rem; color:#666; display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div>Мест: <strong>${room.main_seats} + ${room.extra_seats}</strong></div>
                    <div>Дата: <strong>${date}</strong></div>
                    <div>Цена (осн): <strong>${room.price_main} ₽</strong></div>
                    <div>Цена (доп): <strong>${room.price_extra} ₽</strong></div>
                </div>
            </div>
        `;

        if(bookings.length > 0) {
            html += '<h4 style="margin-bottom:10px;">Текущие бронирования:</h4>';
            bookings.forEach(b => {
                const sLabel = statusLabels[b.status] || b.status;
                html += `
                <div style="padding:15px; background:white; border: 1px solid #eee; border-radius:12px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:1.05rem;">${b.client_name} ${b.guest_gender == 'male' ? '👨' : (b.guest_gender == 'female' ? '👩' : '👤')}</div>
                        <div style="font-size:0.8rem; color:#666; margin-top:4px;">
                            ${b.seat_type == 'extra' ? '🛋 Доп. место' : '🛏 Основное место'} |
                            <span class="status-badge status-${b.status}" style="font-size:0.7rem; padding: 2px 8px;">${sLabel}</span>
                        </div>
                        ${b.admin_notes ? `<div style="font-size:0.75rem; color:#d83b01; margin-top:8px; font-style:italic;">📝 ${b.admin_notes}</div>` : ''}
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="location.href='edit_booking.php?id=${b.id}'">Открыть</button>
                </div>`;
            });
        } else {
            html += '<p style="text-align:center; padding: 20px; color:#888; background:rgba(0,0,0,0.02); border-radius:12px;">На этот день места полностью свободны</p>';
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
    :root {
        --cal-free: <?php echo $colors['free']; ?>;
        --cal-reserved: <?php echo $colors['reserved']; ?>;
        --cal-partial-male: <?php echo $colors['partial_male']; ?>;
        --cal-partial-female: <?php echo $colors['partial_female']; ?>;
        --cal-full: <?php echo $colors['full']; ?>;
    }

    .chess-table { border-collapse: separate; border-spacing: 0; width: 100%; }
    .chess-table th, .chess-table td { border: 1px solid #f0f0f0; padding: 0; height: 50px; text-align: center; }
    .chess-table th { background: #f8fafc; font-size: 0.75rem; color: #64748b; font-weight: 600; padding: 10px 5px; }

    .cal-cell { cursor: pointer; transition: 0.2s; position: relative; }
    .cal-cell:hover { filter: brightness(0.95); }

    .status-free { background: var(--cal-free); }
    .status-reserved { background: var(--cal-reserved); color: #856404; }
    .status-full { background: var(--cal-full); color: #991b1b; }

    .status-partial.gender-male { background: var(--cal-partial-male); color: #0369a1; }
    .status-partial.gender-female { background: var(--cal-partial-female); color: #be185d; }

    .cell-info { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; }
    .gender-icon { font-size: 1.1rem; line-height: 1; margin-bottom: 2px; }
    .occupancy-ratio { font-size: 0.65rem; font-weight: 700; }

    .modal-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); backdrop-filter: blur(5px); z-index:2000; display:flex; align-items:center; justify-content:center; }

    .legend-item { display: flex; align-items: center; gap: 8px; }
    .legend-item .box { width: 14px; height: 14px; border-radius: 3px; border: 1px solid #ddd; }
    .box.free { background: var(--cal-free); }
    .box.reserved { background: var(--cal-reserved); }
    .box.partial-male { background: var(--cal-partial-male); }
    .box.partial-female { background: var(--cal-partial-female); }
    .box.full { background: var(--cal-full); }
</style>

<?php include 'includes/footer.php'; ?>
