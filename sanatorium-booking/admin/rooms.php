<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_room') {
    $store->save('rooms', [
        'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'room_number' => $_POST['room_number'],
        'room_class_id' => (int)$_POST['room_class_id'],
        'price_per_day' => (float)$_POST['price_per_day'],
        'capacity' => (int)$_POST['capacity'],
        'status' => 'free'
    ]);
    header('Location: rooms.php');
    exit;
}
$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Номера</title><link rel="stylesheet" href="../public/assets/css/admin.css"></head>
<body>
    <header><nav><a href="dashboard.php">Бронирования</a> <a href="rooms.php">Номера</a> <a href="room_classes.php">Классы</a> <a href="calendar.php">Календарь</a></nav></header>
    <div class="mica-card">
        <h2>Номера</h2>
        <table>
            <thead><tr><th>ID</th><th>Номер</th><th>Класс</th><th>Цена</th><th>Мест</th></tr></thead>
            <tbody>
                <?php foreach ($rooms as $r):
                    $className = "Неизвестно";
                    foreach($classes as $c) if($c['id'] == $r['room_class_id']) $className = $c['name'];
                ?>
                <tr><td><?php echo $r['id']; ?></td><td><?php echo $r['room_number']; ?></td><td><?php echo $className; ?></td><td><?php echo $r['price_per_day']; ?></td><td><?php echo $r['capacity']; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Добавить номер</h3>
        <form method="post">
            <input type="hidden" name="action" value="save_room">
            <p>Номер: <input type="text" name="room_number" required></p>
            <p>Класс:
                <select name="room_class_id">
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>Цена: <input type="number" name="price_per_day" required></p>
            <p>Мест: <input type="number" name="capacity" required></p>
            <button type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>
