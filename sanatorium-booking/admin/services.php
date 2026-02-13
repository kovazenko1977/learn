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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Доп. услуги</title>
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
        <h2>📋 Дополнительные услуги</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Цена</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?php echo $i['id']; ?></td>
                    <td><?php echo htmlspecialchars($i['name']); ?></td>
                    <td><?php echo number_format($i['price'], 0, ',', ' '); ?> ₽</td>
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

        <h3 id="form-title">➕ Добавить услугу</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="item-id">
            <p>Название:<br><input type="text" name="name" id="item-name" required style="width:100%;"></p>
            <p>Цена:<br><input type="number" name="price" id="item-price" required style="width:100%;"></p>
            <button type="submit">Сохранить</button>
            <button type="button" onclick="resetForm()" style="background:#6c757d;">Очистить</button>
        </form>
    </main>
    <script>
        function editItem(item) {
            document.getElementById('form-title').textContent = '📝 Редактировать услугу';
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-name').value = item.name;
            document.getElementById('item-price').value = item.price;
        }
        function resetForm() {
            document.getElementById('form-title').textContent = '➕ Добавить услугу';
            document.getElementById('item-id').value = '';
            document.getElementById('item-name').value = '';
            document.getElementById('item-price').value = '';
        }
    </script>
</body>
</html>
