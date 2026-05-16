<?php require_once "auth.php";
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('room_classes', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'booking_type' => $_POST['booking_type'] ?? 'daily',
            'show_slots' => isset($_POST['show_slots']),
            'min_duration' => (int)($_POST['min_duration'] ?? 1),
            'buffer_time' => (int)($_POST['buffer_time'] ?? 0)
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('room_classes', (int)$_POST['id']);
    }
    header('Location: room_classes.php');
    exit;
}

$items = $store->findAll('room_classes');
$pageTitle = 'Типы объектов';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>📋 Управление типами объектов</h2>
    <div class="table-responsive"><table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Тип записи</th>
                <th>Слоты</th>
                <th>Мин. время</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i): if (!is_array($i)) continue; ?>
            <tr>
                <td><?php echo $i['id'] ?? ''; ?></td>
                <td><strong><?php echo htmlspecialchars($i['name']); ?></strong></td>
                <td><?php echo ($i['booking_type'] ?? 'daily') === 'daily' ? 'Посуточно' : 'По часам'; ?></td>
                <td><?php echo ($i['show_slots'] ?? false) ? '✅ Да' : '❌ Нет'; ?></td>
                <td><?php echo $i['min_duration'] ?? 1; ?></td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот тип?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $i['id'] ?? ''; ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.8rem;">Удалить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<div class="mica-card">
    <h3 id="form-title">➕ Добавить новый тип объекта</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">

        <div class="grid-2">
            <div>
                <label>Название</label>
                <input type="text" name="name" id="item-name" required placeholder="напр: Коттедж">
            </div>
            <div>
                <label>Тип записи</label>
                <select name="booking_type" id="item-type">
                    <option value="daily">По суткам (Отели, дома)</option>
                    <option value="hourly">По часам (Сауны, беседки, корты)</option>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div>
                <label>Мин. длительность (часов или суток)</label>
                <input type="number" name="min_duration" id="item-min" value="1" min="1">
            </div>
            <div>
                <label>Буферное время (мин. между бронями)</label>
                <input type="number" name="buffer_time" id="item-buffer" value="0" min="0">
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label>
                <input type="checkbox" name="show_slots" id="item-slots">
                Показывать сетку свободных слотов на публичной форме
            </label>
        </div>

        <label>Описание</label>
        <textarea name="description" id="item-desc" style="width:100%; height:80px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2);"></textarea>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Сохранить тип</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Очистить форму</button>
        </div>
    </form>
</div>

<script>
    function editItem(item) {
        document.getElementById('form-title').textContent = '📝 Редактировать тип';
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-desc').value = item.description;
        document.getElementById('item-type').value = item.booking_type || 'daily';
        document.getElementById('item-slots').checked = !!item.show_slots;
        document.getElementById('item-min').value = item.min_duration || 1;
        document.getElementById('item-buffer').value = item.buffer_time || 0;
        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('form-title').textContent = '➕ Добавить тип';
        document.getElementById('item-id').value = '';
        document.getElementById('item-name').value = '';
        document.getElementById('item-desc').value = '';
        document.getElementById('item-type').value = 'daily';
        document.getElementById('item-slots').checked = false;
        document.getElementById('item-min').value = 1;
        document.getElementById('item-buffer').value = 0;
    }
</script>

<?php include 'includes/footer.php'; ?>
