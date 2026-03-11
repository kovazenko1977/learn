<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('analytics_view')) {
    die("Доступ ограничен");
}

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$docPerf = $analytics->getDoctorPerformance($startDate, $endDate);

$labels = array_keys($docPerf);
$counts = array_map(function($d) { return $d['count']; }, $docPerf);
$revenues = array_map(function($d) { return $d['revenue']; }, $docPerf);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <a href="reports.php" style="text-decoration: none; color: var(--win-text-secondary); font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width:16px; height:16px;"></i> Назад к отчетам
        </a>
        <h1 style="margin-top: 10px;">Отчет по врачам</h1>
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
        <div style="border-left: 1px solid var(--win-border); padding-left: 20px; display: flex; gap: 15px; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom: 8px;">Дней вперед</label>
                <input type="number" id="days_ahead" min="0" max="365" placeholder="0" style="width: 80px;">
            </div>
            <div style="padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="save_period" style="width: 18px; height: 18px; cursor: pointer;">
                <label for="save_period" style="font-size: 0.85rem; cursor: pointer;">Запомнить</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Обновить</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h2>Нагрузка врачей (кол-во назначений)</h2>
        <div style="height: 350px; margin-top: 20px;">
            <canvas id="docCountChart"></canvas>
        </div>
    </div>
    <div class="card mica-effect">
        <h2>Финансовый вклад (выручка от назначений)</h2>
        <div style="height: 350px; margin-top: 20px;">
            <canvas id="docRevenueChart"></canvas>
        </div>
    </div>
</div>

<div class="card mica-effect">
    <h2>Сводная таблица эффективности</h2>
    <table style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Врач</th>
                <th style="text-align: center;">Всего назначений</th>
                <th style="text-align: right;">Принесенная выручка</th>
                <th style="text-align: right;">Ср. чек назначения</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($docPerf as $name => $data): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                    <td style="text-align: center;"><?php echo $data['count']; ?></td>
                    <td style="text-align: right; font-weight: 700; color: #107c10;"><?php echo number_format($data['revenue'], 0, ',', ' '); ?> ₽</td>
                    <td style="text-align: right;">
                        <?php echo number_format($data['revenue'] / ($data['count'] ?: 1), 0, ',', ' '); ?> ₽
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    // Count Chart
    const countCtx = document.getElementById('docCountChart').getContext('2d');
    new Chart(countCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Назначений',
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: '#8b44d5',
                borderRadius: 4
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

    // Revenue Chart
    const revCtx = document.getElementById('docRevenueChart').getContext('2d');
    new Chart(revCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Выручка',
                data: <?php echo json_encode($revenues); ?>,
                backgroundColor: '#0078d4',
                borderRadius: 4
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
