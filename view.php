<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\UserManager;
use Hop\Core\NotificationManager;

$id = (int)($_GET['id'] ?? 0);
$settingsStore = new JsonStore('data/settings.json');
$notificationStore = new JsonStore('data/notifications.json');
$notifier = new NotificationManager($settingsStore, $notificationStore);

$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore, $notifier);
$req = $requestManager->getById($id);

if (!$req) {
    die('Заявка не найдена');
}

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);
$initiator = $userManager->getById($req['initiator_id']);
$performer = isset($req['performer_id']) ? $userManager->getById($req['performer_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCsrf();
    $action = $_POST['action'];
    $isAllowed = false;
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];

    if ($userRole === 'admin') { $isAllowed = true; }
    elseif ($userRole === 'performer' && ($req['performer_id'] ?? 0) === $userId) {
        if (in_array($action, ['working', 'checking'])) $isAllowed = true;
    } elseif ($userRole === 'controller') {
        if (in_array($action, ['completed', 'returned', 'closed'])) $isAllowed = true;
    }

    if (!$isAllowed) { die('Доступ запрещен'); }

    $comment = $_POST['comment'] ?? '';
    $photoPath = '';

    if (isset($_FILES['proof_photo']) && $_FILES['proof_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $photoPath = 'uploads/' . uniqid() . '_proof.' . $ext;
            move_uploaded_file($_FILES['proof_photo']['tmp_name'], $photoPath);
        }
    }

    $requestManager->updateStatus($id, $action, $_SESSION['user_id'], $comment, $photoPath);
    header("Location: view.php?id=$id");
    exit;
}

$servicesStore = new JsonStore('data/services.json');
$services = [];
foreach ($servicesStore->read() as $s) $services[$s['id']] = $s['name'];

$statusNames = [
    'new' => 'Новая', 'assigned' => 'Назначена', 'working' => 'В работе',
    'checking' => 'Проверка', 'returned' => 'Доработка', 'completed' => 'Выполнена', 'closed' => 'Закрыта'
];

