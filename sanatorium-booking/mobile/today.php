<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');

$roomMap = [];
foreach ($rooms as $r) {
    if (isset($r['id'])) $roomMap[$r['id']] = $r['room_number'];
}

$today = date('Y-m-d');
$arrivals = [];
$departures = [];

foreach ($bookings as $b) {
    if (($b['check_in'] ?? '') === $today && ($b['status'] ?? '') !== 'cancelled') {
        $arrivals[] = $b;
    }
    if (($b['check_out'] ?? '') === $today && ($b['status'] ?? '') !== 'cancelled') {
        $departures[] = $b;
    }
}

$pageTitle = 'Сегодня';
include 'includes/header.php';
?>

<div style="margin-bottom: 20px; display: flex; gap: 10px;">
    <button class="btn-m btn-m-primary" style="flex: 1; font-size: 0.8rem; padding: 8px;" onclick="showTab('arrivals')">Заезды (<?php echo count($arrivals); ?>)</button>
    <button class="btn-m" style="flex: 1; font-size: 0.8rem; padding: 8px; background: #e2e8f0; color: #1e293b;" onclick="showTab('departures')">Выезды (<?php echo count($departures); ?>)</button>
</div>

<div id="arrivals-tab">
    <h3 style="font-size: 0.9rem; color: #64748b; margin-bottom: 12px;">Гости на заезд</h3>
    <?php if (empty($arrivals)): ?>
        <p style="text-align: center; color: #94a3b8; font-size: 0.9rem; margin-top: 40px;">Нет заездов на сегодня</p>
    <?php endif; ?>
    <?php foreach ($arrivals as $b): ?>
        <div class="m-card" style="padding: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($b['client_name'] ?? 'N/A'); ?></div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;"><?php echo $b['phone'] ?? ''; ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: var(--mobile-primary);">№<?php echo $roomMap[$b['room_id'] ?? 0] ?? '?'; ?></div>
                    <span class="badge-m <?php echo ($b['status'] === 'reserved') ? 'badge-m-warning' : 'badge-m-success'; ?>">
                        <?php echo ($b['status'] === 'reserved') ? 'Резерв' : 'Занят'; ?>
                    </span>
                </div>
            </div>
            <div style="margin-top: 12px; border-top: 1px solid #f1f5f9; pt: 10px;">
                <a href="../admin/today.php" class="btn-m btn-m-primary" style="font-size: 0.75rem; padding: 6px;">Управление</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="departures-tab" style="display: none;">
    <h3 style="font-size: 0.9rem; color: #64748b; margin-bottom: 12px;">Гости на выезд</h3>
    <?php if (empty($departures)): ?>
        <p style="text-align: center; color: #94a3b8; font-size: 0.9rem; margin-top: 40px;">Нет выездов на сегодня</p>
    <?php endif; ?>
    <?php foreach ($departures as $b): ?>
        <div class="m-card" style="padding: 12px; border-left: 4px solid #f97316;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($b['client_name'] ?? 'N/A'); ?></div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;"><?php echo $b['phone'] ?? ''; ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #f97316;">№<?php echo $roomMap[$b['room_id'] ?? 0] ?? '?'; ?></div>
                    <span class="badge-m badge-m-success">Проживает</span>
                </div>
            </div>
            <div style="margin-top: 12px; border-top: 1px solid #f1f5f9; pt: 10px;">
                <a href="../admin/today.php" class="btn-m btn-m-primary" style="font-size: 0.75rem; padding: 6px; background: #f97316;">Оформить выезд</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function showTab(tab) {
    document.getElementById('arrivals-tab').style.display = (tab === 'arrivals' ? 'block' : 'none');
    document.getElementById('departures-tab').style.display = (tab === 'departures' ? 'block' : 'none');
}
</script>

<?php include 'includes/footer.php'; ?>
