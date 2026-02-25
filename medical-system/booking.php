<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$sys = (new \Medical\Core\JsonStore('settings'))->getAll();
if (!($sys['is_booking_enabled'] ?? false)) {
    header('Location: index.php');
    exit;
}

$rm = new \Medical\Core\Managers\RoomManager();
$bm = new \Medical\Core\Managers\BookingManager();

$rooms = $rm->getAll();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_booking') {
        if ($bm->isAvailable($_POST['room_id'], $_POST['check_in'], $_POST['check_out'])) {
            $bm->create([
                'room_id' => $_POST['room_id'],
                'guest_name' => $_POST['guest_name'],
                'guest_phone' => $_POST['guest_phone'],
                'guest_birth_date' => $_POST['guest_birth_date'] ?? '',
                'guest_card_number' => $_POST['guest_card_number'] ?? '',
                'guest_residence' => $_POST['guest_residence'] ?? '',
                'check_in' => $_POST['check_in'],
                'check_out' => $_POST['check_out'],
                'status' => 'confirmed',
                'comment' => $_POST['comment'] ?? ''
            ]);
            $message = 'Бронирование успешно создано';
        } else {
            $message = 'Ошибка: Номер занят на выбранные даты';
        }
    } elseif ($action === 'update_status') {
        $bm->update($_POST['id'], ['status' => $_POST['status']]);
        $message = 'Статус бронирования обновлен';
    }
}

require_once __DIR__ . '/includes/header.php';

$view = $_GET['view'] ?? 'calendar';
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

$startDate = date("$year-$month-01");
$daysInMonth = date('t', strtotime($startDate));
$endDate = date("$year-$month-$daysInMonth");

