<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');
$roomMap = [];
foreach ($rooms as $r) { if (isset($r['id'])) $roomMap[$r['id']] = $r['room_number']; }

// Sort bookings by date descending
usort($bookings, function($a, $b) {
    return strcmp($b['check_in'] ?? '', $a['check_in'] ?? '');
});

$pageTitle = 'Бронирования';
include 'includes/header.php';
?>

<div class="m-card" style="padding: 10px;">
    <input type="text" id="m-booking-search" placeholder="Поиск по имени или номеру..." class="form-control" style="margin-bottom: 0; padding: 10px; font-size: 0.9rem;" onkeyup="filterBookings()">
</div>

<div id="m-bookings-list" style="padding-bottom: 80px;">
    <?php foreach ($bookings as $b): ?>
        <div class="m-card booking-item" style="padding: 12px; margin-bottom: 12px;"
             data-search="<?php echo mb_strtolower(($b['client_name'] ?? '') . ' ' . ($roomMap[$b['room_id'] ?? 0] ?? '')); ?>">
            <div style="display: flex; justify-content: space-between;">
                <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($b['client_name'] ?? 'N/A'); ?></div>
                <div style="font-weight: 700; color: var(--mobile-primary);">№<?php echo $roomMap[$b['room_id'] ?? 0] ?? '?'; ?></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 0.8rem; color: #64748b;">
                <span><?php echo $b['check_in'] ?? ''; ?> — <?php echo $b['check_out'] ?? ''; ?></span>
                <span class="badge-m <?php echo (($b['status'] ?? '') === 'cancelled') ? 'badge-m-danger' : ((($b['status'] ?? '') === 'booked') ? 'badge-m-success' : 'badge-m-warning'); ?>">
                    <?php
                        $labels = ['reserved' => 'Резерв', 'booked' => 'Занят', 'cancelled' => 'Отмена', 'new' => 'Новый'];
                        echo $labels[$b['status'] ?? 'new'] ?? ($b['status'] ?? 'new');
                    ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function filterBookings() {
    const q = document.getElementById('m-booking-search').value.toLowerCase();
    const items = document.querySelectorAll('.booking-item');
    items.forEach(item => {
        const text = item.getAttribute('data-search');
        item.style.display = text.includes(q) ? 'block' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
