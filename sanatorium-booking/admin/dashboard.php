<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');

$roomMap = [];
foreach ($rooms as $r) { $roomMap[$r['id']] = $r['room_number']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $booking = $store->findOne('bookings', $id);
    if ($booking) {
        $booking['status'] = $status;
        $store->save('bookings', $booking);
    }
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.9em; font-weight: bold; }
        .status-new { background: #e7f3ff; color: #007bff; }
        .status-confirmed { background: #d4edda; color: #28a745; }
        .status-cancelled { background: #f8d7da; color: #dc3545; }
    </style>
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
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    <main>
        <div class="mica-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>📋 Список заявок</h2>
                <a href="export_csv.php" class="btn" style="background:#28a745; text-decoration:none; padding:8px 15px; border-radius:6px; color:#fff;">📥 Экспорт в CSV</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Гость</th>
                        <th>Заезд / Выезд</th>
                        <th>Номер</th>
                        <th>Телефон</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($bookings) as $b): ?>
                    <tr>
                        <td><?php echo $b['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($b['client_name'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo $b['check_in']; ?> — <?php echo $b['check_out']; ?></td>
                        <td><?php echo htmlspecialchars($roomMap[$b['room_id']] ?? 'Room '.$b['room_id']); ?></td>
                        <td><?php echo htmlspecialchars($b['phone']); ?></td>
                        <td><?php echo number_format($b['total_price'], 0, ',', ' '); ?> ₽</td>
                        <td><span class="status-badge status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                <select name="status" onchange="this.form.submit()" style="font-size:0.8em; padding:2px;">
                                    <option value="new" <?php if($b['status']=='new') echo 'selected'; ?>>New</option>
                                    <option value="confirmed" <?php if($b['status']=='confirmed') echo 'selected'; ?>>Confirm</option>
                                    <option value="cancelled" <?php if($b['status']=='cancelled') echo 'selected'; ?>>Cancel</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
