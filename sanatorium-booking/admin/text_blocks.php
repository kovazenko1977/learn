<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Helpers\TextBlockManager;
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$manager = new TextBlockManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $manager->set($_POST['key'], $_POST['content']);
    }
    header('Location: text_blocks.php');
    exit;
}

$blocks = $manager->getAll();
$pageTitle = 'Текстовые блоки';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>📝 Редактирование текстов для посетителей</h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">Эти тексты отображаются в форме бронирования на фронтенде.</p>

    <table>
        <thead>
            <tr>
                <th>Ключ (Slug)</th>
                <th>Содержимое</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($blocks as $block): ?>
            <tr>
                <td style="font-family: monospace; color: #0078d4;"><?php echo htmlspecialchars($block['slug']); ?></td>
                <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($block['content'], 0, 100, "..."))); ?></td>
                <td>
                    <button class="btn btn-secondary" onclick="editBlock('<?php echo addslashes($block['slug']); ?>', '<?php echo addslashes(str_replace("\n", "\\n", str_replace("\r", "", $block['content']))); ?>')" style="padding: 4px 10px; font-size: 0.8rem;">Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mica-card">
    <h3 id="form-title">📝 Редактировать блок</h3>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <label>Ключ блока (не меняется)</label>
        <input type="text" name="key" id="item-key" readonly style="background: rgba(0,0,0,0.05);">

        <label>Содержимое (HTML поддерживается)</label>
        <textarea name="content" id="item-content" style="width:100%; height:200px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2); font-family: inherit;"></textarea>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn">Сохранить изменения</button>
        </div>
    </form>
</div>

<script>
    function editBlock(key, content) {
        document.getElementById('item-key').value = key;
        document.getElementById('item-content').value = content.replace(/\\n/g, '\n');
        document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
    }
</script>

<?php include 'includes/footer.php'; ?>
