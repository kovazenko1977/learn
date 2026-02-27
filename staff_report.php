<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\AnalyticsManager;
use Hop\Core\UserManager;

checkRole(['manager', 'admin']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Сотрудник не указан');

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);
$user = $userManager->getById($id);

if (!$user) die('Сотрудник не найден');

$requestStore = new JsonStore('data/requests.json');
$serviceStore = new JsonStore('data/services.json');
$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$analytics = new AnalyticsManager($requestStore, $serviceStore, $settings);
$report = $analytics->getPerformerReport($id, $startDate, $endDate);

$statusNames = [
    'new' => 'Новая', 'assigned' => 'Назначена', 'working' => 'В работе',
    'checking' => 'Проверка', 'returned' => 'Доработка', 'completed' => 'Выполнена', 'closed' => 'Закрыта'
];

$priorityColors = [
    'low' => '#107c10', 'medium' => '#ffaa44', 'high' => '#ff8c00', 'critical' => '#d13438'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <div class="header-action-row">
            <div style="display:flex; align-items:center; gap:16px;">
                <a href="analytics.php" class="btn-icon" style="text-decoration:none; color:inherit; background:rgba(0,0,0,0.05); border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                    <i data-lucide="arrow-left"></i>
                </a>
                <div>
                    <h1 style="margin:0;"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <div style="font-size:12px; color:var(--win-text-secondary); margin-top:2px;">
                        Отчет по личной эффективности за <?php echo $startDate ? "период с $startDate по $endDate" : "все время"; ?>
                    </div>
                </div>
            </div>
            <div class="header-buttons-block">
                <button onclick="window.print()" class="btn-secondary">
                    <i data-lucide="printer"></i> Печать отчета
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Widgets -->
    <div class="stats-grid" style="animation: slideUp 0.6s ease-out; margin-top: 24px;">
        <div class="card mica kpi-card">
            <div class="kpi-value" style="color: var(--win-accent);"><?php echo round($report['coefficients']['efficiency'] * 100); ?>%</div>
            <div class="kpi-label">Эффективность (КПД)</div>
            <div class="kpi-sub"><?php echo $report['completed']; ?> из <?php echo $report['total']; ?> заявок</div>
        </div>
        <div class="card mica kpi-card">
            <div class="kpi-value" style="color: #ffc107;"><?php echo $report['avg_rating']; ?></div>
            <div class="kpi-label">Качество работы</div>
            <div class="kpi-sub">Средняя оценка пользователей</div>
        </div>
        <div class="card mica kpi-card">
            <div class="kpi-value" style="color: #107c10;"><?php echo round($report['coefficients']['speed'] * 100); ?>%</div>
            <div class="kpi-label">Соблюдение SLA</div>
            <div class="kpi-sub"><?php echo $report['overdue']; ?> просрочек за период</div>
        </div>
        <div class="card mica kpi-card">
            <div class="kpi-value" style="color: #8e24aa;"><?php echo $report['avg_hours']; ?></div>
            <div class="kpi-label">Скорость (часы)</div>
            <div class="kpi-sub">Среднее время на заявку</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 24px; margin-top: 24px;">
        <!-- Activity Timeline -->
        <section class="card mica" style="padding: 32px; animation: slideUp 0.7s ease-out;">
            <h2 style="margin-top:0; font-size:18px; margin-bottom:24px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="activity" style="color:var(--win-accent);"></i> Журнал активности
            </h2>
            <div class="timeline" style="margin-left: 4px;">
                <?php if (empty($report['activity'])): ?>
                    <div style="text-align:center; padding: 40px; color:var(--win-text-secondary);">Активности за выбранный период не зафиксировано</div>
                <?php endif; ?>
                <?php foreach ($report['activity'] as $entry): ?>
                    <div class="timeline-item" style="padding-bottom: 24px;">
                        <div class="timeline-marker" style="width: 10px; height: 10px; border-width: 2px; top: 4px; border-color: var(--status-<?php echo $entry['status']; ?>);"></div>
                        <div class="timeline-content" style="margin-left: 20px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div>
                                    <div style="font-size: 11px; color: var(--win-text-secondary);"><?php echo date('d.m.Y H:i', strtotime($entry['timestamp'])); ?></div>
                                    <div style="font-size: 14px; font-weight: 700; margin: 2px 0;">
                                        Заявка <a href="view.php?id=<?php echo $entry['request_id']; ?>" style="color:var(--win-accent); text-decoration:none;">#<?php echo $entry['request_id']; ?></a>
                                        -> <span style="color:var(--status-<?php echo $entry['status']; ?>);"><?php echo $statusNames[$entry['status']] ?? $entry['status']; ?></span>
                                    </div>
                                    <?php if ($entry['comment']): ?>
                                        <div style="font-size:12px; font-style:italic; color:var(--win-text-secondary); margin-top:4px;">"<?php echo htmlspecialchars($entry['comment']); ?>"</div>
                                    <?php endif; ?>
                                </div>
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $priorityColors[$entry['priority']] ?? '#ccc'; ?>;" title="Приоритет: <?php echo $entry['priority']; ?>"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Right Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Geographic Footprint -->
            <section class="card mica" style="padding: 24px; animation: slideUp 0.8s ease-out;">
                <h3 style="margin-top:0; font-size:14px; text-transform: uppercase; color:var(--win-text-secondary); margin-bottom:16px;">География работ</h3>
                <div style="font-size: 13px;">
                    <div style="font-weight:700; margin-bottom:12px;">По корпусам:</div>
                    <?php
                    arsort($report['locations']['buildings']);
                    foreach ($report['locations']['buildings'] as $b => $count):
                    ?>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span>Корпус <?php echo htmlspecialchars($b); ?></span>
                            <span style="font-weight:700;"><?php echo $count; ?></span>
                        </div>
                        <div style="height:4px; background:rgba(0,0,0,0.05); border-radius:2px; margin-bottom:12px; overflow:hidden;">
                            <div style="height:100%; width:<?php echo ($count/$report['total'])*100; ?>%; background:var(--win-accent);"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Quality Distribution -->
            <section class="card mica" style="padding: 24px; animation: slideUp 0.9s ease-out;">
                <h3 style="margin-top:0; font-size:14px; text-transform: uppercase; color:var(--win-text-secondary); margin-bottom:16px;">Оценки пользователей</h3>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php for($i=5; $i>=1; $i--):
                        $c = $report['ratings_dist'][$i];
                        $p = $report['completed'] > 0 ? ($c / $report['completed']) * 100 : 0;
                    ?>
                        <div style="display:flex; align-items:center; gap:8px; font-size:12px;">
                            <div style="width:12px; font-weight:700;"><?php echo $i; ?></div>
                            <div style="flex:1; height:8px; background:rgba(0,0,0,0.05); border-radius:4px; overflow:hidden;">
                                <div style="height:100%; width:<?php echo $p; ?>%; background:<?php echo $i >= 4 ? '#107c10' : ($i == 3 ? '#ffaa44' : '#d13438'); ?>;"></div>
                            </div>
                            <div style="width:20px; text-align:right; opacity:0.6;"><?php echo $c; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
.kpi-card {
    text-align: center;
    padding: 24px;
}
.kpi-value {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 4px;
}
.kpi-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--win-text-secondary);
}
.kpi-sub {
    font-size: 11px;
    opacity: 0.6;
    margin-top: 4px;
}
@media (max-width: 900px) {
    .container > div { grid-template-columns: 1fr !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
