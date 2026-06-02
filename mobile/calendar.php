<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Calendar\CalendarManager;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/../data');
$calendarManager = new CalendarManager($store);
$bookingManager = new BookingManager($store);

$startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime($startDate . ' +14 days'));

// Handle POST actions before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    if ($bookingManager->updateBookingStatus($id, $status)) {
        header("Location: calendar.php?start_date=$startDate&success=1");
        exit;
    }
}

$rooms = $store->findAll('rooms');
$dates = $calendarManager->getDateRange($startDate, $endDate);
$occupancy = $calendarManager->getOccupancyData($startDate, $endDate);
$bookings = $store->findAll('bookings');
$allPackages = $store->findAll('packages');

$bookingMap = [];
if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (isset($b['id'])) $bookingMap[$b['id']] = $b;
    }
}

$packageMap = [];
if (is_array($allPackages)) {
    foreach ($allPackages as $p) {
        if (isset($p['id'])) $packageMap[$p['id']] = $p['name'];
    }
}

$pageTitle = 'Шахматка';
include 'includes/header.php';
?>

<div class="m-card" style="padding: 12px; margin-bottom: 12px;">
    <form method="get" style="display: flex; gap: 8px; align-items: center;">
        <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="form-control" style="font-size: 0.8rem; padding: 6px; margin-bottom:0; flex: 1;">
        <button type="submit" class="btn-m btn-m-primary" style="padding: 6px 16px; width: auto; font-size: 0.8rem;">Показать</button>
    </form>
</div>

<div class="m-card" style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0;">
    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table style="border-collapse: collapse; width: 100%; font-size: 0.75rem; min-width: 600px;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px 8px; text-align: left; border-right: 1px solid #e2e8f0; position: sticky; left: 0; background: #f8fafc; z-index: 20; min-width: 50px;">№</th>
                    <?php foreach ($dates as $date):
                        $isWeekend = in_array(date('N', strtotime($date)), [6, 7]);
                    ?>
                        <th style="padding: 8px; text-align: center; min-width: 45px; border-right: 1px solid #e2e8f0; <?php echo $isWeekend ? 'background: rgba(0,0,0,0.03);' : ''; ?>">
                            <div style="font-weight: 700; color: #1e293b;"><?php echo date('d', strtotime($date)); ?></div>
                            <div style="font-size: 0.6rem; color: #64748b; font-weight: 400; text-transform: uppercase;"><?php echo date('D', strtotime($date)); ?></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td style="padding: 12px 8px; font-weight: 700; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #e2e8f0; position: sticky; left: 0; background: #fff; z-index: 10; color: var(--mobile-primary);">
                            <?php echo $room['room_number']; ?>
                        </td>
                        <?php foreach ($dates as $date):
                            $rid = $room['id'] ?? 0;
                            $occ = $occupancy[$rid][$date] ?? ['status' => 'free'];
                            $status = $occ['status'];
                            $bookingId = $occ['booking_id'] ?? null;

                            $cellStyle = "";
                            if ($status === 'reserved') $cellStyle = "background-color: #fff3cd;";
                            if ($status === 'booked') $cellStyle = "background-color: #fee2e2;";

                            $onclick = $bookingId ? "showBookingDetails($bookingId, '{$room['room_number']}')" : "quickReserve({$rid}, '{$room['room_number']}', '{$date}')";
                        ?>
                            <td onclick="<?php echo $onclick; ?>"
                                style="padding: 0; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; <?php echo $cellStyle; ?> cursor: pointer;">
                                <div style="height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <?php if ($status !== 'free'): ?>
                                        <div style="width: 8px; height: 8px; background: currentColor; border-radius: 50%; opacity: 0.5;"></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 15px; display: flex; gap: 15px; font-size: 0.7rem; color: #64748b; padding: 0 5px;">
    <div style="display:flex; align-items:center; gap:4px;"><div style="width:10px; height:10px; background:#fff3cd; border-radius:2px;"></div> Резерв</div>
    <div style="display:flex; align-items:center; gap:4px;"><div style="width:10px; height:10px; background:#fee2e2; border-radius:2px;"></div> Занят</div>
</div>

<!-- Details Drawer (Bottom Sheet) -->
<div id="m-details-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:flex-end;">
    <div style="background:#fff; width:100%; border-radius: 20px 20px 0 0; padding: 20px 20px calc(20px + var(--safe-area-bottom)); box-sizing: border-box; max-height: 85vh; overflow-y: auto;">
        <div style="width: 40px; height: 4px; background: #e2e8f0; border-radius: 2px; margin: 0 auto 20px;"></div>

        <div id="m-details-content">
            <!-- Content will be injected by JS -->
        </div>

        <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 10px;">
            <div id="m-actions-container" style="display: flex; flex-direction: column; gap: 10px;"></div>
            <button class="btn-m" onclick="closeDetails()" style="background:#f1f5f9; color:#1e293b; margin-top: 5px;">Закрыть</button>
        </div>
    </div>
