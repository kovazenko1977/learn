<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');

$totalIncome = 0;
$statusCounts = ['new' => 0, 'confirmed' => 0, 'cancelled' => 0];
$roomPopularity = [];
$procedurePopularity = [];
$servicePopularity = [];

foreach ($bookings as $b) {
    if ($b['status'] !== 'cancelled') {
        $totalIncome += (float)$b['total_price'];
    }
    $statusCounts[$b['status']] = ($statusCounts[$b['status']] ?? 0) + 1;
    $roomPopularity[$b['room_id']] = ($roomPopularity[$b['room_id']] ?? 0) + 1;

    if (!empty($b['procedure_ids'])) {
        foreach($b['procedure_ids'] as $pid) {
            $procedurePopularity[$pid] = ($procedurePopularity[$pid] ?? 0) + 1;
        }
    }
    if (!empty($b['service_ids'])) {
        foreach($b['service_ids'] as $sid) {
            $servicePopularity[$sid] = ($servicePopularity[$sid] ?? 0) + 1;
        }
    }
}

arsort($roomPopularity);
arsort($procedurePopularity);
arsort($servicePopularity);

// Occupancy calculation for last 30 days
$totalSlots = count($rooms) * 30;
$occupiedSlots = 0;
$today = time();
for ($i = 0; $i < 30; $i++) {
    $date = date('Y-m-d', $today - ($i * 86400));
    foreach ($calendar as $entry) {
        if ($entry['date'] === $date && $entry['status'] !== 'free') {
            $occupiedSlots++;
        }
    }
}
$occupancyRate = $totalSlots > 0 ? round(($occupiedSlots / $totalSlots) * 100, 1) : 0;

$pageTitle = 'Аналитика и статистика';
include 'includes/header.php';
?>

<div class="grid-3">
    <div class="mica-card stat-card">
        <h3>💰 Общий доход</h3>
        <h2 style="color: #28a745;"><?php echo number_format($totalIncome, 0, ',', ' '); ?> ₽</h2>
    </div>
    <div class="mica-card stat-card">
        <h3>📈 Загруженность (30д)</h3>
        <h2 style="color: #0078d4;"><?php echo $occupancyRate; ?>%</h2>
    </div>
    <div class="mica-card stat-card">
        <h3>📊 Всего заявок</h3>
        <h2><?php echo count($bookings); ?></h2>
    </div>
</div>

<div class="grid-2">
    <div class="mica-card">
        <h3>🏆 Топ услуг</h3>
        <div style="margin-top: 20px;">
            <?php
            $servicesData = $store->findAll('extra_services');
            $svcMap = []; foreach($servicesData as $s) $svcMap[$s['id']] = $s['name'];
            $i = 1;
            foreach (array_slice($servicePopularity, 0, 5, true) as $id => $count): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <span><?php echo $i++; ?>. <?php echo htmlspecialchars($svcMap[$id] ?? "Service $id"); ?></span>
                    <strong><?php echo $count; ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if(empty($servicePopularity)) echo '<p style="color:#888;">Нет данных</p>'; ?>
        </div>
    </div>

    <div class="mica-card">
        <h3>🧪 Популярные процедуры</h3>
        <div style="margin-top: 20px;">
            <?php
            $procData = $store->findAll('procedures');
            $procMap = []; foreach($procData as $p) $procMap[$p['id']] = $p['name'];
            $i = 1;
            foreach (array_slice($procedurePopularity, 0, 5, true) as $id => $count): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <span><?php echo $i++; ?>. <?php echo htmlspecialchars($procMap[$id] ?? "Procedure $id"); ?></span>
                    <strong><?php echo $count; ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if(empty($procedurePopularity)) echo '<p style="color:#888;">Нет данных</p>'; ?>
        </div>
    </div>
</div>

<div class="mica-card" style="margin-top: 24px;">
    <h3>🏥 Загрузка по номерам (заявок)</h3>
    <div style="margin-top: 20px;">
        <?php
        $roomNames = []; foreach($rooms as $r) $roomNames[$r['id']] = $r['room_number'];
        foreach (array_slice($roomPopularity, 0, 10, true) as $id => $count):
            $percentage = count($bookings) > 0 ? ($count / count($bookings)) * 100 : 0;
        ?>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem;">
                    <span>Номер <?php echo htmlspecialchars($roomNames[$id] ?? $id); ?></span>
                    <span><?php echo $count; ?></span>
                </div>
                <div style="height: 8px; background: rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: var(--primary-color); width: <?php echo $percentage; ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
