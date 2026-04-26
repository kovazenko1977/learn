<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::getUser();
if (!$user) {
    http_response_code(401);
    die('Unauthorized');
}

$tasks = Storage::get('tasks');

$filtered = array_filter($tasks, function($t) use ($user) {
    if ($user['role'] === 'admin') return true;
    if ($user['role'] === 'head') return $t['department'] === $user['department'];
    if ($user['role'] === 'executor') return $t['executor_id'] === $user['id'];
    if ($user['role'] === 'employee') return $t['creator_id'] === $user['id'];
    return false;
});

// Since we don't have a PDF library like TCPDF or Dompdf installed,
// and we shouldn't install new packages without diagnosis/need,
// I will implement a "Print-friendly" HTML report that the user can "Save as PDF".
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет по заявкам - Service CRM</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; padding: 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 12px; }
        th { background-color: #f4f4f4; }
        h1 { font-size: 20px; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>Отчет по заявкам на <?php echo date('d.m.Y H:i'); ?></h1>
    <p>Сгенерировано пользователем: <?php echo $user['full_name']; ?></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Заголовок</th>
                <th>Приоритет</th>
                <th>Статус</th>
                <th>Создатель</th>
                <th>Исполнитель</th>
                <th>Дата создания</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filtered as $t): ?>
            <tr>
                <td><?php echo $t['id']; ?></td>
                <td><?php echo $t['title']; ?></td>
                <td><?php echo $t['priority']; ?></td>
                <td><?php echo $t['status']; ?></td>
                <td><?php echo $t['creator_name']; ?></td>
                <td><?php echo $t['executor_name'] ?? '-'; ?></td>
                <td><?php echo $t['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Service CRM PRO - Система управления заявками
    </div>

    <script>
        window.onload = function() {
            // Uncomment to auto-trigger print dialog
            // window.print();
        }
    </script>
</body>
</html>
