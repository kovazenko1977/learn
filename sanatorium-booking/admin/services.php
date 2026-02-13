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
    if ($_POST['action'] === 'save_service') {
        $data = [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'price' => (float)$_POST['price']
        ];
        $store->save('extra_services', $data);
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $store->delete('extra_services', (int)$_POST['id']);
    }
    header('Location: services.php');
    exit;
}

$services = $store->findAll('extra_services');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление услугами</title>
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
        <section class="mica-card">
            <h2>Дополнительные услуги</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['id']); ?></td>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><?php echo htmlspecialchars($s['price']); ?> руб.</td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Удалить?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:red; cursor:pointer;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 8px;">
            <h3>Добавить услугу</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_service">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Название</label>
                        <input type="text" name="name" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Цена</label>
                        <input type="number" name="price" required style="width:100%; padding:8px;">
                    </div>
                </div>
                <button type="submit" class="btn" style="margin-top: 20px;">Сохранить</button>
            </form>
        </section>
    </main>
</body>
</html>
