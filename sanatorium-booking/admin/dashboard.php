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
        if (is_array($r) && isset($r['id'])) $roomMap[$r['id']] = $r['room_number'] ?? ('ID '.$r['id']);
    }
}
if (!is_array($bookings)) $bookings = [];

$statusLabels = [
    'new' => 'Новое',
    'reserved' => 'Зарезервировано',
    'booked' => 'Занято (заехали)',
    'confirmed' => 'Подтверждено',
    'cancelled' => 'Отменено'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        $bookingManager->updateBookingStatus($id, $status);
    } elseif ($_POST['action'] === 'update_notes') {
        $id = (int)$_POST['id'];
        $notes = $_POST['admin_notes'];
        $bookingManager->updateBookingNotes($id, $notes);
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
                <th>Заметки</th>
                <th>Статус</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($bookings) as $b):
                if (!is_array($b)) continue; ?>
            <tr>
                <td><?php echo $b['id'] ?? ''; ?></td>
                <td><strong><?php echo htmlspecialchars($b['client_name'] ?? 'N/A'); ?></strong></td>
                <td><?php echo htmlspecialchars($b['check_in'] ?? ''); ?> — <?php echo htmlspecialchars($b['check_out'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($roomMap[$b['room_id'] ?? 0] ?? 'Room '.($b['room_id'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($b['phone'] ?? ''); ?></td>
                <td><?php echo number_format((float)($b['total_price'] ?? 0), 0, ',', ' '); ?> ₽</td>
                <td style="font-size: 0.85rem; max-width: 200px; color: #666;">
                    <form method="post" style="margin-bottom:0;">
                        <input type="hidden" name="action" value="update_notes">
                        <input type="hidden" name="id" value="<?php echo $b['id'] ?? ''; ?>">
                        <textarea name="admin_notes" onblur="this.form.submit()" style="font-size: 0.8rem; margin:0; padding:4px; height:40px; border:none; background:transparent; resize:none; overflow-y:auto;"><?php echo htmlspecialchars($b['admin_notes'] ?? ''); ?></textarea>
                    </form>
                </td>
                <td><span class="status-badge status-<?php echo htmlspecialchars($b['status'] ?? 'new'); ?>"><?php echo htmlspecialchars($statusLabels[$b['status'] ?? 'new'] ?? ($b['status'] ?? 'new')); ?></span></td>
                <td>
                    <div style="display:flex; gap:5px; align-items:center;">
                        <?php
                            $today = date('Y-m-d');
                            if (($b['check_in'] ?? '') === $today && ($b['status'] ?? '') === 'reserved'):
                        ?>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                <input type="hidden" name="status" value="booked">
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 0.75rem;">Заселить</button>
                            </form>
                        <?php elseif (($b['check_out'] ?? '') === $today && ($b['status'] ?? '') === 'booked'): ?>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem;">Выселить</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" style="display:inline; margin:0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $b['id'] ?? ''; ?>">
                            <select name="status" onchange="this.form.submit()" style="font-size:0.75rem; padding:4px; width: auto; margin-bottom: 0;">
                                <option value="new" <?php if($b['status']=='new') echo 'selected'; ?>>Новое</option>
                                <option value="reserved" <?php if($b['status']=='reserved') echo 'selected'; ?>>Зарезервировано</option>
                                <option value="booked" <?php if($b['status']=='booked') echo 'selected'; ?>>Занято (заехали)</option>
                                <option value="confirmed" <?php if($b['status']=='confirmed') echo 'selected'; ?>>Подтверждено</option>
                                <option value="cancelled" <?php if($b['status']=='cancelled') echo 'selected'; ?>>Отменено</option>
                            </select>
                        </form>
                    </div>
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
