<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('finance_view')) {
    die("Доступ ограничен");
}

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$summary = $analytics->getSummary($startDate, $endDate);
$dailyRevenue = $analytics->getDailyRevenue($startDate, $endDate);
$procStats = $analytics->getProcedureStats($startDate, $endDate);

// Revenue by procedure for doughnut
$revLabels = [];
$revData = [];
foreach ($procStats as $stat) {
    if ($stat['revenue'] > 0) {
        $revLabels[] = $stat['name'];
        $revData[] = $stat['revenue'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <a href="reports.php" style="text-decoration: none; color: var(--win-text-secondary); font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width:16px; height:16px;"></i> Назад к отчетам
        </a>
        <h1 style="margin-top: 10px;">Финансовый отчет (Касса)</h1>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="btn" onclick="window.print()"><i data-lucide="printer" class="icon"></i> Печать</button>
    </div>
</div>

<div class="card mica-effect" style="margin-bottom: 24px;">
    <form method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
        <div>
            <label style="display:block; margin-bottom: 8px;">Начало периода</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div>
            <label style="display:block; margin-bottom: 8px;">Конец периода</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Обновить</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Общая выручка</h3>
        <div style="font-size: 2rem; font-weight: 700; color: #107c10;"><?php echo number_format($summary['total_revenue'], 0, ',', ' '); ?> ₽</div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Кол-во оплат</h3>
        <?php
            $paidCount = 0;
            foreach($procStats as $s) $paidCount += ($s['total_records'] - $s['unpaid']); // Approximation if free are counted differently
            // Actually let's just count paid status in summary if we had it.
            // Summary only has total appointments.
            // Let's use procStats to sum up (total - unpaid - free)
        ?>
        <div style="font-size: 2rem; font-weight: 700;"><?php echo count($dailyRevenue); ?> дн. с выручкой</div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Ср. выручка в день</h3>
        <div style="font-size: 2rem; font-weight: 700;">
            <?php
                $days = count($dailyRevenue) ?: 1;
                echo number_format($summary['total_revenue'] / $days, 0, ',', ' ');
            ?> ₽
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h2>Динамика выручки</h2>
        <div style="height: 350px; margin-top: 20px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    <div class="card mica-effect">
        <h2>Доля в выручке</h2>
        <div style="height: 350px; margin-top: 20px;">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<div class="card mica-effect">
    <h2>Выручка по процедурам</h2>
    <table style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Процедура</th>
                <th style="text-align: right;">Оплачено раз</th>
                <th style="text-align: right;">Ожидает оплаты</th>
                <th style="text-align: right;">Сумма выручки</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($procStats as $stat): if($stat['revenue'] == 0 && $stat['unpaid'] == 0) continue; ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                    <td style="text-align: right;"><?php echo ($stat['total_records'] - $stat['unpaid']); ?></td>
                    <td style="text-align: right; color: #d13438;"><?php echo $stat['unpaid']; ?></td>
                    <td style="text-align: right; font-weight: 700; color: #107c10;"><?php echo number_format($stat['revenue'], 0, ',', ' '); ?> ₽</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    // Revenue Chart
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($dailyRevenue)); ?>,
            datasets: [{
                label: 'Выручка',
                data: <?php echo json_encode(array_values($dailyRevenue)); ?>,
                borderColor: '#107c10',
                backgroundColor: 'rgba(16,124,16,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($revLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($revData); ?>,
                backgroundColor: ['#0078d4', '#107c10', '#ff8c00', '#d13438', '#8b44d5', '#038387', '#004e8c', '#498205']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
