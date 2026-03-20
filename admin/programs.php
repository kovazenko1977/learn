<?php
session_start();
require_once __DIR__ . '/../includes/AuthManager.php';
AuthManager::check();
require_once __DIR__ . '/../includes/ProgramManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_program') {
        ProgramManager::save([
            'name' => $_POST['name'],
            'duration' => (int)$_POST['duration']
        ]);
    }
    if ($action === 'delete_program') {
        ProgramManager::delete($_POST['id']);
    }
    header('Location: programs.php');
    exit;
}

$programs = ProgramManager::getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление программами</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body { display: block; padding: 20px; }</style>
</head>
<body>
    <a href="index.php">← Назад в панель</a>
    <h1>Управление программами</h1>

    <div class="card">
        <h3>Добавить программу</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_program">
            <div class="form-group">
                <label>Название</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Длительность (дней)</label>
                <input type="number" name="duration" required>
            </div>
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Длительность</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($programs as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= $p['duration'] ?> дней</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_program">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" style="color:red; border:none; background:none; cursor:pointer;">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
