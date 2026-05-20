<?php
require_once "auth.php";
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;

$id = (int)($_GET['id'] ?? 0);
$store = new JsonStore(__DIR__ . '/../data');
$booking = $store->findOne('bookings', $id);

if (!$booking) die("Бронирование не найдено");

$guest = $store->findOne('guests', $booking['guest_id']);
$room = $store->findOne('rooms', $booking['room_id']);

$settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Печать ваучера #<?php echo $id; ?></title>
    <style>
        body { font-family: sans-serif; padding: 40px; color: #333; line-height: 1.6; }
        .header { border-bottom: 2px solid #0078d4; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; color: #0078d4; }
        .voucher-title { font-size: 28px; text-transform: uppercase; margin-bottom: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .box { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .label { color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .value { font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; }
        .total { font-size: 24px; text-align: right; margin-top: 30px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()">Распечатать</button>
        <button onclick="window.close()">Закрыть</button>
    </div>

    <div class="header">
        <div class="logo"><?php echo htmlspecialchars($settings['org_name'] ?? 'wesbooking Pro'); ?></div>
        <div style="text-align: right;">
            Дата оформления: <?php echo date('d.m.Y'); ?><br>
            Лицензия №123-456-789
        </div>
    </div>

    <div class="voucher-title">Путевка №<?php echo $id; ?></div>

    <div class="grid">
        <div class="box">
            <div class="label">Гость</div>
            <div class="value"><?php echo htmlspecialchars($booking['client_name']); ?></div>
            <div style="margin-top: 10px;">Тел: <?php echo htmlspecialchars($booking['phone']); ?></div>
            <div>Паспорт: <?php echo htmlspecialchars($guest['citizenship'] ?? '—'); ?></div>
        </div>
        <div class="box">
            <div class="label">Размещение</div>
            <div class="value">Номер <?php echo htmlspecialchars($room['room_number']); ?></div>
            <div style="margin-top: 10px;">Заезд: <?php echo date('d.m.Y H:i', strtotime($booking['check_in'])); ?></div>
            <div>Выезд: <?php echo date('d.m.Y H:i', strtotime($booking['check_out'])); ?></div>
        </div>
    </div>

    <h3>Детализация услуг</h3>
    <table>
        <thead><tr><th>Услуга / Процедура</th><th>Кол-во</th><th>Сумма</th></tr></thead>
        <tbody>
            <tr><td>Проживание (<?php echo htmlspecialchars($booking['seat_type'] ?? 'основное'); ?> место)</td><td>1</td><td>—</td></tr>
            <?php if(!empty($booking['package_id'])): ?>
                <tr><td>Пакет услуг</td><td>1</td><td>—</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total">Итого к оплате: <?php echo number_format($booking['total_price'], 0, ',', ' '); ?> ₽</div>

    <div style="margin-top: 60px; display: flex; justify-content: space-between;">
        <div style="width: 200px; border-top: 1px solid #333; text-align: center; font-size: 12px; padding-top: 5px;">Печать учреждения</div>
        <div style="width: 200px; border-top: 1px solid #333; text-align: center; font-size: 12px; padding-top: 5px;">Подпись администратора</div>
    </div>
</body>
</html>
