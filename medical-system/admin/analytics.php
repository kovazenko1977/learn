<?php
require_once __DIR__ . '/header.php';
requireRole('admin');

use Medical\Core\ProceduresManager;

$procManager = new ProceduresManager($store);
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

$stats = $procManager->getAnalytics($startDate, $endDate);
?>

<div class="win-card mica-effect">
    <h2><i class="lucide-bar-chart-3"></i> Аналитика и отчетность</h2>

    <form method="GET" class="win-card" style="margin-bottom: 30px; display: flex; gap: 20px; align-items: end;">
        <div>
            <label>Начало периода</label>
            <input type="date" name="start" class="form-win" value="<?php echo $startDate; ?>">
        </div>
        <div>
            <label>Конец периода</label>
            <input type="date" name="end" class="form-win" value="<?php echo $endDate; ?>">
        </div>
        <button type="submit" class="btn-win">Применить фильтр</button>
    </form>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="win-card mica-effect" style="text-align: center;">
            <small>Всего назначений</small>
            <h2 style="margin: 5px 0; color: var(--win-accent);"><?php echo $stats['total']; ?></h2>
        </div>
        <div class="win-card mica-effect" style="text-align: center;">
            <small>Выполнено</small>
            <h2 style="margin: 5px 0; color: var(--success);"><?php echo $stats['completed']; ?></h2>
        </div>
        <div class="win-card mica-effect" style="text-align: center;">
            <small>Выручка</small>
            <h2 style="margin: 5px 0; color: var(--win-accent);"><?php echo number_format($stats['paid_revenue'], 2); ?> руб.</h2>
        </div>
    </div>

    <h3>Статистика по процедурам</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #ccc;">
                <th style="padding: 10px;">Наименование</th>
                <th>Кол-во назначений</th>
                <th>Выручка (оплачено)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stats['by_procedure'] as $p): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                <td><?php echo $p['count']; ?></td>
                <td><?php echo number_format($p['revenue'], 2); ?> руб.</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($stats['by_procedure'])): ?>
                <tr><td colspan="3" style="padding: 40px; text-align: center; color: var(--text-sec);">Нет данных за выбранный период</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
