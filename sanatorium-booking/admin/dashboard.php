<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$bookingManager = new BookingManager($store);
$bookings = $store->findAll('bookings');
$rooms = $store->findAll('rooms');

$roomMap = [];
if (is_array($rooms)) {
    foreach ($rooms as $r) {
        if (isset($r['id'])) $roomMap[$r['id']] = $r['room_number'] ?? ('ID '.$r['id']);
    }
}
if (!is_array($bookings)) $bookings = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $booking = $store->findOne('bookings', $id);
    if ($booking) {
        $oldStatus = $booking['status'];
        $booking['status'] = $status;
        $store->save('bookings', $booking);

        if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
            $bookingManager->releaseCalendar($id);
        }
    }
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Список бронирований';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📋 Активные заявки</h2>
        <a href="export_csv.php" class="btn" style="background:#28a745;">📥 Экспорт в CSV</a>
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
                        <select name="status" onchange="this.form.submit()" style="font-size:0.8em; padding:4px; width: auto; margin-bottom: 0;">
                            <option value="new" <?php if($b['status']=='new') echo 'selected'; ?>>Новое</option>
                            <option value="confirmed" <?php if($b['status']=='confirmed') echo 'selected'; ?>>Подтвердить</option>
                            <option value="cancelled" <?php if($b['status']=='cancelled') echo 'selected'; ?>>Отмена</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($bookings)): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding: 40px; color: #888;">Нет активных бронирований</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
