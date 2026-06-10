<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

if (!Auth::isAdmin()) {
    header('Location: index.php');
    exit;
}

$logs = Auth::getLogStore()->getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Журнал действий - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link active" href="logs.php"><i class="bi bi-list-check"></i> Журнал действий</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Журнал действий</h2>
            <p class="text-muted">Последние 1000 операций в системе</p>
        </header>

        <div class="glass-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Дата и время</th>
                            <th>Пользователь</th>
                            <th>Действие</th>
                            <th>IP-адрес</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><small class="text-muted"><?php echo $log['timestamp']; ?></small></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($log['user']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><small class="font-monospace text-muted"><?php echo $log['ip']; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
