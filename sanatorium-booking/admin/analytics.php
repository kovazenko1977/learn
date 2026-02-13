<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Analytics\AnalyticsManager;

$store = new JsonStore(__DIR__ . '/../data');
$analyticsManager = new AnalyticsManager($store);
$stats = $analyticsManager->getStats();

$totalIncome = $stats['totalIncome'];
$occupancyRate = $stats['occupancyRate'];
$totalBookings = $stats['totalBookings'];
$servicePopularity = $stats['servicePopularity'];
$procedurePopularity = $stats['procedurePopularity'];
$roomPopularity = $stats['roomPopularity'];

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
        <h2><?php echo $totalBookings; ?></h2>
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
        $rooms = $store->findAll('rooms');
        $roomNames = []; foreach($rooms as $r) $roomNames[$r['id']] = $r['room_number'];
        foreach (array_slice($roomPopularity, 0, 10, true) as $id => $count):
            $percentage = $totalBookings > 0 ? ($count / $totalBookings) * 100 : 0;
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
