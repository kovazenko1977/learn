<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedRoomId = $_GET['room_id'] ?? null;

$rooms = $store->findAll('rooms');
$bookings = $store->findAll('bookings');

$hourlyBookings = [];
if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;
        if ($selectedRoomId && $b['room_id'] != $selectedRoomId) continue;

        $bStart = strtotime($b['check_in']);
        $bEnd = strtotime($b['check_out']);
        $dayStart = strtotime($selectedDate . ' 00:00:00');
        $dayEnd = strtotime($selectedDate . ' 23:59:59');

        if ($bStart <= $dayEnd && $bEnd >= $dayStart) {
            $hourlyBookings[] = $b;
        }
    }
}

$pageTitle = 'Почасовой график';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>🕰 График на <?php echo date('d.m.Y', strtotime($selectedDate)); ?></h2>
        <form method="get" style="display:flex; gap:10px;">
            <input type="date" name="date" value="<?php echo $selectedDate; ?>">
            <select name="room_id">
                <option value="">Все ресурсы</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo $selectedRoomId == $r['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['room_number']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Показать</button>
        </form>
    </div>

    <div class="hourly-grid" style="display: grid; grid-template-columns: 80px 1fr; border: 1px solid #eee;">
        <?php for ($h = 0; $h < 24; $h++):
            $timeLabel = sprintf('%02d:00', $h);
            $slotStart = strtotime($selectedDate . " $h:00:00");
            $slotEnd = $slotStart + 3600;
        ?>
            <div style="padding: 10px; border-bottom: 1px solid #eee; background: #f9f9f9; font-weight: bold;">
                <?php echo $timeLabel; ?>
            </div>
            <div style="padding: 10px; border-bottom: 1px solid #eee; position: relative; min-height: 40px;">
                <?php
                foreach ($hourlyBookings as $b):
                    $bStart = strtotime($b['check_in']);
                    $bEnd = strtotime($b['check_out']);
                    if ($bStart < $slotEnd && $bEnd > $slotStart):
                        $room = null;
                        foreach($rooms as $r) if($r['id'] == $b['room_id']) $room = $r;
                ?>
                    <div style="background: rgba(0,120,212,0.1); border-left: 3px solid #0078d4; padding: 5px 10px; margin-bottom: 5px; font-size: 0.85rem; border-radius: 4px;">
                        <strong><?php echo htmlspecialchars($room['room_number'] ?? 'N/A'); ?></strong>:
                        <?php echo htmlspecialchars($b['client_name']); ?>
                        (<?php echo date('H:i', $bStart); ?> - <?php echo date('H:i', $bEnd); ?>)
                    </div>
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
