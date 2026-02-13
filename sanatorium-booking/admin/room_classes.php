<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('room_classes', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description']
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('room_classes', (int)$_POST['id']);
    }
    header('Location: room_classes.php');
    exit;
}

$items = $store->findAll('room_classes');
$pageTitle = 'Классы номеров';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>📋 Классификация номеров</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i): ?>
            <tr>
                <td><?php echo $i['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($i['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($i['description']); ?></td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Edit</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот класс?');">
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
    <h3 id="form-title">➕ Добавить класс</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">
        <label>Название класса</label>
        <input type="text" name="name" id="item-name" required placeholder="например, Люкс">

        <label>Описание</label>
        <textarea name="description" id="item-desc" style="width:100%; height:100px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2);"></textarea>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Сохранить</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Очистить</button>
        </div>
    </form>
</div>

<script>
    function editItem(item) {
        document.getElementById('form-title').textContent = '📝 Редактировать класс';
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-desc').value = item.description;
        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('form-title').textContent = '➕ Добавить класс';
        document.getElementById('item-id').value = '';
        document.getElementById('item-name').value = '';
        document.getElementById('item-desc').value = '';
    }
</script>

<?php include 'includes/footer.php'; ?>
