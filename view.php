<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\UserManager;

$id = (int)($_GET['id'] ?? 0);
$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore);
$req = $requestManager->getById($id);

if (!$req) {
    die('Заявка не найдена');
}

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);
$initiator = $userManager->getById($req['initiator_id']);
$performer = isset($req['performer_id']) ? $userManager->getById($req['performer_id']) : null;

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Server-side authorization
    $isAllowed = false;
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];

    if ($userRole === 'admin') {
        $isAllowed = true;
    } elseif ($userRole === 'performer' && ($req['performer_id'] ?? 0) === $userId) {
        if (in_array($action, ['working', 'checking'])) $isAllowed = true;
    } elseif ($userRole === 'controller') {
        if (in_array($action, ['completed', 'returned', 'closed'])) $isAllowed = true;
    }

    if (!$isAllowed) {
        die('У вас нет прав для этого действия');
    }

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
    'new' => 'Новая',
    'assigned' => 'Назначена',
    'working' => 'В работе',
    'checking' => 'Проверка',
    'returned' => 'Доработка',
    'completed' => 'Выполнена',
    'closed' => 'Закрыта'
];

$priorityNames = [
    'low' => 'Низкий',
    'medium' => 'Средний',
    'high' => 'Высокий',
    'critical' => 'Критический'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; align-items:center; gap:12px;">
        <a href="index.php" style="color:var(--win-text);"><i data-lucide="arrow-left"></i></a>
        <h1>Заявка #<?php echo $req['id']; ?></h1>
    </div>

    <section class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
            <span class="badge status-<?php echo $req['status']; ?>">
                <?php echo $statusNames[$req['status']] ?? $req['status']; ?>
            </span>
            <span style="font-size:12px; color:var(--win-text-secondary);">
                Создана: <?php echo date('d.m.Y H:i', strtotime($req['created_at'])); ?>
            </span>
        </div>

        <div style="font-weight:600; font-size:18px; margin-bottom:8px;">
            <?php echo $services[$req['service_id']] ?? 'Служба'; ?>
        </div>

        <div style="margin-bottom:16px; white-space: pre-wrap;"><?php echo htmlspecialchars($req['description']); ?></div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; font-size:14px; color:var(--win-text-secondary);">
            <div>
                <strong>Приоритет:</strong><br>
                <?php echo $priorityNames[$req['priority']] ?? $req['priority']; ?>
            </div>
            <div>
                <strong>Место:</strong><br>
                <?php echo "Корп. {$req['location']['building']}, эт. {$req['location']['floor']}, каб. {$req['location']['room']}"; ?>
            </div>
            <div>
                <strong>Инициатор:</strong><br>
                <?php echo $initiator['name'] ?? 'Неизвестно'; ?>
            </div>
            <div>
                <strong>Исполнитель:</strong><br>
                <?php echo $performer['name'] ?? 'Не назначен'; ?>
            </div>
        </div>

        <?php if (!empty($req['photo'])): ?>
            <div style="margin-top:16px;">
                <strong>Фото проблемы:</strong><br>
                <img src="<?php echo $req['photo']; ?>" style="width:100%; max-width:400px; border-radius:8px; margin-top:8px;">
            </div>
        <?php endif; ?>
    </section>

    <!-- Role-specific actions -->
    <div class="actions">
        <?php if ($req['status'] === 'new' && ($_SESSION['user_role'] === 'service_lead' || $_SESSION['user_role'] === 'admin')): ?>
            <a href="assign.php?id=<?php echo $req['id']; ?>" class="btn-primary" style="display:block; text-align:center; text-decoration:none; margin-bottom:16px;">
                Назначить исполнителя
            </a>
        <?php endif; ?>

        <?php if (($_SESSION['user_role'] === 'performer' && ($req['performer_id'] ?? 0) === $_SESSION['user_id']) || $_SESSION['user_role'] === 'admin'): ?>
            <?php if ($req['status'] === 'assigned' || $req['status'] === 'returned'): ?>
                <form method="POST" class="card">
                    <input type="hidden" name="action" value="working">
                    <p>Принять заявку в работу?</p>
                    <button type="submit" class="btn-primary">В работу</button>
                </form>
            <?php elseif ($req['status'] === 'working'): ?>
                <form method="POST" enctype="multipart/form-data" class="card">
                    <input type="hidden" name="action" value="checking">
                    <h3>Завершить работу</h3>
                    <div class="form-group">
                        <label>Комментарий о выполнении</label>
                        <textarea name="comment" rows="2" required placeholder="Что было сделано..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Фото результата</label>
                        <input type="file" name="proof_photo" accept="image/*" capture="environment">
                    </div>
                    <button type="submit" class="btn-primary">На проверку</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($_SESSION['user_role'] === 'controller' || $_SESSION['user_role'] === 'admin'): ?>
            <?php if ($req['status'] === 'checking'): ?>
                <form method="POST" class="card">
                    <h3>Проверка выполнения</h3>
                    <div class="form-group">
                        <label>Замечания (если возвращаете)</label>
                        <textarea name="comment" rows="2"></textarea>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                        <button type="submit" name="action" value="completed" class="btn-primary" style="background:var(--status-completed);">Принять</button>
                        <button type="submit" name="action" value="returned" class="btn-primary" style="background:var(--status-returned);">На доработку</button>
                    </div>
                </form>
            <?php elseif ($req['status'] === 'completed'): ?>
                <form method="POST" class="card">
                    <input type="hidden" name="action" value="closed">
                    <p>Заявка выполнена. Закрыть и отправить в архив?</p>
                    <button type="submit" class="btn-primary" style="background:var(--status-closed);">Закрыть заявку</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <section class="card">
        <h2>История действий</h2>
        <div class="history-list">
            <?php foreach (array_reverse($req['history']) as $entry): ?>
                <div style="padding:12px 0; border-bottom:1px solid var(--win-border);">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--win-text-secondary); margin-bottom:4px;">
                        <span><?php echo $statusNames[$entry['status']] ?? $entry['status']; ?></span>
                        <span><?php echo date('d.m H:i', strtotime($entry['timestamp'])); ?></span>
                    </div>
                    <div style="font-size:14px;">
                        <strong><?php
                            $u = $userManager->getById($entry['user_id']);
                            echo $u['name'] ?? 'Система';
                        ?>:</strong>
                        <?php echo htmlspecialchars($entry['comment']); ?>
                    </div>
                    <?php if (isset($entry['photo'])): ?>
                        <img src="<?php echo $entry['photo']; ?>" style="width:100px; height:100px; object-fit:cover; border-radius:4px; margin-top:8px;">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
