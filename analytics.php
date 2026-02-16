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
    'new' => 'Новые',
    'assigned' => 'Назначены',
    'working' => 'В работе',
    'checking' => 'На проверке',
    'returned' => 'На доработке',
    'completed' => 'Выполнены',
    'closed' => 'Закрыты'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Аналитика</h1>
        <a href="export.php" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
            <i data-lucide="download"></i>
            <span>Экспорт CSV</span>
        </a>
    </div>

    <section class="card mica">
        <h2>Общая статистика</h2>
        <div style="display:flex; gap:32px;">
            <div>
                <div style="font-size: 32px; font-weight: 700; color: var(--win-accent);">
                    <?php echo $stats['total']; ?>
                </div>
                <div style="font-size: 14px; color: var(--win-text-secondary);">всего заявок</div>
            </div>
            <div>
                <div style="font-size: 32px; font-weight: 700; color: var(--priority-critical);">
                    <?php echo $stats['overdue']; ?>
                </div>
                <div style="font-size: 14px; color: var(--win-text-secondary);">просрочено SLA</div>
            </div>
        </div>
    </section>

    <div class="form-grid">
        <section class="card">
            <h2>По статусам</h2>
            <?php foreach ($statusNames as $code => $name): ?>
                <?php $count = $stats['by_status'][$code] ?? 0; ?>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--win-border);">
                    <span><?php echo $name; ?></span>
                    <span class="badge status-<?php echo $code; ?>"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="card">
            <h2>По службам</h2>
            <?php foreach ($services as $id => $name): ?>
                <?php $count = $stats['by_service'][$id] ?? 0; ?>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--win-border);">
                    <span><?php echo $name; ?></span>
                    <span style="font-weight:600;"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <section class="card">
        <h2>Эффективность</h2>
        <div class="form-grid">
            <div>
                <div style="font-size: 32px; font-weight: 700; color: var(--win-accent);">
                    <?php echo $stats['avg_hours']; ?> ч.
                </div>
                <div style="font-size: 14px; color: var(--win-text-secondary);">ср. время выполнения</div>
            </div>
            <div>
                <div style="font-size: 32px; font-weight: 700; color: var(--status-completed);">
                    <?php echo $stats['completed_count']; ?>
                </div>
                <div style="font-size: 14px; color: var(--win-text-secondary);">выполнено заявок</div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
