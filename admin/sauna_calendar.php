<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

$selectedDate = $_GET['date'] ?? date('Y-m-d');

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
$saunaClassId = null;
foreach($classes as $c) {
    if (stripos($c['name'], 'Сауна') !== false) {
        $saunaClassId = $c['id'];
        break;
    }
}

$saunas = array_filter($rooms, function($r) use ($saunaClassId) {
    return ($r['room_class_id'] == $saunaClassId);
});

$bookings = $store->findAll('bookings');
$hourlyBookings = [];
if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (!is_array($b) || ($b['status'] ?? '') === 'cancelled') continue;

        $isForSauna = false;
        foreach($saunas as $s) if($s['id'] == $b['room_id']) $isForSauna = true;
        if (!$isForSauna) continue;

        $bStart = strtotime($b['check_in']);
        $bEnd = strtotime($b['check_out']);
        $dayStart = strtotime($selectedDate . ' 00:00:00');
        $dayEnd = strtotime($selectedDate . ' 23:59:59');

        if ($bStart <= $dayEnd && $bEnd >= $dayStart) {
            $hourlyBookings[] = $b;
        }
    }
}

$pageTitle = 'График Сауны (по часам)';
include 'includes/header.php';
?>

<style>
    .sauna-grid-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .sauna-grid-table th { background: #f1f5f9; padding: 15px; font-weight: 700; color: #475569; border-bottom: 2px solid #e2e8f0; }
    .sauna-grid-table td { border: 1px solid #e2e8f0; height: 60px; vertical-align: top; padding: 5px; }
    .time-col { width: 80px; background: #f8fafc; font-weight: 800; color: #64748b; text-align: center; vertical-align: middle !important; }
    .booking-block { background: var(--primary-color); color: white; padding: 6px 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid rgba(0,0,0,0.2); }
    .booking-block.status-confirmed { background: #107c10; }
    .booking-block.status-booked { background: #d83b01; }
    .current-time-row { background: rgba(0, 120, 212, 0.05); }
</style>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <div>
            <h2 style="margin:0;">🧖‍♀️ Расписание сауны</h2>
            <p style="color: #666; margin: 5px 0 0 0;"><?php echo date('d F Y', strtotime($selectedDate)); ?></p>
        </div>
        <form method="get" style="display:flex; gap:12px; align-items: center;">
            <input type="date" name="date" value="<?php echo $selectedDate; ?>" style="margin-bottom:0; border-radius: 8px; padding: 8px 12px;">
            <button type="submit" class="btn btn-primary">Показать дату</button>
            <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary">Сегодня</a>
        </form>
    </div>

    <div style="overflow-x: auto; margin: -10px;">
        <div style="padding: 10px;">
            <table class="sauna-grid-table" style="min-width: <?php echo (count($saunas) * 220 + 80); ?>px;">
                <thead>
                    <tr>
                        <th class="time-col">Время</th>
                        <?php foreach($saunas as $s): ?>
                            <th>
                                <div style="font-size: 1.1rem;"><?php echo htmlspecialchars($s['room_number']); ?></div>
                                <div style="font-weight: normal; font-size: 0.75rem; color: #64748b; margin-top: 4px;">Макс: <?php echo $s['capacity']; ?> чел.</div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $currentHour = (int)date('H');
                    $isToday = ($selectedDate === date('Y-m-d'));

                    for ($h = 7; $h <= 23; $h++):
                        $timeLabel = sprintf('%02d:00', $h);
                        $slotStart = strtotime($selectedDate . " $h:00:00");
                        $slotEnd = $slotStart + 3600;
                        $isCurrent = ($isToday && $h === $currentHour);
                    ?>
                    <tr class="<?php echo $isCurrent ? 'current-time-row' : ''; ?>">
                        <td class="time-col">
                            <?php echo $timeLabel; ?>
                            <?php if ($isCurrent): ?>
                                <div style="font-size: 0.6rem; color: var(--primary-color);">СЕЙЧАС</div>
                            <?php endif; ?>
                        </td>
                        <?php foreach($saunas as $s): ?>
                            <td onclick="location.href='create_booking.php?room_id=<?php echo $s['id']; ?>&date=<?php echo $selectedDate; ?>T<?php echo sprintf('%02d:00', $h); ?>'">
                                <?php
                                foreach ($hourlyBookings as $b):
                                    if ($b['room_id'] != $s['id']) continue;
                                    $bStart = strtotime($b['check_in']);
                                    $bEnd = strtotime($b['check_out']);

                                    if ($bStart < $slotEnd && $bEnd > $slotStart):
                                ?>
                                    <div class="booking-block status-<?php echo $b['status']; ?>" onclick="event.stopPropagation();">
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($b['client_name']); ?></div>
                                        <div style="display:flex; justify-content:space-between; align-items: center; margin-top: 2px; opacity: 0.9;">
                                            <span><?php echo date('H:i', $bStart); ?> - <?php echo date('H:i', $bEnd); ?></span>
                                            <span><?php echo $b['persons']; ?> чел.</span>
                                        </div>
                                    </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 0.85rem; color: #666;">
        <div style="display: flex; align-items: center; gap: 6px;">
            <div style="width: 12px; height: 12px; background: var(--primary-color); border-radius: 3px;"></div> Ожидается
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <div style="width: 12px; height: 12px; background: #d83b01; border-radius: 3px;"></div> В сауне
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <div style="width: 12px; height: 12px; background: #107c10; border-radius: 3px;"></div> Завершено
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
