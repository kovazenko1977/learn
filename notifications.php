<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\NotificationManager;

$settingsStore = new JsonStore('data/settings.json');
$notificationStore = new JsonStore('data/logs/notifications.json');
$notifier = new NotificationManager($settingsStore, $notificationStore);
$notifications = $notifier->getForUser($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notifier->markAsRead($_SESSION['user_id']);
    header('Location: notifications.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Уведомления</h1>
        <?php if (!empty($notifications)): ?>
        <form method="POST">
            <button type="submit" name="mark_read" class="filter-btn">Прочитать всё</button>
        </form>
        <?php endif; ?>
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
