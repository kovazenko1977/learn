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

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Управление пользователями</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <section class="card">
        <h2>Добавить пользователя</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Роль</label>
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
                <label>Код (4 цифры)</label>
                <input type="text" name="code" maxlength="4" required>
            </div>
            <div class="form-group">
                <label>Служба (для исполнителей)</label>
                <select name="service_id">
                    <option value="">Нет</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Создать</button>
        </form>
    </section>

    <section class="card">
        <h2>Список пользователей</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Роль</th>
                        <th>Код</th>
                        <th>Служба</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo $u['name']; ?></td>
                        <td><?php echo $u['role']; ?></td>
                        <td><?php echo $u['code']; ?></td>
                        <td><?php
                            if ($u['service_id']) {
                                foreach ($services as $s) if ($s['id'] == $u['service_id']) echo $s['name'];
                            } else {
                                echo '-';
                            }
                        ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Удалить?')">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
