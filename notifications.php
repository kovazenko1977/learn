<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\NotificationManager;

$settingsStore = new JsonStore('data/settings.json');
$notifier = new NotificationManager($settingsStore, 'data/logs/notifications.json');
$notifications = $notifier->getForUser($_SESSION['user_id']);

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Уведомления</h1>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card" style="text-align:center; color:var(--win-text-secondary);">
            <i data-lucide="bell-off" style="width:48px; height:48px; margin-bottom:12px;"></i>
            <p>Нет новых уведомлений</p>
        </div>
    <?php else: ?>
        <?php foreach (array_reverse($notifications) as $n): ?>
            <div class="card mica">
                <div style="font-size:12px; color:var(--win-text-secondary); margin-bottom:4px;">
                    <?php echo date('d.m H:i', strtotime($n['timestamp'])); ?>
                </div>
                <div><?php echo htmlspecialchars($n['message']); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
