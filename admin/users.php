<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

if (!Auth::isAdmin()) {
    header('Location: index.php');
    exit;
}

$userStore = Auth::getUserStore();
$users = $userStore->getAll();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username'] ?? '');
        $pin = $_POST['pin'] ?? '';
        $role = $_POST['role'] ?? 'editor';

        if ($username && strlen($pin) === 6 && is_numeric($pin)) {
            $exists = false;
            foreach ($users as $u) {
                if ($u['username'] === $username) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $users[] = [
                    'id' => uniqid(),
                    'username' => $username,
                    'pin' => password_hash($pin, PASSWORD_BCRYPT),
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $userStore->set($users);
                Auth::log("Добавлен пользователь: $username");
                $success = "Пользователь добавлен";
            } else {
                $error = "Пользователь уже существует";
            }
        } else {
            $error = "Некорректные данные (PIN должен быть из 6 цифр)";
        }
    }

    if (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        if ($id !== $_SESSION['user_id']) {
            $users = array_filter($users, fn($u) => $u['id'] !== $id);
            $userStore->set(array_values($users));
            Auth::log("Удален пользователь (ID: $id)");
            $success = "Пользователь удален";
        } else {
            $error = "Нельзя удалить самого себя";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link active" href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link" href="logs.php"><i class="bi bi-list-check"></i> Журнал действий</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5 d-flex justify-content-between align-items-center">
            <h2>Пользователи</h2>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal">Добавить пользователя</button>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Имя пользователя</th>
                            <th>Роль</th>
                            <th>Дата создания</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                            <td><span class="badge <?php echo $u['role'] === 'admin' ? 'bg-primary' : 'bg-secondary'; ?>"><?php echo $u['role']; ?></span></td>
                            <td><small class="text-muted"><?php echo $u['created_at'] ?? '-'; ?></small></td>
                            <td class="text-end">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Вы уверены?')">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Имя пользователя</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">PIN-код (6 цифр)</label>
                            <input type="password" name="pin" class="form-control" maxlength="6" pattern="\d{6}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Роль</label>
                            <select name="role" class="form-select">
                                <option value="editor">Редактор</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_user" class="btn btn-primary px-4 rounded-pill">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
