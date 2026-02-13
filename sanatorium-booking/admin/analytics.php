<?php require_once "auth.php";

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');
$totalIncome = 0;
foreach ($bookings as $b) { if ($b['status'] !== 'cancelled') $totalIncome += (float)$b['total_price']; }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <div class="mica-card">
        <h2>📊 Аналитика</h2>
        <p>Общий доход: <strong><?php echo number_format($totalIncome, 0, ',', ' '); ?> ₽</strong></p>
        <p>Всего бронирований: <strong><?php echo count($bookings); ?></strong></p>
    </div>
</body>
</html>
