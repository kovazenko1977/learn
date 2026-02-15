<?php
require_once 'auth.php';
require_once '../core/autoload.php';

use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Users\UserManager;

$store = new JsonStore(__DIR__ . '/../data');
$userManager = new UserManager($store);

$pageTitle = 'Управление пользователями';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $userData = [
            'username' => $_POST['username'],
            'full_name' => $_POST['full_name'],
            'role' => $_POST['role'],
            'permissions' => $_POST['permissions'] ?? []
        ];

        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }

        if ($action === 'add') {
            $userManager->addUser($userData);
            $message = 'Пользователь успешно добавлен';
        } else {
            $id = (int)$_POST['id'];
            $userManager->updateUser($id, $userData);
            $message = 'Данные пользователя обновлены';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $userManager->deleteUser($id);
        $message = 'Пользователь удален';
    }
}

$users = $userManager->getUsers();
$allPermissions = $userManager->getAllPermissions();

require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Список пользователей</h2>
        <button class="btn btn-primary" onclick="showModal('add-user-modal')">Добавить пользователя</button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>ФИО</th>
                    <th>Роль</th>
                    <th>API Token</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo ($user['role'] === 'administrator' ? 'Администратор' : 'Пользователь'); ?></td>
                        <td><code style="font-size: 0.8em;"><?php echo $user['api_token'] ?? '—'; ?></code></td>
                        <td>
                            <button class="btn btn-sm btn-outline" onclick='editUser(<?php echo json_encode($user); ?>)'>Изм.</button>
                            <?php if ($user['username'] !== 'admin'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Add/Edit User -->
<div id="user-modal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="modal-title">Добавить пользователя</h2>
            <span class="close" onclick="hideModal('user-modal')">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="user-action" value="add">
            <input type="hidden" name="id" id="user-id" value="">

            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" id="user-username" required class="form-control">
            </div>

            <div class="form-group">
                <label>Пароль (оставьте пустым, чтобы не менять)</label>
                <input type="password" name="password" id="user-password" class="form-control">
            </div>

            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="full_name" id="user-full_name" required class="form-control">
            </div>

            <div class="form-group">
                <label>Роль</label>
                <select name="role" id="user-role" class="form-control" onchange="togglePermissions()">
                    <option value="user">Пользователь</option>
                    <option value="administrator">Администратор (все права)</option>
                </select>
            </div>

            <div id="permissions-section">
                <label>Права доступа</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <?php foreach ($allPermissions as $key => $label): ?>
                        <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                            <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" class="permission-checkbox" style="margin-right: 8px;">
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer" style="margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="hideModal('user-modal')">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.modal-content { background: var(--glass-bg); margin: 5% auto; padding: 25px; border-radius: 16px; border: 1px solid var(--glass-border); color: white; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.close { cursor: pointer; font-size: 24px; }
.form-group { margin-bottom: 15px; }
.form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: white; }
.btn-sm { padding: 4px 8px; font-size: 12px; }
.alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; }
.alert-success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #28a745; }
</style>

<script>
function showModal(id) {
    if (id === 'add-user-modal') {
        document.getElementById('modal-title').innerText = 'Добавить пользователя';
        document.getElementById('user-action').value = 'add';
        document.getElementById('user-id').value = '';
        document.getElementById('user-username').value = '';
        document.getElementById('user-password').value = '';
        document.getElementById('user-full_name').value = '';
        document.getElementById('user-role').value = 'user';
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        togglePermissions();
    }
    document.getElementById('user-modal').style.display = 'block';
}

function hideModal(id) {
    document.getElementById(id).style.display = 'none';
}

function editUser(user) {
    document.getElementById('modal-title').innerText = 'Редактировать пользователя';
    document.getElementById('user-action').value = 'edit';
    document.getElementById('user-id').value = user.id;
    document.getElementById('user-username').value = user.username;
    document.getElementById('user-password').value = '';
    document.getElementById('user-full_name').value = user.full_name;
    document.getElementById('user-role').value = user.role;

    const permissions = user.permissions || [];
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.checked = permissions.includes(cb.value);
    });

    togglePermissions();
    document.getElementById('user-modal').style.display = 'block';
}

function togglePermissions() {
    const role = document.getElementById('user-role').value;
    const section = document.getElementById('permissions-section');
    if (role === 'administrator') {
        section.style.opacity = '0.5';
        section.style.pointerEvents = 'none';
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
    } else {
        section.style.opacity = '1';
        section.style.pointerEvents = 'auto';
    }
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
