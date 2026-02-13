<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('packages', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'base_price' => (float)$_POST['base_price'],
            'duration_days' => (int)$_POST['duration_days'],
            'procedure_ids' => isset($_POST['procedure_ids']) ? array_map('intval', $_POST['procedure_ids']) : []
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('packages', (int)$_POST['id']);
    }
    header('Location: packages.php');
    exit;
}

$items = $store->findAll('packages');
$procedures = $store->findAll('procedures');
$procMap = [];
foreach($procedures as $p) $procMap[$p['id']] = $p['name'];

$pageTitle = 'Путёвки и пакеты';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>📋 Список путёвок</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Дни</th>
                <th>Цена</th>
                <th>Состав (Процедуры)</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i): ?>
            <tr>
                <td><?php echo $i['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($i['name']); ?></strong></td>
                <td><?php echo $i['duration_days']; ?></td>
                <td><?php echo number_format($i['base_price'], 0, ',', ' '); ?> ₽</td>
                <td>
                    <?php
                    $pNames = [];
                    if (!empty($i['procedure_ids'])) {
                        foreach($i['procedure_ids'] as $pid) $pNames[] = htmlspecialchars($procMap[$pid] ?? "Proc $pid");
                    }
                    echo implode(', ', $pNames);
                    ?>
                </td>
                <td>
                    <button class="btn btn-secondary" onclick='editItem(<?php echo json_encode($i); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Удалить путёвку?');">
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
    <h3 id="form-title">➕ Создать путёвку</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="item-id">
        <div class="grid-2">
            <div>
                <label>Название путёвки</label>
                <input type="text" name="name" id="item-name" required>
            </div>
            <div>
                <label>Длительность (дней)</label>
                <input type="number" name="duration_days" id="item-days" required>
            </div>
            <div>
                <label>Базовая стоимость (₽)</label>
                <input type="number" name="base_price" id="item-price" required>
            </div>
        </div>

        <label>Включенные процедуры (удерживайте Ctrl для выбора нескольких)</label>
        <select name="procedure_ids[]" id="item-procs" multiple style="height: 120px; margin-bottom: 15px;">
            <?php foreach($procedures as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Описание</label>
        <textarea name="description" id="item-desc" style="width:100%; height:60px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2);"></textarea>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn">Сохранить</button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Очистить</button>
        </div>
    </form>
</div>

<script>
    function editItem(item) {
        document.getElementById('form-title').textContent = '📝 Редактировать путёвку';
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-days').value = item.duration_days;
        document.getElementById('item-price').value = item.base_price;
        document.getElementById('item-desc').value = item.description;

        // Handle multiple select
        const select = document.getElementById('item-procs');
        const ids = item.procedure_ids || [];
        for (let i = 0; i < select.options.length; i++) {
            select.options[i].selected = ids.includes(parseInt(select.options[i].value));
        }

        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
    function resetForm() {
        document.getElementById('form-title').textContent = '➕ Создать путёвку';
        document.getElementById('item-id').value = '';
        document.getElementById('item-name').value = '';
        document.getElementById('item-days').value = '';
        document.getElementById('item-price').value = '';
        document.getElementById('item-desc').value = '';
        const select = document.getElementById('item-procs');
        for (let i = 0; i < select.options.length; i++) {
            select.options[i].selected = false;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
