<?php
require_once __DIR__ . '/header.php';
requireRole('admin');

use Medical\Core\UserManager;

$userManager = new UserManager($store);

if (isset($_POST['save'])) {
    checkCsrf();
    $data = [
        'id' => $_POST['id'] ?: null,
        'username' => $_POST['username'],
        'name' => $_POST['name'],
        'role' => $_POST['role'],
        'access_code' => $_POST['access_code']
    ];
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    $userManager->create($data);
    echo "<script>showToast('Данные пользователя сохранены');</script>";
}

if (isset($_GET['delete'])) {
    $userManager->delete($_GET['delete']);
    header('Location: staff.php');
    exit;
}

$users = $userManager->getAll();
?>

<div class="win-card mica-effect">
    <h2><i class="lucide-users"></i> Управление персоналом</h2>

    <form method="POST" class="win-card" style="margin-bottom: 30px;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="id" id="userId">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label>Имя</label>
                <input type="text" name="name" id="userName" class="form-win" required placeholder="Иван Иванович">
            </div>
            <div>
                <label>Логин</label>
                <input type="text" name="username" id="userLogin" class="form-win" required placeholder="ivanov">
            </div>
            <div>
                <label>Роль</label>
                <select name="role" id="userRole" class="form-win">
                    <option value="doctor">Врач</option>
                    <option value="cashier">Кассир</option>
                    <option value="nurse">Медсестра</option>
                    <option value="admin">Админ</option>
                </select>
            </div>
            <div>
                <label>Код (6 цифр)</label>
                <input type="text" name="access_code" id="userCode" class="form-win" maxlength="6" required placeholder="000000">
            </div>
            <div>
                <label>Пароль (опц)</label>
                <input type="password" name="password" class="form-win" placeholder="********">
            </div>
            <div>
                <button type="submit" name="save" class="btn-win">Сохранить</button>
            </div>
        </div>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #ccc;">
                <th style="padding: 10px;">Имя</th>
                <th>Логин</th>
                <th>Роль</th>
                <th>Код доступа</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo $u['role']; ?></td>
                <td><code><?php echo $u['access_code']; ?></code></td>
                <td>
                    <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="btn-win-sec" style="padding: 4px 8px;">Ред.</button>
                    <?php if ($u['username'] !== 'admin'): ?>
                        <a href="?delete=<?php echo $u['id']; ?>" class="btn-win-sec" style="color: var(--danger); padding: 4px 8px; text-decoration: none;" onclick="return confirm('Удалить?')">Удалить</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editUser(u) {
    document.getElementById('userId').value = u.id;
    document.getElementById('userName').value = u.name;
    document.getElementById('userLogin').value = u.username;
    document.getElementById('userRole').value = u.role;
    document.getElementById('userCode').value = u.access_code;
    window.scrollTo(0, 0);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
