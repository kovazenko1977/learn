<?php
require_once "auth.php";
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Tasks\TaskManager;

$store = new JsonStore(__DIR__ . '/../data');
$taskManager = new TaskManager($store);

if (isset($_POST['text'])) {
    $taskManager->addTask($_POST['text'], $_POST['assignee']);
}

$tasks = $taskManager->getTasks();
$pageTitle = 'Задачи персонала';
include 'includes/header.php';
?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    <div class="mica-card">
        <h3>Новая задача</h3>
        <form method="POST">
            <div class="form-group"><label>Что сделать?</label><textarea name="text" required style="width:100%"></textarea></div>
            <div class="form-group"><label>Исполнитель</label><input type="text" name="assignee" required style="width:100%" placeholder="Имя или отдел"></div>
            <button type="submit" class="btn btn-primary">Поставить задачу</button>
        </form>
    </div>
    <div class="mica-card">
        <h3>Список поручений</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach(array_reverse($tasks) as $t): ?>
                <div style="padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.02); border-left: 4px solid #0078d4;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong><?php echo htmlspecialchars($t['assignee']); ?></strong>
                        <span style="font-size: 0.8rem; color: #666;"><?php echo $t['created_at']; ?></span>
                    </div>
                    <p style="margin: 5px 0;"><?php echo htmlspecialchars($t['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
