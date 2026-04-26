<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

$token = $_GET['token'] ?? '';
$user = Auth::authenticate($token);
if (!$user) {
    die('Unauthorized');
}

$id = $_GET['id'] ?? '';
$task = Storage::getById('tasks.json', $id);

if (!$task) {
    die('Task not found');
}

$settings = Storage::read('settings.json');
$deptName = 'Прочее';
if (isset($settings['departments'])) {
    foreach ($settings['departments'] as $d) {
        if ($d['id'] == $task['department_id']) {
            $deptName = $d['name'];
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет по заявке #<?php echo $task['id']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #333; line-height: 1.6; }
        .header { border-bottom: 2px solid #333; padding-bottom: 20px; mb: 40px; display: flex; justify-content: space-between; align-items: center; }
        .title { font-size: 24px; font-weight: bold; }
        .meta { margin: 20px 0; display: grid; grid-template-cols: 1fr 1fr; gap: 20px; }
        .meta-item { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        .meta-label { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #666; margin-bottom: 5px; }
        .meta-value { font-size: 16px; font-weight: bold; }
        .description { margin-top: 40px; padding: 20px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #6366f1; }
        .history { margin-top: 40px; }
        .history table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .history th, .history td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-size: 12px; }
        .history th { background: #f4f4f4; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Печать отчета</button>
    </div>

    <div class="header">
        <div>
            <div class="title">Заявка #<?php echo $task['id']; ?></div>
            <div style="color: #666;"><?php echo $settings['system_name'] ?? 'Служба ХОП'; ?></div>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: bold;"><?php echo strtoupper($task['status']); ?></div>
            <div style="font-size: 12px; color: #666;"><?php echo $task['created_at']; ?></div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">Отдел</div>
            <div class="meta-value"><?php echo $deptName; ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Приоритет</div>
            <div class="meta-value"><?php echo strtoupper($task['priority']); ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Дедлайн (SLA)</div>
            <div class="meta-value"><?php echo $task['deadline'] ?? '-'; ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Исполнитель</div>
            <div class="meta-value"><?php echo $task['executor_name'] ?? 'Не назначен'; ?></div>
        </div>
    </div>

    <div class="description">
        <div class="meta-label" style="margin-bottom: 10px;">Описание проблемы</div>
        <div style="white-space: pre-wrap;"><?php echo htmlspecialchars($task['description']); ?></div>
    </div>

    <div class="history">
        <div class="meta-label">Журнал событий</div>
        <table>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Событие</th>
                    <th>Пользователь</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($task['history'] as $h): ?>
                <tr>
                    <td><?php echo $h['at']; ?></td>
                    <td><?php echo $h['msg']; ?></td>
                    <td><?php echo $h['user']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 60px; font-size: 10px; color: #999; text-align: center;">
        Документ сформирован автоматически в системе CRM PRO. Дата формирования: <?php echo date('Y-m-d H:i:s'); ?>
    </div>
</body>
</html>
