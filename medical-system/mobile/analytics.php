<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('analytics_view')) {
    die("У вас недостаточно прав.");
}

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$date = date('Y-m-d');
$summary = $analytics->getSummary($date, $date);
$revenue = $analytics->getRevenueByDateRange($date, $date);
$procPopularity = $analytics->getProcedurePopularity($date, $date);

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; background: #F3EDF7;">
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Аналитика сегодня</h2>
    <div style="font-size: 14px; color: var(--md-secondary); margin-top: 4px;"><?php echo date('d.m.Y'); ?></div>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <div class="md-card" style="margin: 0; background: #D0E1FF; border: none;">
            <div style="font-size: 11px; color: #001D35; font-weight: 700; text-transform: uppercase;">Процедур</div>
            <div style="font-size: 28px; font-weight: 700; margin-top: 4px;"><?php echo $summary['total_appointments']; ?></div>
        </div>
        <div class="md-card" style="margin: 0; background: #DFF6DD; border: none;">
            <div style="font-size: 11px; color: #107C10; font-weight: 700; text-transform: uppercase;">Выполнено</div>
            <div style="font-size: 28px; font-weight: 700; margin-top: 4px;"><?php echo $summary['attended_count']; ?></div>
        </div>
    </div>

    <div class="md-card" style="margin: 0 0 16px 0; background: #E8DEF8; border: none;">
        <div style="font-size: 11px; color: #21005D; font-weight: 700; text-transform: uppercase;">Выручка</div>
        <div style="font-size: 32px; font-weight: 700; margin-top: 4px;"><?php echo number_format($revenue, 2); ?> ₽</div>
    </div>

    <div class="md-card" style="margin: 0;">
        <h3 style="margin-top: 0; font-size: 16px; font-weight: 500; border-bottom: 1px solid #CAC4D0; padding-bottom: 12px; margin-bottom: 12px;">Популярные услуги</h3>
        <?php if (empty($procPopularity)): ?>
            <p style="text-align: center; color: var(--md-secondary); font-size: 14px; padding: 20px;">Нет данных</p>
        <?php else: ?>
            <?php foreach (array_slice($procPopularity, 0, 5) as $proc => $count):
                $max = max($procPopularity);
                $percent = ($count / $max) * 100;
            ?>
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                        <span><?php echo htmlspecialchars($proc); ?></span>
                        <span style="font-weight: 700;"><?php echo $count; ?></span>
                    </div>
                    <div style="height: 6px; background: #E7E0EC; border-radius: 3px;">
                        <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--md-primary); border-radius: 3px;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="padding: 16px; text-align: center;">
        <p style="font-size: 12px; color: var(--md-secondary);">Полные отчеты доступны в десктопной версии.</p>
        <a href="../analytics.php" class="md-btn" style="background: #eee; border-radius: 8px; font-size: 13px;">Открыть полную версию</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
