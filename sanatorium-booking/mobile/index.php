<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Analytics\AnalyticsManager;

$store = new JsonStore(__DIR__ . '/../data');
$analytics = new AnalyticsManager($store);
$stats = $analytics->getStats();

$pageTitle = 'Дашборд';
include 'includes/header.php';
?>

<div class="m-card">
    <h2>📊 Статистика</h2>
    <div class="m-stats-grid">
        <div class="m-stat-item" style="grid-column: span 2;">
            <div class="m-stat-value"><?php echo number_format($stats['totalIncome'] ?? 0, 0, '.', ' '); ?></div>
            <div class="m-stat-label">Общий доход (BYN)</div>
        </div>
        <div class="m-stat-item">
            <div class="m-stat-value"><?php echo $stats['occupancyRate'] ?? 0; ?>%</div>
            <div class="m-stat-label">Загрузка (30д)</div>
        </div>
        <div class="m-stat-item">
            <div class="m-stat-value"><?php echo $stats['totalBookings'] ?? 0; ?></div>
            <div class="m-stat-label">Всего броней</div>
        </div>
    </div>
</div>

<div class="m-card">
    <h2>🚀 Быстрые действия</h2>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <a href="today.php" class="btn-m btn-m-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            Заезды и выезды
        </a>
        <a href="calendar.php" class="btn-m" style="background: #e2e8f0; color: #1e293b;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
            Список бронирований
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
