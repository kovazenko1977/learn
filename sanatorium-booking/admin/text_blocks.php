<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Helpers\TextBlockManager;
$store = new JsonStore(__DIR__ . '/../data');
$manager = new TextBlockManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $manager->save([
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'slug' => $_POST['slug'],
            'content' => $_POST['content']
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $manager->delete((int)$_POST['id']);
    }
    header('Location: text_blocks.php');
    exit;
}

$items = $manager->getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Текстовые блоки</title>
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
        <h2>📋 Текстовые блоки для сайта</h2>
        <table>
            <thead><tr><th>Slug</th><th>Контент</th><th>Действия</th></tr></thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($i['slug']); ?></code></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($i['content'], 0, 100, "...")); ?></td>
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

        <h3 id="form-title">➕ Добавить блок</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="item-id">
            <p>Slug (код):<br><input type="text" name="slug" id="item-slug" required style="width:100%;" placeholder="например: header-text"></p>
            <p>Контент (HTML):<br><textarea name="content" id="item-content" required style="width:100%; height:150px;"></textarea></p>
            <button type="submit">Сохранить</button>
            <button type="button" onclick="resetForm()" style="background:#6c757d;">Очистить</button>
        </form>
    </main>
    <script>
        function editItem(item) {
            document.getElementById('form-title').textContent = '📝 Редактировать блок';
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-slug').value = item.slug;
            document.getElementById('item-content').value = item.content;
        }
        function resetForm() {
            document.getElementById('form-title').textContent = '➕ Добавить блок';
            document.getElementById('item-id').value = '';
            document.getElementById('item-slug').value = '';
            document.getElementById('item-content').value = '';
        }
    </script>
</body>
</html>
