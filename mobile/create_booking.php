<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Booking\BookingManager;

$store = new JsonStore(__DIR__ . '/data');
$bookingManager = new BookingManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration = (int)($_POST['duration'] ?? 1);
    $data = [
        'room_id' => (int)$_POST['room_id'],
        'client_name' => $_POST['client_name'],
        'phone' => $_POST['phone'],
        'check_in' => $_POST['check_in'],
        'check_out' => date('Y-m-d', strtotime($_POST['check_in'] . " +$duration days")),
        'persons' => (int)($_POST['persons'] ?? 1),
        'status' => $_POST['status'] ?? 'reserved',
        'package_id' => !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null,
        'procedure_ids' => !empty($_POST['procedure_ids']) ? array_map('intval', $_POST['procedure_ids']) : [],
        'service_ids' => !empty($_POST['service_ids']) ? array_map('intval', $_POST['service_ids']) : [],
        'citizenship' => $_POST['citizenship'] ?? '',
        'address' => $_POST['address'] ?? '',
        'admin_notes' => $_POST['admin_notes'] ?? ''
    ];

    $id = $bookingManager->createBooking($data);
    if ($id) {
        header("Location: calendar.php?success=created");
        exit;
    } else {
        $error = "Ошибка: Номер занят или данные некорректны.";
    }
}

$rooms = $store->findAll('rooms');
$packages = $store->findAll('packages');
$procedures = $store->findAll('procedures');
$services = $store->findAll('extra_services');

$preRoomId = $_GET['room_id'] ?? null;
$preDate = $_GET['date'] ?? date('Y-m-d');

$pageTitle = 'Новая бронь';
include 'includes/header.php';
?>

<div class="m-card">
    <?php if (isset($error)): ?>
        <div class="badge-m badge-m-danger" style="display: block; padding: 10px; margin-bottom: 15px; border-radius: 8px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Номер</label>
            <select name="room_id" class="form-control" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo $preRoomId == $r['id'] ? 'selected' : ''; ?>>
                        №<?php echo $r['room_number']; ?> (<?php echo $r['price_per_day']; ?> BYN/сут)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Гость (ФИО)</label>
            <input type="text" name="client_name" required placeholder="Иванов Иван Иванович" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Телефон</label>
            <input type="tel" name="phone" required placeholder="+375..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Дата заезда</label>
                <input type="date" name="check_in" value="<?php echo $preDate; ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Дней</label>
                <input type="number" name="duration" id="m-duration" value="1" min="1" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Путёвка (Пакет)</label>
            <select name="package_id" id="m-package" onchange="updateDuration()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <option value="">Без пакета</option>
                <?php foreach ($packages as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-days="<?php echo $p['duration_days']; ?>"><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Процедуры и услуги</label>
            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 8px;">Выберите дополнительные позиции:</div>
                <div style="max-height: 150px; overflow-y: auto;">
                    <?php foreach ($procedures as $p): ?>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 0.85rem;">
                            <input type="checkbox" name="procedure_ids[]" value="<?php echo $p['id']; ?>"> <?php echo $p['name']; ?>
                        </label>
                    <?php endforeach; ?>
                    <?php foreach ($services as $s): ?>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 0.85rem;">
                            <input type="checkbox" name="service_ids[]" value="<?php echo $s['id']; ?>"> <?php echo $s['name']; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Статус</label>
            <select name="status" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <option value="reserved">Резерв</option>
                <option value="booked">Проживает (Заселение)</option>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Заметки</label>
            <textarea name="admin_notes" placeholder="Дополнительная информация..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 80px; box-sizing: border-box;"></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="calendar.php" class="btn-m" style="background: #f1f5f9; color: #1e293b; flex: 1;">Отмена</a>
            <button type="submit" class="btn-m btn-m-primary" style="flex: 2;">Создать бронь</button>
        </div>
    </form>
</div>

<script>
function updateDuration() {
    const pkg = document.getElementById('m-package');
    const opt = pkg.options[pkg.selectedIndex];
    const days = opt.getAttribute('data-days');
    if (days) {
        document.getElementById('m-duration').value = days;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
