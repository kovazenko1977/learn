<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\NotificationManager;

$settingsStore = new JsonStore('data/settings.json');
$notificationStore = new JsonStore('data/notifications.json');
$notifier = new NotificationManager($settingsStore, $notificationStore);
$notifications = $notifier->getForUser($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    checkCsrf();
    $notifier->markAsRead($_SESSION['user_id']);
    header('Location: notifications.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; animation: slideDown 0.5s ease-out;">
        <div>
            <h1>Центр уведомлений</h1>
            <p style="color:var(--win-text-secondary);">Важные события и обновления статусов</p>
        </div>
        <?php if (!empty($notifications)): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <button type="submit" name="mark_read" class="btn-primary" style="padding: 8px 16px; font-size: 13px; background: rgba(0,0,0,0.05); color: var(--win-text); border: 1px solid var(--win-border);">
                <i class="lucide-check-circle-2"></i> Прочитать всё
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card mica" style="text-align:center; color:var(--win-text-secondary); padding: 80px 20px; animation: slideUp 0.6s ease-out; display: flex; flex-direction: column; align-items: center;">
            <div style="width: 80px; height: 80px; background: rgba(0,0,0,0.03); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                <i class="lucide-bell-off" style="width:40px; height:40px; opacity: 0.3;"></i>
            </div>
            <p style="font-size: 18px; font-weight: 600; margin: 0; color: var(--win-text);">Входящих нет</p>
            <p style="font-size: 14px; margin-top: 8px; opacity: 0.7;">Мы сообщим вам, когда произойдет что-то важное</p>
        </div>
    <?php else: ?>
        <div class="list-container" style="display: grid; gap: 12px; animation: slideUp 0.6s ease-out;">
            <?php foreach (array_reverse($notifications) as $index => $n): ?>
                <div class="card mica list-item <?php echo $n['read'] ? '' : 'unread-pulse'; ?>" style="animation-delay: <?php echo $index * 0.05; ?>s; padding: 16px; border-left: 4px solid <?php echo $n['read'] ? 'transparent' : 'var(--win-accent)'; ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <div style="font-size:11px; color:var(--win-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">
                            <i class="lucide-clock" style="width:12px; height:12px; vertical-align: middle; margin-right:4px;"></i>
                            <?php echo date('d.m.Y H:i', strtotime($n['timestamp'])); ?>
                        </div>
                        <?php if (!$n['read']): ?>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <div style="width: 8px; height: 8px; background: var(--win-accent); border-radius: 50%;"></div>
                                <span style="font-size: 11px; font-weight: 800; color: var(--win-accent);">НОВОЕ</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:15px; font-weight: 500; line-height: 1.5; color: var(--win-text);">
                        <?php echo htmlspecialchars($n['message']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.unread-pulse {
    background: rgba(0, 120, 212, 0.03) !important;
}
</style>

<?php include 'includes/footer.php'; ?>
