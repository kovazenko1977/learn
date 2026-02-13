<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('rooms', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'room_number' => $_POST['room_number'],
            'room_class_id' => (int)$_POST['room_class_id'],
            'price_per_day' => (float)$_POST['price_per_day'],
            'capacity' => (int)$_POST['capacity'],
            'status' => $_POST['status'] ?? 'free'
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('rooms', (int)$_POST['id']);
    }
    header('Location: rooms.php');
    exit;
}

$items = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
$classMap = [];
foreach($classes as $c) $classMap[$c['id']] = $c['name'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Номера</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="rooms.php">Номера</a>
            <a href="procedures.php">Процедуры</a>
            <a href="services.php">Услуги</a>
            <a href="packages.php">Пакеты</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
            <a href="text_blocks.php">Тексты</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    <main class="mica-card">
        <h2>📋 Справочник номеров</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Номер</th>
                    <th>Класс</th>
                    <th>Цена</th>
                    <th>Мест</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?php echo $i['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($i['room_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($classMap[$i['room_class_id'] ?? 0] ?? 'N/A'); ?></td>
                    <td><?php echo number_format($i['price_per_day'], 0, ',', ' '); ?> ₽</td>
                    <td><?php echo $i['capacity']; ?></td>
                    <td><?php echo $i['status']; ?></td>
                    <td>
                        <button onclick='editItem(<?php echo json_encode($i); ?>)'>Edit</button>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Удалить?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $i['id']; ?>">
                            <button type="submit" style="background:#dc3545;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 id="form-title">➕ Добавить номер</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="item-id">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <p>Номер комнаты:<br><input type="text" name="room_number" id="item-num" required style="width:100%;"></p>
                <p>Класс:<br>
                    <select name="room_class_id" id="item-class" required style="width:100%;">
                        <option value="">-- Выберите класс --</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>Цена за сутки:<br><input type="number" name="price_per_day" id="item-price" required style="width:100%;"></p>
                <p>Вместимость:<br><input type="number" name="capacity" id="item-cap" required style="width:100%;"></p>
                <p>Статус:<br>
                    <select name="status" id="item-status" style="width:100%;">
                        <option value="free">Свободен</option>
                        <option value="reserved">Резерв</option>
                        <option value="booked">Занят</option>
                    </select>
                </p>
            </div>
            <button type="submit">Сохранить</button>
            <button type="button" onclick="resetForm()" style="background:#6c757d;">Очистить</button>
        </form>
    </main>
    <script>
        function editItem(item) {
            document.getElementById('form-title').textContent = '📝 Редактировать номер';
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-num').value = item.room_number;
            document.getElementById('item-class').value = item.room_class_id || '';
            document.getElementById('item-price').value = item.price_per_day;
            document.getElementById('item-cap').value = item.capacity;
            document.getElementById('item-status').value = item.status;
        }
        function resetForm() {
            document.getElementById('form-title').textContent = '➕ Добавить номер';
            document.getElementById('item-id').value = '';
            document.getElementById('item-num').value = '';
            document.getElementById('item-class').value = '';
            document.getElementById('item-price').value = '';
            document.getElementById('item-cap').value = '';
            document.getElementById('item-status').value = 'free';
        }
    </script>
</body>
</html>
