<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('logs_view')) {
    die("У вас недостаточно прав для просмотра журнала системных событий.");
}

$logManager = new \Medical\Core\Managers\LogManager();
$logs = $logManager->getRecent(500);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Журнал системных событий</h1>
    <button class="btn" onclick="window.print()"><i data-lucide="printer" class="icon"></i> Печать</button>
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
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($log['user']); ?></span>
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