$priorityNames = [
    'low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий', 'critical' => 'Критический'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; align-items:center; justify-content:space-between; animation: slideDown 0.5s ease-out;">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="index.php" class="btn-icon" style="text-decoration:none; color:inherit; background:rgba(0,0,0,0.05); border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                <i class="lucide-arrow-left"></i>
            </a>
            <div>
                <h1 style="margin:0;">Заявка #<?php echo $req['id']; ?></h1>
                <div style="font-size:12px; color:var(--win-text-secondary); margin-top:2px;">
                    Создана <?php echo date('d.m.Y в H:i', strtotime($req['created_at'])); ?>
                </div>
            </div>
        </div>
        <button onclick="window.print()" class="btn-secondary desktop-only">
            <i class="lucide-printer"></i> Печать
        </button>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; animation: slideUp 0.6s ease-out;">
        <!-- Left Column: Details -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section class="card mica" style="padding: 32px; border-top: 4px solid var(--priority-<?php echo $req['priority']; ?>);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--win-text-secondary); font-weight: 700; margin-bottom: 4px;">Служба</div>
                        <h2 style="margin: 0; font-size: 22px; font-weight: 800;"><?php echo $services[$req['service_id']] ?? 'Служба'; ?></h2>
                    </div>
                    <span class="badge status-<?php echo $req['status']; ?>" style="padding: 8px 16px; font-size: 14px;">
                        <?php echo $statusNames[$req['status']]; ?>
                    </span>
                </div>

                <div style="font-size: 17px; line-height: 1.6; color: var(--win-text); margin-bottom: 32px; white-space: pre-wrap;"><?php echo htmlspecialchars($req['description']); ?></div>

                <?php if (!empty($req['photo'])): ?>
                    <div style="margin-top: 24px;">
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--win-text-secondary); font-weight: 700; margin-bottom: 12px;">Фото фиксация</div>
                        <a href="<?php echo $req['photo']; ?>" target="_blank">
                            <img src="<?php echo $req['photo']; ?>" style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);" loading="lazy">
                        </a>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 32px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; border-top: 1px solid var(--win-border); pt: 24px;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--win-text-secondary); font-weight: 700; margin-bottom: 4px;">Приоритет</div>
                        <div style="font-weight: 700; color: var(--priority-<?php echo $req['priority']; ?>);"><?php echo $priorityNames[$req['priority']]; ?></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--win-text-secondary); font-weight: 700; margin-bottom: 4px;">Объект</div>
                        <div style="font-weight: 700;">Корп. <?php echo $req['location']['building']; ?>, каб. <?php echo $req['location']['room']; ?></div>
                    </div>
                </div>
            </section>

            <!-- Actions Section -->
            <?php if ($req['status'] !== 'closed'): ?>
            <section class="card mica" style="padding: 24px;">
                <h3 style="margin-top:0; font-size:16px; margin-bottom:20px;">Управление состоянием</h3>

                <?php if ($req['status'] === 'new' && ($_SESSION['user_role'] === 'service_lead' || $_SESSION['user_role'] === 'admin')): ?>
                    <a href="assign.php?id=<?php echo $req['id']; ?>" class="btn-primary" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="lucide-user-check"></i> Назначить исполнителя
                    </a>
                <?php endif; ?>

                <?php if (($_SESSION['user_role'] === 'performer' && ($req['performer_id'] ?? 0) === $_SESSION['user_id']) || $_SESSION['user_role'] === 'admin'): ?>
                    <?php if ($req['status'] === 'assigned' || $req['status'] === 'returned'): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="working">
                            <button type="submit" class="btn-primary" style="width:100%;">Принять в работу</button>
                        </form>
                    <?php elseif ($req['status'] === 'working'): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="checking">
                            <div class="form-group">
                                <label>Результат (отчет)</label>
                                <textarea name="comment" rows="2" required placeholder="Опишите выполненные действия..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Фото подтверждение (необязательно)</label>
                                <input type="file" name="proof_photo" accept="image/*" capture="environment">
                            </div>
                            <button type="submit" class="btn-primary" style="width:100%; background:var(--status-checking);">Завершить и передать на проверку</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($_SESSION['user_role'] === 'controller' || $_SESSION['user_role'] === 'admin'): ?>
                    <?php if ($req['status'] === 'checking'): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <div class="form-group">
                                <label>Комментарий контролера</label>
                                <textarea name="comment" rows="2"></textarea>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                                <button type="submit" name="action" value="completed" class="btn-primary" style="background:var(--status-completed);">Принять работу</button>
                                <button type="submit" name="action" value="returned" class="btn-primary" style="background:var(--status-returned);">Вернуть на доработку</button>
                            </div>
                        </form>
                    <?php elseif ($req['status'] === 'completed'): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="closed">
                            <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:16px;">Заявка прошла все этапы. Подтвержите закрытие.</p>
                            <button type="submit" class="btn-primary" style="width:100%; background:var(--win-accent);">Закрыть и архивировать</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </div>

        <!-- Right Column: Sidebar info & Timeline -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section class="card mica" style="padding: 20px;">
                <h3 style="margin-top:0; font-size:14px; text-transform: uppercase; letter-spacing: 0.5px; color:var(--win-text-secondary); margin-bottom:16px;">Участники</h3>

                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div style="width:36px; height:36px; background:rgba(0,0,0,0.05); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">И</div>
                    <div>
                        <div style="font-size:13px; font-weight:700;"><?php echo htmlspecialchars($initiator['name'] ?? 'Система'); ?></div>
                        <div style="font-size:11px; color:var(--win-text-secondary);">Инициатор</div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width:36px; height:36px; background:rgba(0,120,212,0.1); color:var(--win-accent); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">Р</div>
                    <div>
                        <div style="font-size:13px; font-weight:700;"><?php echo htmlspecialchars($performer['name'] ?? 'Не назначен'); ?></div>
                        <div style="font-size:11px; color:var(--win-text-secondary);">Исполнитель</div>
                    </div>
                </div>
            </section>

            <section class="card mica" style="padding: 20px;">
                <h3 style="margin-top:0; font-size:14px; text-transform: uppercase; letter-spacing: 0.5px; color:var(--win-text-secondary); margin-bottom:20px;">История заявки</h3>
                <div class="timeline" style="margin-left: 4px;">
                    <?php foreach (array_reverse($req['history']) as $entry): ?>
                        <div class="timeline-item" style="padding-bottom: 24px;">
                            <div class="timeline-marker" style="width: 10px; height: 10px; border-width: 2px; top: 4px; border-color: var(--status-<?php echo $entry['status']; ?>);"></div>
                            <div class="timeline-content" style="margin-left: 20px;">
                                <div style="font-size: 11px; color: var(--win-text-secondary); margin-bottom: 2px;">
                                    <?php echo date('d.m.Y H:i', strtotime($entry['timestamp'])); ?>
                                </div>
                                <div style="font-size: 13px; font-weight: 700; margin-bottom: 4px;">
                                    <?php echo $statusNames[$entry['status']]; ?>
                                </div>
                                <?php if (!empty($entry['comment'])): ?>
                                    <div style="font-size: 12px; color: var(--win-text-secondary); line-height: 1.4; background: rgba(0,0,0,0.02); padding: 8px; border-radius: 6px;">
                                        <?php echo htmlspecialchars($entry['comment']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .container > div { grid-template-columns: 1fr !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
