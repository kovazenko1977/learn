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

$pageTitle = 'Управление номерами';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📋 Список номеров</h2>
    </div>
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
                <td><span class="status-badge" style="background:rgba(0,0,0,0.05); color:#333;"><?php echo $i['status']; ?></span></td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Edit</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот номер?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $i['id']; ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.8rem;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mica-card">
    <h3 id="form-title">➕ Добавить номер</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">
        <div class="grid-2">
            <div>
                <label>Номер комнаты</label>
                <input type="text" name="room_number" id="item-num" required>
            </div>
            <div>
                <label>Класс номера</label>
                <select name="room_class_id" id="item-class" required>
                    <option value="">-- Выберите класс --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Цена за сутки (₽)</label>
                <input type="number" name="price_per_day" id="item-price" required>
            </div>
            <div>
                <label>Вместимость (чел.)</label>
                <input type="number" name="capacity" id="item-cap" required>
            </div>
            <div>
                <label>Текущий статус</label>
                <select name="status" id="item-status">
                    <option value="free">Свободен</option>
                    <option value="reserved">Резерв</option>
                    <option value="booked">Занят</option>
                </select>
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Сохранить</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Очистить</button>
        </div>
    </form>
</div>

<script>
    function editItem(item) {
        document.getElementById('form-title').textContent = '📝 Редактировать номер';
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-num').value = item.room_number;
        document.getElementById('item-class').value = item.room_class_id || '';
        document.getElementById('item-price').value = item.price_per_day;
        document.getElementById('item-cap').value = item.capacity;
        document.getElementById('item-status').value = item.status;
        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
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

<?php include 'includes/footer.php'; ?>
