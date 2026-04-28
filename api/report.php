<?php
require_once __DIR__ . '/../includes/Auth.php';

$user = Auth::authenticate();
if (!$user) {
    die("Unauthorized");
}

$tasks = Storage::getData('tasks');
$id = $_GET['id'] ?? '';
$task = null;
foreach ($tasks as $t) {
    if ($t['id'] === $id) {
        $task = $t;
        break;
    }
}

if (!$task) die("Task not found");

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет по заявке #<?php echo h($task['id']); ?></title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; line-height: 1.6; color: #333; padding: 40px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .row { margin-bottom: 10px; }
        .label { font-weight: bold; width: 200px; display: inline-block; }
        .status { padding: 4px 8px; border-radius: 4px; background: #eee; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Заявка на обслуживание #<?php echo h($task['id']); ?></h1>
        <p>Сгенерировано: <?php echo date('d.m.Y H:i'); ?></p>
    </div>

    <div class="row"><span class="label">Статус:</span> <span class="status"><?php echo h($task['status']); ?></span></div>
    <div class="row"><span class="label">Приоритет:</span> <?php echo h($task['priority']); ?></div>
    <div class="row"><span class="label">Категория:</span> <?php echo h($task['category']); ?></div>
    <div class="row"><span class="label">Создана:</span> <?php echo h($task['created_at']); ?> (<?php echo h($task['created_by_name']); ?>)</div>
    <div class="row"><span class="label">Исполнитель:</span> <?php echo h($task['executor_name'] ?: 'Не назначен'); ?></div>

    <div class="row" style="margin-top: 20px;">
        <span class="label">Описание:</span>
        <p><?php echo nl2br(h($task['description'])); ?></p>
    </div>

    <?php if (!empty($task['comments'])): ?>
    <div class="header" style="margin-top: 40px;"><h3>Комментарии</h3></div>
    <?php foreach ($task['comments'] as $c): ?>
        <div style="margin-bottom: 15px; border-left: 3px solid #ccc; padding-left: 10px;">
            <strong><?php echo h($c['user_name']); ?></strong> (<?php echo h($c['created_at']); ?>):<br>
            <?php echo h($c['text']); ?>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>