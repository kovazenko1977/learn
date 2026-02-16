<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$summary = $analytics->getSummary();
?>
<h1>Панель управления</h1>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Пациентов всего</h3>
        <div style="font-size: 2rem; font-weight: 600;"><?php echo $summary['total_patients']; ?></div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Назначено процедур</h3>
        <div style="font-size: 2rem; font-weight: 600;"><?php echo $summary['total_appointments']; ?></div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Оказано услуг</h3>
        <div style="font-size: 2rem; font-weight: 600;"><?php echo $summary['attended_count']; ?></div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: #666; font-size: 0.9rem;">Выручка (платные)</h3>
        <div style="font-size: 2rem; font-weight: 600; color: #107c10;"><?php echo number_format($summary['total_revenue'], 2, ',', ' '); ?> ₽</div>
    </div>
</div>

<div style="margin-top: 40px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="card mica-effect">
        <h2>Быстрые действия</h2>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="patients.php" class="btn btn-primary"><i data-lucide="user-plus" class="icon"></i> Новый пациент</a>
            <a href="procedures_doctor.php" class="btn"><i data-lucide="calendar" class="icon"></i> Расписание</a>
            <a href="analytics.php" class="btn"><i data-lucide="download" class="icon"></i> Отчеты</a>
        </div>
    </div>
    <div class="card mica-effect">
        <h2>Загрузка по кабинетам</h2>
        <?php
        $workload = $analytics->getWorkloadByCabinet();
        foreach ($workload as $cab => $count):
        ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid var(--win-border);">
                <span>Кабинет <?php echo htmlspecialchars($cab); ?></span>
                <strong><?php echo $count; ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
