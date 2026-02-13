<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('procedures', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => (float)$_POST['price'],
            'duration' => (int)$_POST['duration']
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('procedures', (int)$_POST['id']);
    }
    header('Location: procedures.php');
    exit;
}

$items = $store->findAll('procedures');
$pageTitle = 'Медицинские процедуры';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>📋 Список процедур</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Цена</th>
                <th>Длительность</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i): if (!is_array($i)) continue; ?>
            <tr>
                <td><?php echo $i['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($i['name']); ?></strong></td>
                <td><?php echo number_format($i['price'], 0, ',', ' '); ?> ₽</td>
                <td><?php echo $i['duration']; ?> мин.</td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить процедуру?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $i['id']; ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.8rem;">Удалить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mica-card">
    <h3 id="form-title">➕ Добавить процедуру</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">
        <div class="grid-2">
            <div>
                <label>Название процедуры</label>
                <input type="text" name="name" id="item-name" required>
            </div>
            <div>
                <label>Стоимость (₽)</label>
                <input type="number" name="price" id="item-price" required>
            </div>
            <div>
                <label>Длительность (мин.)</label>
                <input type="number" name="duration" id="item-dur" required>
            </div>
        </div>
        <label>Описание</label>
        <textarea name="description" id="item-desc" style="width:100%; height:80px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2);"></textarea>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Сохранить</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Очистить</button>
        </div>
    </form>
</div>

<script>
    function editItem(item) {
        document.getElementById('form-title').textContent = '📝 Редактировать процедуру';
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-price').value = item.price;
        document.getElementById('item-dur').value = item.duration;
        document.getElementById('item-desc').value = item.description;
        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('form-title').textContent = '➕ Добавить процедуру';
        document.getElementById('item-id').value = '';
        document.getElementById('item-name').value = '';
        document.getElementById('item-price').value = '';
        document.getElementById('item-dur').value = '';
        document.getElementById('item-desc').value = '';
    }
</script>

<?php include 'includes/footer.php'; ?>
