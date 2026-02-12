<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_room') {
    $roomData = [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'room_number' => $_POST['room_number'],
        'room_class' => $_POST['room_class'],
        'price_per_day' => (float)$_POST['price_per_day'],
        'capacity' => (int)$_POST['capacity'],
        'status' => $_POST['status']
    ];
    $store->save('rooms', $roomData);
    header('Location: rooms.php');
    exit;
}

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление номерами</title>
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
            <h2>Номера</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер</th>
                        <th>Класс</th>
                        <th>Цена/сут</th>
                        <th>Вместимость</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['id']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_class']); ?></td>
                        <td><?php echo htmlspecialchars($room['price_per_day']); ?> руб.</td>
                        <td><?php echo htmlspecialchars($room['capacity']); ?> чел.</td>
                        <td><?php echo htmlspecialchars($room['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 8px;">
            <h3>Добавить / Редактировать номер</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_room">
                <input type="hidden" name="id" id="room_id">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Номер комнаты</label>
                        <input type="text" name="room_number" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Класс номера</label>
                        <select name="room_class" required style="width:100%; padding:8px;">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Цена за сутки</label>
                        <input type="number" name="price_per_day" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Вместимость</label>
                        <input type="number" name="capacity" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Статус</label>
                        <select name="status" style="width:100%; padding:8px;">
                            <option value="free">Свободен</option>
                            <option value="reserved">Резерв</option>
                            <option value="booked">Занят</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn" style="margin-top: 20px;">Сохранить</button>
            </form>
        </section>
    </main>
</body>
</html>
