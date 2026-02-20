<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\UserManager;

checkRole('admin');

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $userManager->create([
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'code' => $_POST['code'],
            'service_id' => !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null
        ]);
        $message = 'Пользователь создан';
    } elseif ($action === 'delete') {
        $userManager->delete((int)$_POST['id']);
        $message = 'Пользователь удален';
    }
}

$users = $userManager->getAll();
$servicesStore = new JsonStore('data/services.json');
$services = $servicesStore->read();

$roleLabels = [
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
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Управление пользователями</h1>
        <p style="color:var(--win-text-secondary);">Учетные записи и права доступа</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="animation: slideDown 0.3s ease-out;"><?php echo $message; ?></div>
    <?php endif; ?>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; margin-bottom: 24px;">
        <h2 style="margin-top:0; font-size:18px; margin-bottom:20px;">Добавить нового сотрудника</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="name" required placeholder="Иванов Иван Иванович">
            </div>
            <div class="form-group">
                <label>Роль в системе</label>
                <select name="role">
                    <option value="initiator">Инициатор</option>
                    <option value="performer">Исполнитель</option>
                    <option value="service_lead">Ответственный службы</option>
                    <option value="controller">Контролёр</option>
                    <option value="manager">Руководитель</option>
                    <option value="admin">Администратор</option>
                </select>
            </div>
            <div class="form-group">
                <label>Код доступа (6 цифр)</label>
                <input type="text" name="code" maxlength="6" pattern="\d{6}" required placeholder="123456">
            </div>
            <div class="form-group">
                <label>Служба (для исполнителей)</label>
                <select name="service_id">
                    <option value="">Не привязано</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="grid-column: 1 / -1; margin-top:8px;">
                <button type="submit" class="btn-primary" style="width:100%;">
                    <i class="lucide-user-plus"></i> Создать аккаунт
                </button>
            </div>
        </form>
    </section>

    <div class="list-container" style="display: grid; gap: 12px;">
        <?php foreach ($users as $index => $u): ?>
        <div class="card mica list-item" style="animation-delay: <?php echo $index * 0.05; ?>s; display: flex; align-items: center; justify-content: space-between; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width:40px; height:40px; background:var(--win-accent); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px;">
                    <?php echo mb_substr($u['name'], 0, 1); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($u['name']); ?></div>
                    <div style="font-size: 12px; color: var(--win-text-secondary);">
                        <span class="badge" style="background: rgba(0,120,212,0.1); color: var(--win-accent); padding: 2px 8px; border-radius: 4px;">
                            <?php echo $roleLabels[$u['role']] ?? $u['role']; ?>
                        </span>
                        <?php if ($u['service_id']): ?>
                            <span style="margin-left: 8px;">
                                <i class="lucide-briefcase" style="font-size: 10px;"></i>
                                <?php
                                    foreach ($services as $s) if ($s['id'] == $u['service_id']) echo $s['name'];
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="text-align: right; font-family: monospace; color: var(--win-text-secondary); font-size: 14px;">
                    <?php echo $u['code']; ?>
                </div>
                <?php if ($u['role'] !== 'admin' || $u['id'] !== 1): ?>
                <form method="POST" onsubmit="return confirm('Удалить пользователя?')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:8px;">
                        <i class="lucide-trash-2"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