$allBookings = $bm->getAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Бронирование Номеров</h1>
    <div style="display: flex; gap: 10px;">
        <button class="btn btn-primary" onclick="document.getElementById('newBookingModal').style.display='block'">
            <i data-lucide="plus" class="icon"></i> Новая бронь
        </button>
        <div class="btn-group">
            <a href="?view=calendar&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn <?php echo $view === 'calendar' ? 'btn-primary' : ''; ?>">Календарь</a>
            <a href="?view=list" class="btn <?php echo $view === 'list' ? 'btn-primary' : ''; ?>">Список</a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-info mb-4"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($view === 'calendar'): ?>
    <div class="card mica-effect" style="overflow-x: auto; padding: 0;">
        <div style="padding: 20px; border-bottom: 1px solid var(--win-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 600; font-size: 1.2rem;">
                <?php
                $monthsRu = ['', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
                echo $monthsRu[(int)$month] . ' ' . $year;
                ?>
            </div>
            <div style="display: flex; gap: 5px;">
                <?php
                $prevM = $month - 1; $prevY = $year; if ($prevM < 1) { $prevM = 12; $prevY--; }
                $nextM = $month + 1; $nextY = $year; if ($nextM > 12) { $nextM = 1; $nextY++; }
                ?>
                <a href="?view=calendar&month=<?php echo $prevM; ?>&year=<?php echo $prevY; ?>" class="btn btn-sm">&larr;</a>
                <a href="?view=calendar&month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-sm">Сегодня</a>
                <a href="?view=calendar&month=<?php echo $nextM; ?>&year=<?php echo $nextY; ?>" class="btn btn-sm">&rarr;</a>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <thead>
                <tr>
                    <th style="width: 150px; padding: 10px; border-right: 1px solid var(--win-border); background: rgba(0,0,0,0.02); text-align: left;">Номер</th>
                    <?php for($d=1; $d<=$daysInMonth; $d++):
                        $currDate = sprintf("%04d-%02d-%02d", $year, $month, $d);
                        $isToday = $currDate === date('Y-m-d');
                        $dayW = date('N', strtotime($currDate));
                        $isWeekend = $dayW >= 6;
                    ?>
                        <th style="padding: 10px 5px; text-align: center; font-size: 0.8rem; border-right: 1px solid var(--win-border); <?php echo $isToday ? 'background: rgba(0,120,212,0.1); color: var(--win-accent);' : ''; ?> <?php echo $isWeekend ? 'color: #d13438;' : ''; ?>">
                            <?php echo $d; ?><br>
                            <span style="font-weight: 400; font-size: 0.7rem;"><?php echo ['','Пн','Вт','Ср','Чт','Пт','Сб','Вс'][$dayW]; ?></span>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr style="border-top: 1px solid var(--win-border);">
                        <td style="padding: 10px; border-right: 1px solid var(--win-border); font-weight: 500; background: rgba(0,0,0,0.01);">
                            <?php echo htmlspecialchars($room['name']); ?>
                            <div style="font-size: 0.7rem; color: #666; font-weight: 400;"><?php echo $room['type']; ?></div>
                        </td>
                        <?php for($d=1; $d<=$daysInMonth; $d++):
                            $currDate = sprintf("%04d-%02d-%02d", $year, $month, $d);
                            $booking = null;
                            foreach ($allBookings as $b) {
                                if ($b['room_id'] === $room['id'] && $b['status'] !== 'cancelled') {
                                    if ($currDate >= $b['check_in'] && $currDate < $b['check_out']) {
                                        $booking = $b;
                                        break;
                                    }
                                }
                            }

                            $color = 'transparent';
                            $title = 'Свободно';
                            if ($booking) {
                                switch($booking['status']) {
                                    case 'confirmed': $color = 'rgba(209,52,56,0.2)'; $title = 'Бронь: '.$booking['guest_name']; break;
                                    case 'checked_in': $color = 'rgba(16,124,16,0.2)'; $title = 'Проживает: '.$booking['guest_name']; break;
                                    case 'preliminary': $color = 'rgba(255,185,0,0.2)'; $title = 'Предварительно: '.$booking['guest_name']; break;
                                }
                            }
                        ?>
                            <td style="padding: 0; border-right: 1px solid var(--win-border); background: <?php echo $color; ?>;" title="<?php echo htmlspecialchars($title); ?>">
                                <?php if ($booking && $currDate === $booking['check_in']): ?>
                                    <div style="padding: 2px 5px; font-size: 0.65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer;" onclick='showBookingDetails(<?php echo json_encode($booking); ?>)'>
                                        <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; display: flex; gap: 20px; font-size: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: rgba(255,185,0,0.2); border: 1px solid #ffb900;"></div> Предварительно</div>
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: rgba(209,52,56,0.2); border: 1px solid #d13438;"></div> Подтверждено</div>
        <div style="display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: rgba(16,124,16,0.2); border: 1px solid #107c10;"></div> Проживает</div>
    </div>

<?php else: ?>
    <div class="card mica-effect">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Гость</th>
                    <th>Номер</th>
                    <th>Заезд</th>
                    <th>Выезд</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th style="text-align: right;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allBookings as $b):
                    $room = $rm->getById($b['room_id']);
                ?>
                    <tr style="border-top: 1px solid var(--win-border);">
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($b['guest_name']); ?></strong><br>
                            <span style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($b['guest_phone']); ?></span>
                        </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($room['name'] ?? 'Удален'); ?></td>
                        <td style="padding: 12px;"><?php echo date('d.m.Y', strtotime($b['check_in'])); ?></td>
                        <td style="padding: 12px;"><?php echo date('d.m.Y', strtotime($b['check_out'])); ?></td>
                        <td style="padding: 12px;"><?php echo number_format($b['total_cost'], 2); ?> ₽</td>
                        <td style="padding: 12px;">
                            <?php
                            $statusMap = [
                                'preliminary' => ['bg' => '#fff8e1', 'text' => '#b7791f', 'label' => 'Предварительно'],
                                'confirmed' => ['bg' => '#fde7e9', 'text' => '#d13438', 'label' => 'Подтверждено'],
                                'checked_in' => ['bg' => '#dff6dd', 'text' => '#107c10', 'label' => 'Проживает'],
                                'checked_out' => ['bg' => '#f3f2f1', 'text' => '#605e5c', 'label' => 'Выехал'],
                                'cancelled' => ['bg' => '#f3f2f1', 'text' => '#a19f9d', 'label' => 'Отменено']
                            ];
                            $s = $statusMap[$b['status']] ?? $statusMap['preliminary'];
                            ?>
                            <span style="background: <?php echo $s['bg']; ?>; color: <?php echo $s['text']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                <?php echo $s['label']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <button class="btn btn-sm" onclick='showBookingDetails(<?php echo json_encode($b); ?>)'>Детали</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- New Booking Modal -->
<div id="newBookingModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 500px; margin: 60px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Новое бронирование</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="create_booking">

            <div class="mb-3">
                <label>Номер</label>
                <select name="room_id" class="form-control" required>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?> (<?php echo $r['type']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>ФИО гостя</label>
                <input type="text" name="guest_name" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;" class="mb-3">
                <div>
                    <label>Телефон</label>
                    <input type="text" name="guest_phone" class="form-control" value="+375 " required>
                </div>
                <div>
                    <label>Дата рождения</label>
                    <input type="date" name="guest_birth_date" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;" class="mb-3">
                <div>
                    <label>№ Ист. болезни (если есть)</label>
                    <input type="text" name="guest_card_number" class="form-control" placeholder="0000/2024">
                </div>
                <div>
                    <label>Место жительства</label>
                    <input type="text" name="guest_residence" class="form-control" placeholder="Город...">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;" class="mb-3">
                <div>
                    <label>Дата заезда</label>
                    <input type="date" name="check_in" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div>
                    <label>Дата выезда</label>
                    <input type="date" name="check_out" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label>Комментарий</label>
                <textarea name="comment" class="form-control" rows="2"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn" onclick="document.getElementById('newBookingModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Забронировать</button>
            </div>
        </form>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="bookingDetailsModal" style="display:none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 450px; margin: 80px auto; padding: 32px;">
        <h2 id="det_guest_name">Детали бронирования</h2>
        <div style="margin-bottom: 24px; font-size: 0.95rem;">
            <p><strong>Номер:</strong> <span id="det_room"></span></p>
            <p><strong>Период:</strong> <span id="det_dates"></span></p>
            <p><strong>Телефон:</strong> <span id="det_phone"></span></p>
            <p><strong>Сумма:</strong> <span id="det_price"></span> ₽</p>
            <p><strong>Комментарий:</strong> <span id="det_comment"></span></p>
        </div>

        <form method="POST" id="statusForm">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="det_id">

            <div class="mb-4">
                <label>Изменить статус</label>
                <select name="status" id="det_status" class="form-control">
                    <option value="preliminary">Предварительно</option>
                    <option value="confirmed">Подтверждено</option>
                    <option value="checked_in">Заселение (Проживает)</option>
                    <option value="checked_out">Выезд</option>
                    <option value="cancelled">Отмена</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('bookingDetailsModal').style.display='none'">Закрыть</button>
                <button type="submit" class="btn btn-primary">Сохранить статус</button>
            </div>
        </form>
    </div>
</div>

<script>
function showBookingDetails(b) {
    document.getElementById('det_id').value = b.id;
    document.getElementById('det_guest_name').innerText = b.guest_name;
    document.getElementById('det_phone').innerText = b.guest_phone;
    document.getElementById('det_dates').innerText = b.check_in + ' — ' + b.check_out;
    document.getElementById('det_price').innerText = b.total_cost.toLocaleString();
    document.getElementById('det_comment').innerText = b.comment || '-';
    document.getElementById('det_status').value = b.status;

    document.getElementById('bookingDetailsModal').style.display = 'block';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
