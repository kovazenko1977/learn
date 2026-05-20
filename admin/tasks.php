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
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach(array_reverse($tasks) as $t): ?>
                <div style="padding: 15px; border-radius: 10px; background: #ffffff; border: 1px solid rgba(0,0,0,0.08); border-left: 5px solid #0078d4; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid #f0f0f0; padding-bottom: 5px;">
                        <strong style="color: #0078d4; font-size: 0.9rem;"><?php echo htmlspecialchars($t['assignee']); ?></strong>
                        <span style="font-size: 0.75rem; color: #888;"><?php echo $t['created_at']; ?></span>
                    </div>
                    <p style="margin: 0; color: #323130; font-size: 1rem; line-height: 1.4; font-weight: 500;">
                        <?php echo nl2br(htmlspecialchars($t['text'])); ?>
                    </p>
                    <div style="margin-top: 10px; text-align: right;">
                        <span style="font-size: 0.7rem; padding: 2px 8px; background: #e1f0fe; color: #0078d4; border-radius: 4px; font-weight: 600;">В РАБОТЕ</span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($tasks)): ?>
                <div style="text-align: center; padding: 40px; color: #888;">Задач пока нет</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
