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
            'main_seats_count' => (int)$_POST['main_seats_count'],
            'extra_seats_count' => (int)$_POST['extra_seats_count'],
            'price_main' => (float)$_POST['price_main'],
            'price_extra' => (float)$_POST['price_extra'],
            'price_per_day' => (float)$_POST['price_main'], // Legacy compatibility
            'price_per_hour' => (float)($_POST['price_per_hour'] ?? 0),
            'capacity' => (int)$_POST['main_seats_count'] + (int)$_POST['extra_seats_count'],
            'status' => $_POST['status'] ?? 'free',
            'equipment' => $_POST['equipment'] ?? []
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
if (is_array($classes)) {
    foreach($classes as $c) {
        if (is_array($c) && isset($c['id'])) $classMap[$c['id']] = $c['name'] ?? 'N/A';
    }
}
if (!is_array($items)) $items = [];

$pageTitle = 'Управление номерным фондом';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📋 Список номеров и ресурсов</h2>
    </div>
    <div class="table-responsive"><table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Номер</th>
                <th>Класс</th>
                <th>Места (осн+доп)</th>
                <th>Цена (осн/доп)</th>
                <th>Почасово</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i):
                if (!is_array($i)) continue; ?>
            <tr>
                <td><?php echo $i['id'] ?? ''; ?></td>
                <td><strong><?php echo htmlspecialchars($i['room_number'] ?? ''); ?></strong></td>
                <td><?php echo htmlspecialchars($classMap[$i['room_class_id'] ?? 0] ?? 'N/A'); ?></td>
                <td><?php echo ($i['main_seats_count'] ?? 1); ?> + <?php echo ($i['extra_seats_count'] ?? 0); ?></td>
                <td>
                    <span title="Основное место"><?php echo number_format((float)($i['price_main'] ?? $i['price_per_day'] ?? 0), 0, ',', ' '); ?> ₽</span> /
                    <span title="Доп. место" style="color: #666;"><?php echo number_format((float)($i['price_extra'] ?? 0), 0, ',', ' '); ?> ₽</span>
                </td>
                <td><?php echo ($i['price_per_hour'] ?? 0) > 0 ? number_format((float)$i['price_per_hour'], 0, ',', ' ').' ₽' : '—'; ?></td>
                <td><span class="status-badge" style="background:rgba(0,0,0,0.05); color:#333;"><?php echo htmlspecialchars($i['status'] ?? 'free'); ?></span></td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот номер?');">
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
    <h3 id="form-title">➕ Добавить номер / Ресурс</h3>
    <form method="post" class="modern-form">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">
        <div class="grid-2">
            <div>
                <label>Номер / Название</label>
                <input type="text" name="room_number" id="item-num" required placeholder="Напр: 101 или Сауна-1">
            </div>
            <div>
                <label>Тип (Класс)</label>
                <select name="room_class_id" id="item-class" required>
                    <option value="">-- Выберите класс --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-4" style="margin-top: 15px;">
            <div>
                <label>Основных мест</label>
                <input type="number" name="main_seats_count" id="item-main-seats" value="1" min="1" required>
            </div>
            <div>
                <label>Цена осн. места (₽)</label>
                <input type="number" name="price_main" id="item-price-main" required>
            </div>
            <div>
                <label>Доп. мест</label>
                <input type="number" name="extra_seats_count" id="item-extra-seats" value="0" min="0">
            </div>
            <div>
                <label>Цена доп. места (₽)</label>
                <input type="number" name="price_extra" id="item-price-extra" value="0">
            </div>
        </div>

        <div class="grid-1" style="margin-top: 15px;">
            <label>Оснащение номера</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px;">
                <?php
                $eqOptions = ['TV' => '📺 Телевизор', 'AC' => '❄️ Кондиционер', 'Fridge' => '🧊 Холодильник', 'Safe' => '🔐 Сейф', 'Wifi' => '📶 Wi-Fi', 'Balcony' => '🌅 Балкон', 'Teapot' => '☕ Чайник', 'Hairdryer' => '💨 Фен'];
                foreach($eqOptions as $key => $label): ?>
                    <label style="display:flex; align-items:center; gap:8px; margin:0; cursor:pointer; font-weight: normal;">
                        <input type="checkbox" name="equipment[]" value="<?php echo $key; ?>" class="eq-check" data-key="<?php echo $key; ?>" style="width:auto; margin:0;">
                        <?php echo $label; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid-2" style="margin-top: 15px;">
            <div>
                <label>Цена за час (для почасовых)</label>
                <input type="number" name="price_per_hour" id="item-hour-price" value="0">
            </div>
            <div>
                <label>Начальный статус</label>
                <select name="status" id="item-status">
                    <option value="free">Свободен</option>
                    <option value="reserved">Резерв (Тех. обслуживание)</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 30px;">Сохранить объект</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Очистить форму</button>
        </div>
    </form>
</div>

<script>
    function editItem(item) {
        document.getElementById('form-title').textContent = '📝 Редактировать: ' + item.room_number;
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-num').value = item.room_number;
        document.getElementById('item-class').value = item.room_class_id || '';
        document.getElementById('item-main-seats').value = item.main_seats_count || item.capacity || 1;
        document.getElementById('item-extra-seats').value = item.extra_seats_count || 0;
        document.getElementById('item-price-main').value = item.price_main || item.price_per_day || 0;
        document.getElementById('item-price-extra').value = item.price_extra || 0;
        document.getElementById('item-hour-price').value = item.price_per_hour || 0;
        document.getElementById('item-status').value = item.status || 'free';

        // Reset checkboxes
        document.querySelectorAll('.eq-check').forEach(cb => cb.checked = false);
        if (item.equipment && Array.isArray(item.equipment)) {
            item.equipment.forEach(key => {
                const cb = document.querySelector(`.eq-check[value="${key}"]`);
                if (cb) cb.checked = true;
            });
        }

        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('form-title').textContent = '➕ Добавить номер / Ресурс';
        document.getElementById('item-id').value = '';
        document.getElementById('item-num').value = '';
        document.getElementById('item-class').value = '';
        document.getElementById('item-main-seats').value = '1';
        document.getElementById('item-extra-seats').value = '0';
        document.getElementById('item-price-main').value = '';
        document.getElementById('item-price-extra').value = '0';
        document.getElementById('item-hour-price').value = '0';
        document.getElementById('item-status').value = 'free';
        document.querySelectorAll('.eq-check').forEach(cb => cb.checked = false);
    }
</script>

<?php include 'includes/footer.php'; ?>
