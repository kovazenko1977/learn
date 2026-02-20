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
            'service_id' => !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null,
            'telegram_chat_id' => $_POST['telegram_chat_id'] ?? ''
        ]);
        $message = 'Пользователь создан';
    } elseif ($action === 'edit') {
        $userManager->update((int)$_POST['id'], [
            'name' => $_POST['name'],
            'role' => $_POST['role'],
            'code' => $_POST['code'],
            'service_id' => !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null,
            'telegram_chat_id' => $_POST['telegram_chat_id'] ?? ''
        ]);
        $message = 'Данные пользователя обновлены';
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
        <h1>Управление персоналом</h1>
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
            <div class="form-group">
                <label>Telegram Chat ID (персональный)</label>
                <input type="text" name="telegram_chat_id" placeholder="Напр. 123456789">
            </div>
            <div style="grid-column: 1 / -1; margin-top:8px;">
                <button type="submit" class="btn-primary" style="width:100%;">
                    <i data-lucide="user-plus"></i> Создать аккаунт
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
                                <i data-lucide="briefcase" style="width: 12px; height: 12px; vertical-align: middle;"></i>
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

                <button class="btn-icon" style="background:none; border:none; color:var(--win-accent); cursor:pointer; padding:8px;"
                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                    <i data-lucide="edit-3"></i>
                </button>

                <?php if ($u['role'] !== 'admin' || $u['id'] !== 1): ?>
                <form method="POST" onsubmit="return confirm('Удалить пользователя?')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:8px;">
                        <i data-lucide="trash-2"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
    <div class="card mica" style="width:100%; max-width:500px; margin:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Редактировать сотрудника</h2>
            <button onclick="closeEditModal()" style="background:none; border:none; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="name" id="edit-name" required>
            </div>

            <div class="form-group">
                <label>Роль</label>
                <select name="role" id="edit-role">
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
                <input type="text" name="code" id="edit-code" maxlength="6" pattern="\d{6}" required>
            </div>

            <div class="form-group">
                <label>Служба</label>
                <select name="service_id" id="edit-service_id">
                    <option value="">Не привязано</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Telegram Chat ID</label>
                <input type="text" name="telegram_chat_id" id="edit-telegram_chat_id">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Отмена</button>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit-id').value = user.id;
    document.getElementById('edit-name').value = user.name;
    document.getElementById('edit-role').value = user.role;
    document.getElementById('edit-code').value = user.code;
    document.getElementById('edit-service_id').value = user.service_id || "";
    document.getElementById('edit-telegram_chat_id').value = user.telegram_chat_id || "";
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
