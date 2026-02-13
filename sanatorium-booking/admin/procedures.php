<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('procedures', [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Процедуры</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        .full-width { grid-column: span 2; }
    </style>
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
        <h2>📋 Справочник процедур</h2>
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
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?php echo $i['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($i['name']); ?></strong><br><small><?php echo htmlspecialchars($i['description']); ?></small></td>
                    <td><?php echo number_format($i['price'], 0, ',', ' '); ?> ₽</td>
                    <td><?php echo $i['duration']; ?> мин.</td>
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

        <h3 id="form-title">➕ Добавить процедуру</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="item-id">
            <div class="form-grid">
                <div>
                    <label>Название</label><br>
                    <input type="text" name="name" id="item-name" required style="width:100%;">
                </div>
                <div>
                    <label>Цена (₽)</label><br>
                    <input type="number" name="price" id="item-price" required style="width:100%;">
                </div>
                <div>
                    <label>Длительность (мин)</label><br>
                    <input type="number" name="duration" id="item-duration" style="width:100%;">
                </div>
                <div class="full-width">
                    <label>Описание</label><br>
                    <textarea name="description" id="item-description" style="width:100%; height:60px;"></textarea>
                </div>
            </div>
            <button type="submit" style="margin-top:20px;">Сохранить</button>
            <button type="button" onclick="resetForm()" style="background:#6c757d; margin-top:20px;">Очистить</button>
        </form>
    </main>

    <script>
        function editItem(item) {
            document.getElementById('form-title').textContent = '📝 Редактировать процедуру';
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-name').value = item.name;
            document.getElementById('item-price').value = item.price;
            document.getElementById('item-duration').value = item.duration;
            document.getElementById('item-description').value = item.description;
            window.scrollTo(0, document.body.scrollHeight);
        }
        function resetForm() {
            document.getElementById('form-title').textContent = '➕ Добавить процедуру';
            document.getElementById('item-id').value = '';
            document.getElementById('item-name').value = '';
            document.getElementById('item-price').value = '';
            document.getElementById('item-duration').value = '';
            document.getElementById('item-description').value = '';
        }
    </script>
</body>
</html>
