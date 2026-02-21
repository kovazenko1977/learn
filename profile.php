<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\UserManager;

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telegram_chat_id'])) {
    checkCsrf();
    $userManager->update($_SESSION['user_id'], [
        'telegram_chat_id' => $_POST['telegram_chat_id']
    ]);
    header('Location: profile.php?success=1');
    exit;
}

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

<div class="container" style="max-width: 500px;">
    <div class="page-header" style="text-align: center; animation: slideDown 0.5s ease-out;">
        <h1>Профиль сотрудника</h1>
        <p style="color:var(--win-text-secondary);">Ваши учетные данные в системе ХОП</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="animation: slideDown 0.3s ease-out; text-align:center;">Данные профиля обновлены</div>
    <?php endif; ?>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; padding: 40px 20px; text-align: center;">
        <div style="width:100px; height:100px; background: linear-gradient(135deg, var(--win-accent) 0%, #005a9e 100%); color:#fff; border-radius:30px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:42px; font-weight:800; box-shadow: 0 10px 20px rgba(0,120,212,0.2);">
            <?php echo mb_substr($user['name'], 0, 1); ?>
        </div>

        <h2 style="margin:0; font-size: 24px;"><?php echo htmlspecialchars($user['name']); ?></h2>
        <div class="badge" style="margin-top: 12px; background: rgba(0,120,212,0.1); color: var(--win-accent); padding: 6px 16px; font-size: 14px; border-radius: 20px;">
            <?php echo $roleNames[$user['role']] ?? $user['role']; ?>
        </div>

        <div style="margin-top: 40px; text-align: left; border-top: 1px solid var(--win-border); pt: 32px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; align-items: center;">
                <span style="color: var(--win-text-secondary); font-size: 13px;">Персональный код</span>
                <span style="font-weight: 700; font-family: monospace; letter-spacing: 2px; background: rgba(0,0,0,0.03); padding: 4px 12px; border-radius: 6px;">●●●●●●</span>
            </div>

            <?php if ($user['service_id']): ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; align-items: center;">
                <span style="color: var(--win-text-secondary); font-size: 13px;">Служба</span>
                <span style="font-weight: 700; color: var(--win-text);"><?php echo htmlspecialchars($services[$user['service_id']] ?? '—'); ?></span>
            </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; margin-bottom: 16px; align-items: center;">
                <span style="color: var(--win-text-secondary); font-size: 13px;">ID аккаунта</span>
                <span style="font-weight: 700; color: var(--win-text);">#<?php echo $user['id']; ?></span>
            </div>

            <form method="POST" style="margin-top: 24px; pt: 24px; border-top: 1px dashed var(--win-border);">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label style="font-size: 12px; margin-bottom: 8px; display: block;">Telegram Chat ID (для личных уведомлений)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($user['telegram_chat_id'] ?? ''); ?>" placeholder="Напр. 123456789" style="height: 38px; font-size: 14px;">
                        <button type="submit" class="btn-primary" style="height: 38px; padding: 0 16px;"><i data-lucide="check" style="width: 16px;"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <div style="margin-top: 24px; text-align: left; background: rgba(0,120,212,0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(0,120,212,0.1);">
            <h3 style="margin-top:0; font-size:15px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="bell-ring" style="width:18px; color:var(--win-accent);"></i> Звуковые оповещения
            </h3>
            <p style="font-size:12px; color:var(--win-text-secondary); margin-bottom:16px;">
                Чтобы получать звуковые уведомления при закрытой странице, используйте Telegram (укажите ID выше) или включите браузерные Push-уведомления.
            </p>
            <button id="btn-push-subscribe" class="btn-secondary" style="width:100%; font-size:13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i data-lucide="message-square"></i> Включить Push-уведомления
            </button>
            <div id="push-status" style="font-size:11px; margin-top:8px; text-align:center; color:var(--win-accent); font-weight:600;"></div>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
        <div class="mobile-only" style="margin-top: 32px; border-top: 1px solid var(--win-border); pt: 24px; text-align: left;">
            <h3 style="font-size: 16px; margin-bottom: 16px;">Панель управления</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <a href="users.php" class="btn-secondary" style="text-decoration:none; text-align:center; font-size: 13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i data-lucide="users" style="width:16px;"></i> Персонал
                </a>
                <a href="services_manage.php" class="btn-secondary" style="text-decoration:none; text-align:center; font-size: 13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i data-lucide="briefcase" style="width:16px;"></i> Службы
                </a>
                <a href="templates_manage.php" class="btn-secondary" style="text-decoration:none; text-align:center; font-size: 13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i data-lucide="copy" style="width:16px;"></i> Шаблоны
                </a>
                <a href="settings.php" class="btn-secondary" style="text-decoration:none; text-align:center; font-size: 13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i data-lucide="settings" style="width:16px;"></i> Настройки
                </a>
            </div>
        </div>
        <?php endif; ?>

        <a href="logout.php" class="btn-primary" style="margin-top: 32px; background: #e81123; width: 100%; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i data-lucide="log-out"></i> Завершить сеанс
        </a>
    </section>
</div>

<script>
document.getElementById('btn-push-subscribe').addEventListener('click', async () => {
    const statusEl = document.getElementById('push-status');

    if (!('Notification' in window)) {
        alert("Ваш браузер не поддерживает уведомления");
        return;
    }

    statusEl.textContent = "Запрос разрешения...";

    let permission = await Notification.requestPermission();
    if (permission === 'granted') {
        statusEl.textContent = "Разрешено! Теперь вы будете получать системные оповещения.";
        // In a real production app, we would register the Push subscription here
        // and send it to the server to be stored in users.json
        new Notification("ХОП", {
            body: "Браузерные уведомления успешно активированы",
            icon: "/assets/icon-192.png"
        });
    } else {
        statusEl.textContent = "Доступ заблокирован в настройках браузера.";
        statusEl.style.color = "var(--priority-critical)";
    }
});

// Check current status
if (Notification.permission === 'granted') {
    document.getElementById('push-status').textContent = "Push-уведомления активны";
    document.getElementById('btn-push-subscribe').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
