<?php
require_once __DIR__ .../../../s../autoload.php';
use App\Database\JsonStore;
use App\Booking\BookingManager;

$store = new JsonStore(__DIR__ .../../data');
$bookingManager = new BookingManager($store);

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
$packages = $store->findAll('packages');

$classMap = [];
foreach($classes as $c) $classMap[$c['id']] = $c;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)$_POST['room_id'];
    $room = null;
    foreach($rooms as $r) if($r['id'] == $roomId) $room = $r;
    $type = $classMap[$room['room_class_id']] ?? null;

    $duration = (int)($_POST['duration'] ?? 1);
    $checkIn = $_POST['check_in'];
    $isHourly = ($type && $type['booking_type'] === 'hourly');

    if ($isHourly) {
        $checkInTime = $_POST['check_in_time'] ?? '12:00';
        $fullCheckIn = "$checkIn $checkInTime:00";
        $fullCheckOut = date('Y-m-d H:i:s', strtotime("$fullCheckIn +$duration hours"));
    } else {
        $fullCheckIn = "$checkIn 14:00:00";
        $fullCheckOut = date('Y-m-d 12:00:00', strtotime("$checkIn +$duration days"));
    }

    $data = [
        'room_id' => $roomId,
        'client_name' => $_POST['client_name'],
        'phone' => $_POST['phone'],
        'guest_gender' => $_POST['guest_gender'] ?? 'male',
        'seat_type' => $_POST['seat_type'] ?? 'main',
        'check_in' => $fullCheckIn,
        'check_out' => $fullCheckOut,
        'persons' => (int)($_POST['persons'] ?? 1),
        'status' => $_POST['status'] ?? 'reserved',
        'is_hourly' => $isHourly,
        'package_id' => !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null,
        'admin_notes' => $_POST['admin_notes'] ?? ''
    ];

    $id = $bookingManager->createBooking($data);
    if ($id) {
        header("Location: calendar.php?success=created");
        exit;
    } else {
        $error = "Ошибка: Номер занят или нарушены правила подселения.";
    }
}

$preRoomId = $_GET['room_id'] ?? null;
$preDate = $_GET['date'] ?? date('Y-m-d');

$pageTitle = 'Новая бронь';
include 'includ../header.php';
?>

<div class="m-card">
    <?php if (isset($error)): ?>
        <div class="badge-m badge-m-danger" style="display: block; padding: 10px; margin-bottom: 15px; border-radius: 8px;"><?php echo $error; ?../div>
    <?php endif; ?>

    <form method="post" id="mobile-booking-form">
        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Объек../label>
            <select name="room_id" id="m-room-id" class="form-control" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;" onchange="toggleTypeFields()">
                <?php foreach ($rooms as $r):
                    $type = $classMap[$r['room_class_id']] ?? ['booking_type' => 'daily'];
                ?>
                    <option value="<?php echo $r['id']; ?>"
                            data-type="<?php echo $type['booking_type']; ?>"
                            <?php echo $preRoomId == $r['id'] ? 'selected' : ''; ?>>
                        №<?php echo $r['room_number']; ?> (<?php echo $type['name']; ?>)
                   ../option>
                <?php endforeach; ?>
           ../select>
       ../div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Пол гост../label>
                <select name="guest_gender" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <option value="male">👨 Муж../option>
                    <option value="female">👩 Жен../option>
               ../select>
           ../div>
            <div id="seat-type-container">
                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Тип мест../label>
                <select name="seat_type" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <option value="main">Основно../option>
                    <option value="extra">Доп../option>
               ../select>
           ../div>
       ../div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Гость (ФИО../label>
            <input type="text" name="client_name" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
       ../div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Телефо../label>
            <input type="tel" name="phone" required placeholder="+7..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
       ../div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Дат../label>
            <input type="date" name="check_in" value="<?php echo $preDate; ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
       ../div>

        <div id="hourly-fields" style="display: none; margin-bottom: 15px; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Время начал../label>
                <input type="time" name="check_in_time" value="12:00" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
           ../div>
            <div>
                <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Кол-во часо../label>
                <input type="number" name="duration_h" value="1" min="1" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
           ../div>
       ../div>

        <div id="daily-fields" style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Количество суто../label>
            <input type="number" name="duration" id="m-duration" value="1" min="1" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-sizing: border-box;">
       ../div>

        <div id="package-field" style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Пакет услу../label>
            <select name="package_id" id="m-package" onchange="updateDuration()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <option value="">Без пакет../option>
                <?php foreach ($packages as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-days="<?php echo $p['duration_days']; ?>"><?php echo $p['name']; ?../option>
                <?php endforeach; ?>
           ../select>
       ../div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">Стату../label>
            <select name="status" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <option value="reserved">Резер../option>
                <option value="booked">Проживае../option>
           ../select>
       ../div>

        <div style="display: flex; gap: 10px; margin-top: 25px;">
            <a href="calendar.php" class="btn-m" style="background: #f1f5f9; color: #1e293b; flex: 1; text-align:center; padding-top:12px;">Отмен../a>
            <button type="submit" class="btn-m btn-m-primary" style="flex: 2;">Создат../button>
       ../div>
   ../form>
</div>

<script>
function toggleTypeFields() {
    const sel = document.getElementById('m-room-id');
    const opt = sel.options[sel.selectedIndex];
    const type = opt.dataset.type;

    const daily = document.getElementById('daily-fields');
    const hourly = document.getElementById('hourly-fields');
    const pkg = document.getElementById('package-field');
    const seatCont = document.getElementById('seat-type-container');

    if(type === 'hourly') {
        daily.style.display = 'none';
        pkg.style.display = 'none';
        seatCont.style.visibility = 'hidden';
        hourly.style.display = 'grid';
        document.querySelector('input[name="duration"]').name = 'duration_unused';
        document.querySelector('input[name="duration_h"]').name = 'duration';
    } else {
        daily.style.display = 'block';
        pkg.style.display = 'block';
        seatCont.style.visibility = 'visible';
        hourly.style.display = 'none';
        document.querySelector('input[name="duration_unused"]')?.setAttribute('name', 'duration');
        document.querySelector('input[name="duration_h"]').name = 'duration_unused_h';
    }
}

function updateDuration() {
    const pkg = document.getElementById('m-package');
    const opt = pkg.options[pkg.selectedIndex];
    const days = opt.getAttribute('data-days');
    if (days) {
        document.getElementById('m-duration').value = days;
    }
}
window.onload = toggleTypeFields;
</script>

<?php include 'includ../footer.php'; ?>
