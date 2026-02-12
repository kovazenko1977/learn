<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_class') {
        $data = [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name']
        ];
        $store->save('room_classes', $data);
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $store->delete('room_classes', (int)$_POST['id']);
    }
    header('Location: room_classes.php');
    exit;
}

$classes = $store->findAll('room_classes');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление классами номеров</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="create_booking.php">Новое бронирование</a>
            <a href="rooms.php">Номера</a>
            <a href="room_classes.php">Классы</a>
            <a href="procedures.php">Процедуры</a>
            <a href="services.php">Услуги</a>
            <a href="packages.php">Пакеты</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    <main>
        <section>
            <h2>Классы номеров</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['id']); ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Удалить?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:red; cursor:pointer;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 8px;">
            <h3>Добавить класс номера</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_class">
                <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                    <div>
                        <label>Название</label>
                        <input type="text" name="name" required style="width:100%; padding:8px;">
                    </div>
                </div>
                <button type="submit" class="btn" style="margin-top: 20px;">Сохранить</button>
            </form>
        </section>
    </main>
</body>
</html>
