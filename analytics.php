<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\AnalyticsManager;

checkRole(['manager', 'admin']);

$requestStore = new JsonStore('data/requests.json');
$serviceStore = new JsonStore('data/services.json');
$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();

$analytics = new AnalyticsManager($requestStore, $serviceStore, $settings);
$stats = $analytics->getStats();

$services = [];
foreach ($serviceStore->read() as $s) $services[$s['id']] = $s['name'];

$statusNames = [
    'new' => 'Новые', 'assigned' => 'Назначены', 'working' => 'В работе',
    'checking' => 'На проверке', 'returned' => 'На доработке', 'completed' => 'Выполнены', 'closed' => 'Закрыты'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; animation: slideDown 0.5s ease-out;">
        <div>
            <h1>Аналитическая панель</h1>
            <p style="color:var(--win-text-secondary);">Показатели эффективности и нагрузка служб</p>
        </div>
        <a href="export.php" class="btn-primary" style="text-decoration:none; display:flex; align-items:center; gap:8px;">
            <i class="lucide-download"></i> Экспорт в CSV
        </a>
    </div>

    <div class="stats-grid" style="animation: slideUp 0.6s ease-out;">
        <div class="stat-card mica">
            <div class="stat-icon" style="background: rgba(0, 120, 212, 0.1); color: var(--win-accent);">
                <i class="lucide-layers"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Всего заявок</div>
        </div>
        <div class="stat-card mica">
            <div class="stat-icon" style="background: rgba(232, 17, 35, 0.1); color: var(--priority-critical);">
                <i class="lucide-alert-triangle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['overdue']; ?></div>
            <div class="stat-label">Просрочено SLA</div>
        </div>
        <div class="stat-card mica">
            <div class="stat-icon" style="background: rgba(16, 124, 16, 0.1); color: var(--status-completed);">
                <i class="lucide-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['completed_count']; ?></div>
            <div class="stat-label">Выполнено</div>
        </div>
        <div class="stat-card mica">
            <div class="stat-icon" style="background: rgba(0, 120, 212, 0.1); color: var(--win-accent);">
                <i class="lucide-clock"></i>
            </div>
            <div class="stat-value"><?php echo $stats['avg_hours']; ?><small style="font-size: 14px; margin-left: 2px;">ч</small></div>
            <div class="stat-label">Ср. время</div>
        </div>
    </div>

    <div class="form-grid" style="margin-top: 24px; animation: slideUp 0.7s ease-out;">
        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <i class="lucide-pie-chart" style="color:var(--win-accent);"></i> Статусы заявок
            </h2>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($statusNames as $code => $name):
                    $count = $stats['by_status'][$code] ?? 0;
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                ?>
                    <div style="margin-bottom: 12px;">
                        <div style="display:flex; justify-content:space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="font-weight: 500;"><?php echo $name; ?></span>
                            <span style="font-weight: 700;"><?php echo $count; ?></span>
                        </div>
                        <div style="height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--status-<?php echo $code; ?>);"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <i class="lucide-bar-chart-3" style="color:var(--win-accent);"></i> Нагрузка на службы
            </h2>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($services as $id => $name):
                    $count = $stats['by_service'][$id] ?? 0;
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                ?>
                    <div style="display:flex; align-items:center; gap:12px; padding: 10px; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid var(--win-border);">
                        <div style="flex: 1; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($name); ?></div>
                        <div style="font-weight: 800; font-size: 16px; color: var(--win-accent);"><?php echo $count; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <section class="card mica" style="margin-top: 24px; animation: slideUp 0.8s ease-out; padding: 40px; text-align: center;">
        <h2 style="margin-top:0;">Краткий отчет по эффективности</h2>
        <div style="display: flex; justify-content: center; gap: 48px; margin-top: 32px;">
             <div>
                <div style="font-size: 48px; font-weight: 800; color: var(--win-accent); letter-spacing: -2px;">
                    <?php echo $stats['avg_hours']; ?>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--win-text-secondary); text-transform: uppercase;">Среднее время (часы)</div>
             </div>
             <div style="width: 1px; background: var(--win-border);"></div>
             <div>
                <div style="font-size: 48px; font-weight: 800; color: var(--status-completed); letter-spacing: -2px;">
                    <?php
                        $efficiency = $stats['total'] > 0 ? round(($stats['completed_count'] / $stats['total']) * 100) : 0;
                        echo $efficiency;
                    ?><small style="font-size: 24px;">%</small>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--win-text-secondary); text-transform: uppercase;">Процент выполнения</div>
             </div>
        </div>
    </section>
</div>

<style>
.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}
.stat-icon i { width: 20px; height: 20px; }
</style>

<?php include 'includes/footer.php'; ?>
