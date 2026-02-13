<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('packages', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'base_price' => (float)$_POST['base_price'],
            'duration_days' => (int)$_POST['duration_days']
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('packages', (int)$_POST['id']);
    }
    header('Location: packages.php');
    exit;
}

$items = $store->findAll('packages');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пакеты / Путевки</title>
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
        </nav>
    </header>
    <main class="mica-card">
        <h2>📋 Справочник пакетов (путевок)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Базовая цена</th>
                    <th>Дней</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?php echo $i['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($i['name']); ?></strong><br><small><?php echo htmlspecialchars($i['description']); ?></small></td>
                    <td><?php echo number_format($i['base_price'], 0, ',', ' '); ?> ₽</td>
                    <td><?php echo $i['duration_days']; ?></td>
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

        <h3 id="form-title">➕ Добавить пакет</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="item-id">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <p>Название:<br><input type="text" name="name" id="item-name" required style="width:100%;"></p>
                <p>Базовая цена:<br><input type="number" name="base_price" id="item-price" required style="width:100%;"></p>
                <p>Длительность (дней):<br><input type="number" name="duration_days" id="item-days" style="width:100%;"></p>
                <p style="grid-column:span 2;">Описание:<br><textarea name="description" id="item-description" style="width:100%; height:60px;"></textarea></p>
            </div>
            <button type="submit">Сохранить</button>
            <button type="button" onclick="resetForm()" style="background:#6c757d;">Очистить</button>
        </form>
    </main>
    <script>
        function editItem(item) {
            document.getElementById('form-title').textContent = '📝 Редактировать пакет';
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-name').value = item.name;
            document.getElementById('item-price').value = item.base_price;
            document.getElementById('item-days').value = item.duration_days;
            document.getElementById('item-description').value = item.description;
        }
        function resetForm() {
            document.getElementById('form-title').textContent = '➕ Добавить пакет';
            document.getElementById('item-id').value = '';
            document.getElementById('item-name').value = '';
            document.getElementById('item-price').value = '';
            document.getElementById('item-days').value = '';
            document.getElementById('item-description').value = '';
        }
    </script>
</body>
</html>
