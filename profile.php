<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\UserManager;

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);
$user = $userManager->getById($_SESSION['user_id']);

$servicesStore = new JsonStore('data/services.json');
$services = [];
foreach ($servicesStore->read() as $s) $services[$s['id']] = $s['name'];

$roleNames = [
    'initiator' => 'Инициатор',
    'performer' => 'Исполнитель',
    'service_lead' => 'Ответственный службы',
    'controller' => 'Контролёр',
    'manager' => 'Руководитель',
    'admin' => 'Администратор'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Профиль пользователя</h1>
    </div>

    <section class="card mica" style="text-align:center; padding: 32px 16px;">
        <div style="width:80px; height:80px; background:var(--win-accent); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:32px; font-weight:600;">
            <?php echo mb_substr($user['name'], 0, 1); ?>
        </div>
        <h2 style="margin:0;"><?php echo $user['name']; ?></h2>
        <p style="color:var(--win-text-secondary); margin:4px 0 16px;"><?php echo $roleNames[$user['role']] ?? $user['role']; ?></p>

        <div style="text-align:left; max-width:300px; margin:0 auto; border-top:1px solid var(--win-border); padding-top:16px;">
            <div style="margin-bottom:8px;">
                <label style="font-size:12px;">Код доступа</label>
                <div style="font-weight:600;">****</div>
            </div>
            <?php if ($user['service_id']): ?>
                <div style="margin-bottom:8px;">
                    <label style="font-size:12px;">Служба</label>
                    <div style="font-weight:600;"><?php echo $services[$user['service_id']] ?? 'Неизвестно'; ?></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section style="margin-top:24px;">
        <a href="logout.php" class="btn-primary" style="background:var(--priority-critical); display:block; text-align:center; text-decoration:none;">
            Выйти из системы
        </a>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
