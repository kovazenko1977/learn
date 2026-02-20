<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\UserManager;
use Hop\Core\NotificationManager;

checkRole(['service_lead', 'admin']);

$requestId = (int)($_GET['id'] ?? 0);
$settingsStore = new JsonStore('data/settings.json');
$notificationStore = new JsonStore('data/notifications.json');
$notifier = new NotificationManager($settingsStore, $notificationStore);

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);

$currentUser = $userManager->getById($_SESSION['user_id']);

$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore, $notifier);
$req = $requestManager->getById($requestId);

if (!$req) die('Заявка не найдена');

if ($_SESSION['user_role'] === 'service_lead' && $req['service_id'] !== $currentUser['service_id']) {
    die('Отказано в доступе');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $performerId = (int)$_POST['performer_id'];
    if ($requestManager->assign($requestId, $performerId, $_SESSION['user_id'])) {
        header("Location: view.php?id=$requestId");
        exit;
    }
}

$allUsers = $userManager->getAll();
$performers = array_filter($allUsers, fn($u) => $u['role'] === 'performer' && $u['service_id'] == $req['service_id']);

include 'includes/header.php';
?>

<div class="container" style="max-width: 600px;">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="view.php?id=<?php echo $requestId; ?>" class="btn-icon" style="text-decoration:none; color:inherit; background:rgba(0,0,0,0.05); border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;">
                <i data-lucide="chevron-left"></i>
            </a>
            <h1>Назначение</h1>
        </div>
    </div>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; padding: 32px;">
        <div style="margin-bottom: 32px; border-bottom: 1px solid var(--win-border); pb: 20px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--win-text-secondary); font-weight: 700; margin-bottom: 8px;">Информация о заявке #<?php echo $req['id']; ?></div>
            <div style="font-size: 15px; line-height: 1.5; color: var(--win-text); font-weight: 500;">
                <?php echo htmlspecialchars(mb_strimwidth($req['description'], 0, 150, "...")); ?>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group" style="margin-bottom: 32px;">
                <label style="font-size: 14px; font-weight: 600; margin-bottom: 12px; display: block;">Выберите ответственного исполнителя</label>
                <div style="display: grid; gap: 10px;">
                    <?php if (empty($performers)): ?>
                        <div style="padding: 20px; text-align: center; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px dashed var(--win-border);">
                            <p style="margin:0; font-size: 13px; color: var(--win-text-secondary);">В данной службе нет доступных исполнителей</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($performers as $p): ?>
                            <label style="display: flex; align-items: center; gap: 12px; padding: 16px; background: rgba(255,255,255,0.5); border: 1px solid var(--win-border); border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="performer_id" value="<?php echo $p['id']; ?>" required style="width: 20px; height: 20px; accent-color: var(--win-accent);">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 15px;"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div style="font-size: 12px; color: var(--win-text-secondary);">Исполнитель службы</div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($performers)): ?>
                <button type="submit" class="btn-primary" style="width:100%; padding: 16px; font-size: 16px; font-weight: 700;">
                    Подтвердить назначение
                </button>
            <?php endif; ?>

            <a href="view.php?id=<?php echo $requestId; ?>" style="display:block; text-align:center; margin-top:20px; color:var(--win-text-secondary); text-decoration:none; font-size: 14px; font-weight: 500;">
                Вернуться назад
            </a>
        </form>
    </section>
</div>

<style>
label:has(input:checked) {
    border-color: var(--win-accent) !important;
    background: rgba(0, 120, 212, 0.05) !important;
    box-shadow: 0 4px 12px rgba(0, 120, 212, 0.1);
}
</style>

<?php include 'includes/footer.php'; ?>
