<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');

$id = (int)($_GET['type_id'] ?? 0);
$type = null;
foreach($classes as $c) if($c['id'] == $id) $type = $c;

if (!$type) {
    $type = !empty($classes) ? $classes[0] : null;
}

if (!$type) die("Система не настроена. Добавьте типы объектов в админ-панели.");

$filteredRooms = array_filter($rooms, function($r) use ($type) {
    return $r['room_class_id'] == $type['id'];
});

$config = json_decode(file_get_contents(__DIR__ . '/data/form_config.json'), true);
$formTitle = $type['booking_type'] === 'hourly' ? ($config['sauna']['title'] ?? 'Почасовое бронирование') : ($config['general']['title'] ?? 'Бронирование номеров');
$fields = $type['booking_type'] === 'hourly' ? ($config['sauna']['fields'] ?? []) : ($config['fields'] ?? []);

function isFieldEnabled($fields, $key) { return $fields[$key]['enabled'] ?? true; }
function isFieldRequired($fields, $key) { return $fields[$key]['required'] ?? true; }
function getFieldLabel($fields, $key, $default) { return $fields[$key]['label'] ?? $default; }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($formTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .type-tabs { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; }
        .type-tab { padding: 10px 20px; background: #eee; border-radius: 20px; text-decoration: none; color: #666; white-space: nowrap; font-weight: 600; font-size: 0.9rem; }
        .type-tab.active { background: var(--primary-color); color: white; }
        .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 15px; }
        .slot { padding: 10px; border: 1px solid #ddd; text-align: center; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .slot:hover { border-color: var(--primary-color); background: rgba(0,120,212,0.05); }
        .slot.selected { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .slot.busy { background: #f5f5f5; color: #ccc; cursor: not-allowed; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="type-tabs">
            <?php foreach($classes as $c): ?>
                <a href="?type_id=<?php echo $c['id']; ?>" class="type-tab <?php echo $type['id'] == $c['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($c['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <h1><?php echo htmlspecialchars($formTitle); ?></h1>
        <p style="margin-bottom: 30px;"><?php echo htmlspecialchars($type['description']); ?></p>

        <form id="unified-booking-form">
            <input type="hidden" name="booking_type" value="<?php echo $type['booking_type']; ?>">

            <div class="form-section">
                <div class="form-group">
                    <label>Выберите номер/ресурс</label>
                    <select name="room_id" id="object_id" required>
                        <?php foreach($filteredRooms as $r): ?>
                            <option value="<?php echo $r['id']; ?>"
                                    data-price-main="<?php echo $r['price_main'] ?? $r['price_per_day']; ?>"
                                    data-price-extra="<?php echo $r['price_extra'] ?? 0; ?>"
                                    data-price-hour="<?php echo $r['price_per_hour'] ?? 0; ?>">
                                №<?php echo htmlspecialchars($r['room_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Дата заезда</label>
                        <input type="date" name="date" id="booking_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <?php if($type['booking_type'] !== 'hourly'): ?>
                    <div class="form-group">
                        <label>Количество суток</label>
                        <input type="number" name="duration" id="duration" value="<?php echo $type['min_duration']; ?>" min="<?php echo $type['min_duration']; ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <?php if($type['booking_type'] === 'hourly' && ($type['show_slots'] ?? true)): ?>
                    <div id="slots-container">
                        <label>Выберите время (часы)</label>
                        <div class="time-slots" id="time-slots"></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-section">
                <h3>Данные гостя</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Пол (для подселения)</label>
                        <select name="guest_gender" required>
                            <option value="male">👨 Мужской</option>
                            <option value="female">👩 Женский</option>
                        </select>
                    </div>
                    <?php if($type['booking_type'] !== 'hourly'): ?>
                    <div class="form-group">
                        <label>Тип места</label>
                        <select name="seat_type" id="seat_type" required>
                            <option value="main">Основное место</option>
                            <option value="extra">Дополнительное место</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <?php if (isFieldEnabled($fields, 'client_name')): ?>
                    <div class="form-group">
                        <label><?php echo getFieldLabel($fields, 'client_name', 'Ваше ФИО'); ?></label>
                        <input type="text" name="client_name" <?php echo isFieldRequired($fields, 'client_name') ? 'required' : ''; ?>>
                    </div>
                    <?php endif; ?>

                    <?php if (isFieldEnabled($fields, 'phone')): ?>
                    <div class="form-group">
                        <label><?php echo getFieldLabel($fields, 'phone', 'Телефон'); ?></label>
                        <input type="tel" name="phone" placeholder="+7..." <?php echo isFieldRequired($fields, 'phone') ? 'required' : ''; ?>>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <input type="hidden" name="check_in" id="check_in">
            <input type="hidden" name="check_out" id="check_out">

            <div id="price-summary" style="padding: 20px; background: var(--primary-color); color: white; border-radius: 12px; margin: 20px 0; text-align: center; font-size: 1.2rem;">
                Предварительная стоимость: <strong id="total-sum">0</strong> ₽
            </div>

            <button type="submit" id="btn-submit" class="btn-primary" style="width:100%; padding: 15px; font-size: 1.1rem; border-radius: 30px;">Оформить бронирование</button>
        </form>
    </div>

    <script>
        const typeData = <?php echo json_encode($type); ?>;
        const objectSelect = document.getElementById('object_id');
        const dateInput = document.getElementById('booking_date');
        const slotsGrid = document.getElementById('time-slots');
        const durationInput = document.getElementById('duration');
        const seatTypeSelect = document.getElementById('seat_type');

        let selectedSlots = [];

        async function updateView() {
            const roomId = objectSelect.value;
            const date = dateInput.value;
            if(!roomId || !date) return;

            if(typeData.booking_type === 'hourly' && typeData.show_slots) {
                const resp = await fetch(`api/v1.php?action=sauna/slots&room_id=${roomId}&date=${date}`);
                const data = await resp.json();
                slotsGrid.innerHTML = '';
                selectedSlots = [];
                data.slots.forEach(slot => {
                    const div = document.createElement('div');
                    div.className = 'slot' + (slot.busy ? ' busy' : '');
                    div.textContent = slot.time;
                    if(!slot.busy) div.onclick = () => toggleSlot(slot.time, div);
                    slotsGrid.appendChild(div);
                });
            }
            calculatePrice();
        }

        function toggleSlot(time, el) {
            if(selectedSlots.includes(time)) {
                selectedSlots = selectedSlots.filter(s => s !== time);
                el.classList.remove('selected');
            } else {
                selectedSlots.push(time);
                el.classList.add('selected');
            }
            selectedSlots.sort();
            calculatePrice();
        }

        function calculatePrice() {
            const opt = objectSelect.selectedOptions[0];
            if(!opt) return;
            let total = 0;
            const date = dateInput.value;

            if(typeData.booking_type === 'hourly') {
                const price = parseFloat(opt.dataset.priceHour);
                total = selectedSlots.length * price;
                if(selectedSlots.length > 0) {
                    document.getElementById('check_in').value = `${date} ${selectedSlots[0]}:00`;
                    const last = parseInt(selectedSlots[selectedSlots.length-1]);
                    document.getElementById('check_out').value = `${date} ${String(last + 1).padStart(2, '0')}:00`;
                }
            } else {
                const seatType = seatTypeSelect ? seatTypeSelect.value : 'main';
                const price = (seatType === 'extra') ? parseFloat(opt.dataset.priceExtra) : parseFloat(opt.dataset.priceMain);
                const days = parseInt(durationInput.value) || 1;
                total = days * price;
                document.getElementById('check_in').value = `${date} 14:00:00`;
                const outDate = new Date(date);
                outDate.setDate(outDate.getDate() + days);
                document.getElementById('check_out').value = outDate.toISOString().split('T')[0] + " 12:00:00";
            }
            document.getElementById('total-sum').textContent = total.toLocaleString();
        }

        objectSelect.onchange = updateView;
        dateInput.onchange = updateView;
        if(durationInput) durationInput.oninput = calculatePrice;
        if(seatTypeSelect) seatTypeSelect.onchange = calculatePrice;
        window.onload = updateView;

        document.getElementById('unified-booking-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const obj = Object.fromEntries(formData.entries());
            obj.check_in = document.getElementById('check_in').value;
            obj.check_out = document.getElementById('check_out').value;
            if(typeData.booking_type === 'hourly') obj.is_hourly = true;

            if(!obj.check_in || !obj.check_out) { alert("Выберите время проживания!"); return; }

            const resp = await fetch('api/v1.php?action=booking/create', {
                method: 'POST',
                body: JSON.stringify(obj)
            });
            const result = await resp.json();
            if(result.success) {
                alert('Успешно забронировано! Ожидайте подтверждения.');
                location.reload();
            } else {
                alert('Ошибка: Недостаточно мест или нарушение правил подселения.');
            }
        };
    </script>
</body>
</html>
