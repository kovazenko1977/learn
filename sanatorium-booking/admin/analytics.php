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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="rooms.php">Номера</a>
            <a href="procedures.php">Процедуры</a>
            <a href="services.php">Услуги</a>
            <a href="packages.php">Пакеты</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
            <a href="text_blocks.php">Тексты</a>
        </nav>
    </header>
    <main>
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:30px;">
            <div class="mica-card" style="text-align:center;">
                <h3>Общий доход</h3>
                <h2 style="color:#28a745;"><?php echo number_format($totalIncome, 0, ',', ' '); ?> ₽</h2>
            </div>
            <div class="mica-card" style="text-align:center;">
                <h3>Загруженность (30д)</h3>
                <h2 style="color:#007bff;"><?php echo $occupancyRate; ?>%</h2>
            </div>
            <div class="mica-card" style="text-align:center;">
                <h3>Всего заявок</h3>
                <h2><?php echo count($bookings); ?></h2>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <div class="mica-card">
                <h3>🏆 Популярность услуг</h3>
                <ol>
                    <?php
                    $servicesData = $store->findAll('extra_services');
                    $svcMap = []; foreach($servicesData as $s) $svcMap[$s['id']] = $s['name'];
                    foreach (array_slice($servicePopularity, 0, 5, true) as $id => $count): ?>
                        <li><?php echo htmlspecialchars($svcMap[$id] ?? "Service $id"); ?>: <strong><?php echo $count; ?></strong></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="mica-card">
                <h3>🧬 Популярность процедур</h3>
                <ol>
                    <?php
                    $procData = $store->findAll('procedures');
                    $procMap = []; foreach($procData as $p) $procMap[$p['id']] = $p['name'];
                    foreach (array_slice($procedurePopularity, 0, 5, true) as $id => $count): ?>
                        <li><?php echo htmlspecialchars($procMap[$id] ?? "Procedure $id"); ?>: <strong><?php echo $count; ?></strong></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </main>
</body>
</html>
