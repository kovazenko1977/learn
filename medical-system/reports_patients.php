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

$ageStats = $analytics->getAgeStats();
$dailyRegs = $analytics->getDailyRegistrations($startDate, $endDate);
$mkbStats = $analytics->getMkbStats();
$durationStats = $analytics->getStayDurationStats();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <a href="reports.php" style="text-decoration: none; color: var(--win-text-secondary); font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width:16px; height:16px;"></i> Назад к отчетам
        </a>
        <h1 style="margin-top: 10px;">Отчет по пациентам</h1>
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h2>Динамика регистраций</h2>
        <div style="height: 300px; margin-top: 20px;">
            <canvas id="regsChart"></canvas>
        </div>
    </div>
    <div class="card mica-effect">
        <h2>Возрастные группы</h2>
        <div style="height: 300px; margin-top: 20px;">
            <canvas id="ageChart"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card mica-effect">
        <h2>Структура заболеваний (МКБ-10)</h2>
        <div style="margin-top: 20px;">
            <?php foreach ($mkbStats as $code => $data): ?>
                <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--win-border);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span style="font-family: monospace; font-weight: 700; color: var(--win-accent); background: rgba(0,120,212,0.05); padding: 2px 6px; border-radius: 4px;"><?php echo $code; ?></span>
                            <div style="font-size: 0.85rem; margin-top: 4px; color: var(--win-text-secondary);"><?php echo htmlspecialchars($data['text']); ?></div>
                        </div>
                        <div style="font-weight: 700; font-size: 1.1rem;"><?php echo $data['count']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card mica-effect">
        <h2>Длительность лечения</h2>
        <div style="height: 250px; margin-top: 20px;">
            <canvas id="durationChart"></canvas>
        </div>
        <div style="margin-top: 20px; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px;">
            <p style="font-size: 0.9rem; color: var(--win-text-secondary); margin: 0;">Распределение пациентов по количеству дней от первой до последней процедуры.</p>
        </div>
    </div>
</div>

<script>
    // Registration Chart
    const regsCtx = document.getElementById('regsChart').getContext('2d');
    new Chart(regsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($dailyRegs)); ?>,
            datasets: [{
                label: 'Регистраций',
                data: <?php echo json_encode(array_values($dailyRegs)); ?>,
                borderColor: '#0078d4',
                backgroundColor: 'rgba(0,120,212,0.1)',
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

    // Age Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    new Chart(ageCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($ageStats)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($ageStats)); ?>,
                backgroundColor: ['#0078d4', '#107c10', '#ff8c00', '#d13438']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Duration Chart
    const durationCtx = document.getElementById('durationChart').getContext('2d');
    new Chart(durationCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($durationStats)); ?>.map(d => d + ' дн.'),
            datasets: [{
                label: 'Пациентов',
                data: <?php echo json_encode(array_values($durationStats)); ?>,
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
