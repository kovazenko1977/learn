<?php
session_start();
require_once __DIR__ . '/../includes/AuthManager.php';
AuthManager::check();
require_once __DIR__ . '/../includes/SanatoriumManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_room') {
        SanatoriumManager::saveRoom([
            'name' => $_POST['name'],
            'building_id' => $_POST['building_id'],
            'price' => (int)$_POST['price']
        ]);
    }
    if ($action === 'delete_room') {
        SanatoriumManager::deleteRoom($_POST['id']);
    }
    header('Location: rooms.php');
    exit;
}

$rooms = SanatoriumManager::getRooms();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление номерами</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body { display: block; padding: 20px; }</style>
</head>
<body>
    <a href="index.php">← Назад в панель</a>
    <h1>Управление номерами</h1>

    <div class="card">
        <h3>Добавить номер</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_room">
            <div class="form-group">
                <label>Название</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Корпус</label>
                <input type="text" name="building_id" required>
            </div>
            <div class="form-group">
                <label>Цена за сутки</label>
                <input type="number" name="price" required>
            </div>
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Корпус</th>
                    <th>Цена</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['building_id']) ?></td>
                    <td><?= $r['price'] ?> руб.</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_room">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
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
