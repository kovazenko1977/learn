<?php
session_start();
require_once __DIR__ . '/../includes/AuthManager.php';
AuthManager::check();

require_once __DIR__ . '/../includes/BookingManager.php';
require_once __DIR__ . '/../includes/SanatoriumManager.php';

$bookings = BookingManager::getAll();
$info = SanatoriumManager::getInfo();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - <?= htmlspecialchars($info['name'] ?? 'HIS') ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; background: #f0f2f5; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #001529; color: white; padding: 20px; }
        .sidebar h2 { font-size: 1.2em; margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { padding: 12px 0; border-bottom: 1px solid #1f2d3d; cursor: pointer; }
        .sidebar li a { color: white; text-decoration: none; display: block; }
        .sidebar li:hover { color: #1890ff; }
        .sidebar li a:hover { color: #1890ff; }
        .main-content { flex: 1; padding: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; text-transform: uppercase; }
        .status-new { background: #e6f7ff; color: #1890ff; }
        .status-confirmed { background: #f6ffed; color: #52c41a; }
        .status-cancelled { background: #fff1f0; color: #f5222d; }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <h2>HIS Admin</h2>
            <ul>
                <li><a href="index.php">Бронирования</a></li>
                <li><a href="programs.php">Программы</a></li>
                <li><a href="rooms.php">Номера</a></li>
                <li><a href="calendar.php">Календарь</a></li>
                <li><hr></li>
                <li><a href="logout.php" style="color:#ff4d4f">Выход</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <h1>Бронирования</h1>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Гость</th>
                            <th>Программа</th>
                            <th>Заезд</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['id']) ?></td>
                            <td><?= htmlspecialchars($b['guest']['name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($b['program_id'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($b['check_in'] ?? '—') ?></td>
                            <td><span class="status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm" onclick="updateStatus('<?= htmlspecialchars($b['id']) ?>', 'confirmed')">Подтвердить</button>
                                <button class="btn btn-sm" onclick="updateStatus('<?= htmlspecialchars($b['id']) ?>', 'cancelled')" style="color:red">Отменить</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($bookings)): ?>
                            <tr><td colspan="6" style="text-align:center">Нет бронирований</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        function updateStatus(id, status) {
            if (!confirm('Вы уверены?')) return;
            fetch('../api/admin_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status })
            }).then(() => location.reload());
        }
    </script>
</body>
</html>