</div>

<script>
const bookings = <?php echo json_encode($bookingMap); ?>;
const packageMap = <?php echo json_encode($packageMap); ?>;

function showBookingDetails(id, roomNum) {
    const b = bookings[id];
    if (!b) return;

    let statusLabel = 'Новое';
    let statusClass = 'badge-m-warning';
    if (b.status === 'booked') { statusLabel = 'Проживает'; statusClass = 'badge-m-success'; }
    if (b.status === 'confirmed') { statusLabel = 'Завершено'; statusClass = 'badge-m-success'; }
    if (b.status === 'cancelled') { statusLabel = 'Отменено'; statusClass = 'badge-m-danger'; }
    if (b.status === 'reserved') { statusLabel = 'Резерв'; statusClass = 'badge-m-warning'; }

    let html = `
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Гость</div>
                <div style="font-weight: 800; font-size: 1.2rem; color: #1e293b;">${b.client_name || 'N/A'}</div>
                <div style="color: var(--mobile-primary); font-weight: 600; font-size: 0.95rem; margin-top: 2px;">${b.phone || ''}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Номер</div>
                <div style="font-weight: 800; font-size: 1.2rem; color: var(--mobile-primary);">№${roomNum}</div>
            </div>
        </div>

        <div class="m-stats-grid" style="margin-bottom: 20px;">
            <div class="m-stat-item" style="text-align: left; padding: 10px;">
                <div class="m-stat-label">Заезд</div>
                <div style="font-weight: 700; color: #1e293b;">${b.check_in}</div>
            </div>
            <div class="m-stat-item" style="text-align: left; padding: 10px;">
                <div class="m-stat-label">Выезд</div>
                <div style="font-weight: 700; color: #1e293b;">${b.check_out}</div>
            </div>
        </div>

        <div style="margin-bottom: 15px; padding: 12px; background: #f8fafc; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b;">Статус</span>
                <span class="badge-m ${statusClass}">${statusLabel}</span>
            </div>
            <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #64748b;">Пакет</span>
                <span style="font-weight: 600; color: #1e293b;">${packageMap[b.package_id] || 'Без пакета'}</span>
            </div>
        </div>

        \${b.admin_notes ? `
        <div style="margin-bottom: 15px; padding: 12px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
            <div style="font-size: 0.7rem; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 5px;">Заметки</div>
            <div style="font-size: 0.9rem; color: #92400e;">\${b.admin_notes}</div>
        </div>
        ` : ''}
    `;

    document.getElementById('m-details-content').innerHTML = html;

    // Actions
    let actionsHtml = '';
    if (b.status === 'reserved') {
        actionsHtml += `<button class="btn-m btn-m-primary" onclick="updateBookingStatus(\${id}, 'booked')">Оформить заезд (Заселить)</button>`;
    } else if (b.status === 'booked') {
        actionsHtml += `<button class="btn-m" style="background: #f97316; color: white;" onclick="updateBookingStatus(\${id}, 'confirmed')">Оформить выезд</button>`;
    }

    if (b.status !== 'cancelled' && b.status !== 'confirmed') {
        actionsHtml += `<button class="btn-m" style="background: #fee2e2; color: #991b1b;" onclick="updateBookingStatus(\${id}, 'cancelled')">Отменить бронирование</button>`;
    }

    document.getElementById('m-actions-container').innerHTML = actionsHtml;
    document.getElementById('m-details-overlay').style.display = 'flex';
}

function quickReserve(roomId, roomNum, date) {
    location.href = \`create_booking.php?room_id=\${roomId}&date=\${date}\`;
}

function closeDetails() {
    document.getElementById('m-details-overlay').style.display = 'none';
}

function updateBookingStatus(id, status) {
    let msg = "Вы уверены?";
    if (status === 'cancelled') msg = "Действительно отменить бронирование?";
    if (!confirm(msg)) return;

    // Use a simple form submission or fetch for status update
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'calendar.php';
    form.innerHTML = \`
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="\${id}">
        <input type="hidden" name="status" value="\${status}">
    \`;
    document.body.appendChild(form);
    form.submit();
}

// Close drawer on overlay click
document.getElementById('m-details-overlay').onclick = function(e) {
    if (e.target === this) closeDetails();
}
</script>

<?php include 'includes/footer.php'; ?>
