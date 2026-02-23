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

$popularity = $analytics->getProcedurePopularity($startDate, $endDate);
$cabLoad = $analytics->getCabinetTimeLoad($startDate, $endDate);
$procStats = $analytics->getProcedureStats($startDate, $endDate);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <a href="reports.php" style="text-decoration: none; color: var(--win-text-secondary); font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width:16px; height:16px;"></i> Назад к отчетам
        </a>
        <h1 style="margin-top: 10px;">Отчет по процедурам</h1>
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
        <h2>Топ-10 популярных процедур</h2>
        <div style="height: 350px; margin-top: 20px;">
            <canvas id="popularityChart"></canvas>
        </div>
    </div>
    <div class="card mica-effect">
        <h2>Загрузка кабинетов (в минутах)</h2>
        <div style="height: 350px; margin-top: 20px;">
            <canvas id="cabinetChart"></canvas>
        </div>
    </div>
</div>

<div class="card mica-effect">
    <h2>Детальная статистика</h2>
    <table style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Наименование</th>
                <th style="text-align: center;">Назначено</th>
                <th style="text-align: center;">Выполнено</th>
                <th style="text-align: center;">% Выполнения</th>
                <?php if (\Medical\Core\Auth::can('finance_view')): ?>
                    <th style="text-align: right;">Выручка</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($procStats as $stat):
                $rate = $stat['total_records'] > 0 ? ($stat['attended'] / $stat['total_records']) * 100 : 0;
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                    <td style="text-align: center;"><?php echo $stat['total_records']; ?></td>
                    <td style="text-align: center;"><?php echo $stat['attended']; ?></td>
                    <td style="text-align: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex-grow: 1; height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $rate; ?>%; background: <?php echo $rate > 80 ? '#107c10' : ($rate > 50 ? '#ff8c00' : '#d13438'); ?>;"></div>
                            </div>
                            <span style="font-size: 0.8rem; width: 40px;"><?php echo round($rate); ?>%</span>
                        </div>
                    </td>
                    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
                        <td style="text-align: right; font-weight: 700;"><?php echo number_format($stat['revenue'], 0, ',', ' '); ?> ₽</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    // Popularity Chart
    const popCtx = document.getElementById('popularityChart').getContext('2d');
    new Chart(popCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($popularity)); ?>,
            datasets: [{
                label: 'Назначений',
                data: <?php echo json_encode(array_values($popularity)); ?>,
                backgroundColor: '#107c10',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                y: { grid: { display: false } }
            }
        }
    });

    // Cabinet Chart
    const cabCtx = document.getElementById('cabinetChart').getContext('2d');
    new Chart(cabCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($cabLoad)); ?>.map(c => 'Каб. ' + c),
            datasets: [{
                label: 'Минуты',
                data: <?php echo json_encode(array_values($cabLoad)); ?>,
                backgroundColor: '#ff8c00',
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
