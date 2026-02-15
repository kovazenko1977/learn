<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\UserManager;

checkRole(['service_lead', 'admin']);

$requestId = (int)($_GET['id'] ?? 0);
$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);

$currentUser = $userManager->getById($_SESSION['user_id']);

$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore);
$req = $requestManager->getById($requestId);

if (!$req) die('Заявка не найдена');

if ($_SESSION['user_role'] === 'service_lead' && $req['service_id'] !== $currentUser['service_id']) {
    die('Вы не можете назначать исполнителей для чужой службы');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $performerId = (int)$_POST['performer_id'];
    if ($requestManager->assign($requestId, $performerId, $_SESSION['user_id'])) {
        header("Location: view.php?id=$requestId");
        exit;
    }
}

// Get performers for this service
$allUsers = $userManager->getAll();
$performers = array_filter($allUsers, fn($u) => $u['role'] === 'performer' && $u['service_id'] == $req['service_id']);

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Назначить исполнителя</h1>
    </div>

    <section class="card">
        <p>Заявка #<?php echo $req['id']; ?></p>
        <p><strong>Описание:</strong> <?php echo htmlspecialchars($req['description']); ?></p>

        <form method="POST">
            <div class="form-group">
                <label>Выберите сотрудника</label>
                <select name="performer_id" required>
                    <option value="">-- Выберите --</option>
                    <?php foreach ($performers as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Назначить</button>
            <a href="view.php?id=<?php echo $requestId; ?>" style="display:block; text-align:center; margin-top:16px; color:var(--win-text-secondary);">Отмена</a>
        </form>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
