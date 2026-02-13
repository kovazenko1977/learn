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

$blocks = $manager->getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Текстовые блоки</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <div class="mica-card">
        <h2>Текстовые блоки</h2>
        <table>
            <thead><tr><th>Slug</th><th>Контент</th><th>Действия</th></tr></thead>
            <tbody>
                <?php foreach ($blocks as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['slug']); ?></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($b['content'], 0, 50, "...")); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                            <button type="submit">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Добавить блок</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <p>Slug: <input type="text" name="slug" required></p>
            <p>Контент:<br><textarea name="content" required style="width:100%; height:100px;"></textarea></p>
            <button type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>
