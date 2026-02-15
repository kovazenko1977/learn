<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;

$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore);
$requests = $requestManager->getAll();

$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// Filter based on role
$filteredRequests = [];
foreach ($requests as $req) {
    if ($userRole === 'admin' || $userRole === 'manager') {
        $filteredRequests[] = $req;
    } elseif ($userRole === 'initiator') {
        if ($req['initiator_id'] === $userId) $filteredRequests[] = $req;
    } elseif ($userRole === 'performer') {
        if (isset($req['performer_id']) && $req['performer_id'] === $userId) $filteredRequests[] = $req;
    } elseif ($userRole === 'service_lead') {
        // Find user service
        $userStore = new JsonStore('data/users.json');
        $user = (new \Hop\Core\UserManager($userStore))->getById($userId);
        if ($user && $req['service_id'] === $user['service_id']) $filteredRequests[] = $req;
    } elseif ($userRole === 'controller') {
        if ($req['status'] === 'checking' || $req['status'] === 'completed') $filteredRequests[] = $req;
    }
}

// Sort by date desc
usort($filteredRequests, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

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

$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();
$slaConfig = $settings['sla'] ?? [];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Мои заявки</h1>
        <?php if (isset($_GET['success'])): ?>
            <span class="badge status-completed">Заявка создана!</span>
        <?php endif; ?>
    </div>

    <div class="request-list">
        <?php if (empty($filteredRequests)): ?>
            <div class="card" style="text-align:center; color:var(--win-text-secondary);">
                <i data-lucide="clipboard-list" style="width:48px; height:48px; margin-bottom:12px;"></i>
                <p>Нет активных заявок</p>
            </div>
        <?php else: ?>
            <?php foreach ($filteredRequests as $req): ?>
                <?php
                    $isOverdue = false;
                    if (!in_array($req['status'], ['completed', 'closed'])) {
                        $hoursLimit = $slaConfig[$req['priority']] ?? 24;
                        $createdTime = strtotime($req['created_at']);
                        if (time() > ($createdTime + ($hoursLimit * 3600))) {
                            $isOverdue = true;
                        }
                    }
                ?>
                <a href="view.php?id=<?php echo $req['id']; ?>" class="card p-<?php echo $req['priority']; ?>" style="display:block; text-decoration:none; color:inherit; <?php echo $isOverdue ? 'border: 1px solid var(--priority-critical);' : ''; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:8px;">
                        <div>
                            <span class="badge status-<?php echo $req['status']; ?>">
                                <?php echo $statusNames[$req['status']] ?? $req['status']; ?>
                            </span>
                            <?php if ($isOverdue): ?>
                                <span class="badge" style="background:var(--priority-critical); margin-left:4px;">SLA</span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:12px; color:var(--win-text-secondary);">
                            #<?php echo $req['id']; ?> | <?php echo date('d.m H:i', strtotime($req['created_at'])); ?>
                        </span>
                    </div>
                    <div style="font-weight:600; margin-bottom:4px;">
                        <?php echo $services[$req['service_id']] ?? 'Служба'; ?>
                    </div>
                    <div style="font-size:14px; margin-bottom:8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                        <?php echo htmlspecialchars($req['description']); ?>
                    </div>
                    <div style="display:flex; align-items:center; font-size:12px; color:var(--win-text-secondary);">
                        <i data-lucide="map-pin" style="width:12px; height:12px; margin-right:4px;"></i>
                        <?php echo "Корп. {$req['location']['building']}, эт. {$req['location']['floor']}, каб. {$req['location']['room']}"; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($userRole === 'admin'): ?>
    <div class="container" style="margin-top:20px;">
        <a href="users.php" class="btn-primary" style="display:inline-block; text-align:center;">Управление пользователями</a>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
