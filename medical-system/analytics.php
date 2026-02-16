<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$summary = $analytics->getSummary();
$workload = $analytics->getWorkloadByCabinet();
?>

<h1>Аналитика и отчетность</h1>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <div class="card mica-effect">
        <h3>Эффективность работы</h3>
        <p>Всего пациентов: <strong><?php echo $summary['total_patients']; ?></strong></p>
        <p>Назначено процедур: <strong><?php echo $summary['total_appointments']; ?></strong></p>
        <p>Выполнено: <strong><?php echo $summary['attended_count']; ?></strong></p>
        <p>Процент выполнения: <strong><?php echo $summary['total_appointments'] > 0 ? round(($summary['attended_count'] / $summary['total_appointments']) * 100) : 0; ?>%</strong></p>
    </div>
    <div class="card mica-effect">
        <h3>Финансовые показатели</h3>
        <p>Общая выручка: <strong style="color: #107c10; font-size: 1.2rem;"><?php echo number_format($summary['total_revenue'], 2, ',', ' '); ?> ₽</strong></p>
        <hr style="border:0; border-top: 1px solid var(--win-border); margin: 15px 0;">
        <button class="btn"><i data-lucide="download" class="icon"></i> Экспорт в CSV</button>
        <button class="btn"><i data-lucide="printer" class="icon"></i> Печать отчета</button>
    </div>
</div>

<div class="card mica-effect">
    <h3>Загруженность кабинетов</h3>
    <div style="display: flex; gap: 20px; align-items: flex-end; height: 200px; padding-top: 20px;">
        <?php foreach ($workload as $cab => $count):
            $height = min(100, ($count / max(1, max($workload))) * 100);
        ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                <div style="width: 100%; background: var(--win-accent); border-radius: 4px 4px 0 0; height: <?php echo $height; ?>%;"></div>
                <span style="font-size: 0.8em;">Каб. <?php echo $cab; ?></span>
                <strong><?php echo $count; ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card mica-effect" style="margin-top: 20px;">
    <h3>Детализация по врачам</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                <th style="padding: 10px;">Врач</th>
                <th style="padding: 10px;">Назначений</th>
                <th style="padding: 10px;">Пациентов</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 10px;">Лечащий врач</td>
                <td style="padding: 10px;">12</td>
                <td style="padding: 10px;">5</td>
            </tr>
            <tr>
                <td style="padding: 10px;">Администратор</td>
                <td style="padding: 10px;">3</td>
                <td style="padding: 10px;">2</td>
            </tr>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
