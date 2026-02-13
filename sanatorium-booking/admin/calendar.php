<?php require_once "auth.php";

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_booking') {
    $duration = (int)($_POST['duration'] ?? 1);
    $bookingData = [
        'room_id' => (int)$_POST['room_id'],
        'check_in' => $_POST['date'],
        'check_out' => date('Y-m-d', strtotime($_POST['date'] . " +$duration days")),
        'persons' => 1,
        'phone' => $_POST['phone'],
        'status' => 'confirmed',
        'client_name' => $_POST['client_name']
    ];
    $bookingManager->createBooking($bookingData);
    header('Location: calendar.php?success=1');
    exit;
}

$rooms = $store->findAll('rooms');
$calendar = $store->findAll('room_calendar');

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$period = new DatePeriod(new DateTime($startDate), new DateInterval('P1D'), (new DateTime($endDate))->modify('+1 day'));
$dates = [];
foreach ($period as $date) { $dates[] = $date->format('Y-m-d'); }

$occupancy = [];
foreach ($calendar as $entry) { $occupancy[$entry['room_id']][$entry['date']] = $entry['status']; }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Календарь занятости</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <style>
        .calendar-table { border-collapse: collapse; width: 100%; }
        .calendar-table th, .calendar-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .status-booked { background: #f8d7da; color: #721c24; }
        .status-free { background: #d4edda; color: #155724; cursor: pointer; }
        #modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
        .modal-content { background:#fff; padding:20px; border-radius:8px; width:300px; }
    </style>
</head>
<body>
    <div class="mica-card">
        <h2>📅 Календарь занятости</h2>
        <form method="get" style="margin-bottom:20px;">
            <input type="date" name="start_date" value="<?php echo $startDate; ?>">
            <input type="date" name="end_date" value="<?php echo $endDate; ?>">
            <button type="submit">Показать</button>
        </form>
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Номер</th>
                    <?php foreach ($dates as $date): ?><th><?php echo date('d.m', strtotime($date)); ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                    <?php foreach ($dates as $date):
                        $status = $occupancy[$room['id']][$date] ?? 'free';
                        $onclick = ($status === 'free') ? "openModal({$room['id']}, '{$room['room_number']}', '{$date}')" : "";
                    ?>
                        <td class="status-<?php echo $status; ?>" onclick="<?php echo $onclick; ?>">
                            <?php echo ($status !== 'free' ? 'X' : ''); ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="modal">
        <div class="modal-content">
            <h3>Бронирование</h3>
            <p>Номер: <span id="m-num"></span> | Дата: <span id="m-date"></span></p>
            <form method="post">
                <input type="hidden" name="action" value="quick_booking">
                <input type="hidden" name="room_id" id="m-id">
                <input type="hidden" name="date" id="m-input-date">
                <p>Имя гостя:<br><input type="text" name="client_name" required style="width:100%;"></p>
                <p>Телефон:<br><input type="tel" name="phone" required style="width:100%;"></p>
                <p>Количество дней:<br><input type="number" name="duration" value="1" min="1" required style="width:100%;"></p>
                <button type="submit">Забронировать</button>
                <button type="button" onclick="document.getElementById('modal').style.display='none'">Отмена</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id, num, date) {
            document.getElementById('m-id').value = id;
            document.getElementById('m-input-date').value = date;
            document.getElementById('m-num').textContent = num;
            document.getElementById('m-date').textContent = date;
            document.getElementById('modal').style.display = 'flex';
        }
    </script>
</body>
</html>
