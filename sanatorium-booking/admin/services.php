<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('extra_services', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'price' => (float)$_POST['price']
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('extra_services', (int)$_POST['id']);
    }
    header('Location: services.php');
    exit;
}

$items = $store->findAll('extra_services');
$pageTitle = 'Дополнительные услуги';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>📋 Платные услуги</h2>
    <div class="table-responsive"><table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Стоимость</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i): if (!is_array($i)) continue; ?>
            <tr>
                <td><?php echo $i['id'] ?? ''; ?></td>
                <td><strong><?php echo htmlspecialchars($i['name']); ?></strong></td>
                <td><?php echo number_format($i['price'], 0, ',', ' '); ?> ₽</td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить услугу?');">
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
    <h3 id="form-title">➕ Добавить услугу</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">
        <div class="grid-2">
            <div>
                <label>Название услуги</label>
                <input type="text" name="name" id="item-name" required>
            </div>
            <div>
                <label>Стоимость (₽)</label>
                <input type="number" name="price" id="item-price" required>
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
        document.getElementById('form-title').textContent = '📝 Редактировать услугу';
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-price').value = item.price;
        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('form-title').textContent = '➕ Добавить услугу';
        document.getElementById('item-id').value = '';
        document.getElementById('item-name').value = '';
        document.getElementById('item-price').value = '';
    }
</script>

<?php include 'includes/footer.php'; ?>
