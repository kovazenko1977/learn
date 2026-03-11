<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('logs_view')) {
    die("У вас недостаточно прав для просмотра журнала системных событий.");
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$logManager = new \Medical\Core\Managers\LogManager();
$logs = $logManager->getByDateRange($startDate, $endDate);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Журнал системных событий</h1>
    <div style="display: flex; gap: 10px;">
        <button class="btn" onclick="window.print()"><i data-lucide="printer" class="icon"></i> Печать</button>
    </div>
</div>

<div class="card mica-effect" style="margin-bottom: 24px;">
    <form method="GET" style="display: flex; gap: 12px; align-items: flex-end;">
        <div>
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">С даты</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">По дату</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <div style="border-left: 1px solid var(--win-border); padding-left: 20px; display: flex; gap: 15px; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom: 8px; font-size: 0.8rem;">Дней вперед</label>
                <input type="number" id="days_ahead" min="0" max="365" placeholder="0" style="width: 80px;">
            </div>
            <div style="padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="save_period" style="width: 18px; height: 18px; cursor: pointer;">
                <label for="save_period" style="font-size: 0.85rem; cursor: pointer;">Запомнить</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Показать</button>
    </form>
</div>

<div class="card mica-effect">
    <table style="font-size: 0.85rem;">
        <thead>
            <tr>
                <th>Дата и время</th>
                <th>Пользователь</th>
                <th>Действие</th>
                <th>Подробности</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td style="white-space: nowrap; color: var(--win-text-secondary);"><?php echo $log['timestamp']; ?></td>
                <td>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($log['user_name'] ?? $log['user'] ?? 'Система'); ?></span>
                        <span style="font-size: 0.7rem; color: var(--win-text-secondary);"><?php echo htmlspecialchars($log['role']); ?></span>
                    </div>
                </td>
                <td>
                    <span style="padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.05); font-weight: 500;">
                        <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                </td>
                <td style="color: #444;"><?php echo htmlspecialchars($log['details']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--win-text-secondary);">Событий не найдено</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
