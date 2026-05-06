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
foreach($classes as $c) if(stripos($c['name'], 'Сауна') !== false) $saunaClassId = $c['id'];

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
    .booking-bar { background: var(--primary-color); color: white; padding: 4px; border-radius: 4px; font-size: 0.7rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 2px; }
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
            <input type="date" name="date" value="<?php echo $selectedDate; ?>" style="margin-bottom:0; width: auto;">
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
                                <div class="booking-bar status-<?php echo $b['status']; ?>" onclick="event.stopPropagation();">
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

<div style="margin-top: 20px; text-align: center;">
    <a href="../sauna_booking.php" class="btn btn-outline">Открыть публичную форму сауны</a>
</div>

<?php include 'includes/footer.php'; ?>
