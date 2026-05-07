<?php
require_once __DIR__ . '/core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/data');

$rooms = $store->findAll('rooms');
$classes = $store->findAll('room_classes');
$saunaClassId = null;
foreach($classes as $c) if(stripos($c['name'], 'Сауна') !== false) $saunaClassId = $c['id'];

$saunas = array_filter($rooms, function($r) use ($saunaClassId) {
    return ($r['room_class_id'] == $saunaClassId);
});
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование Сауны</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 15px; }
        .slot { padding: 10px; border: 1px solid #ddd; text-align: center; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .slot:hover { border-color: var(--primary-color); background: rgba(0,120,212,0.05); }
        .slot.selected { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .slot.busy { background: #f5f5f5; color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧖‍♀️ Забронировать Сауну</h1>
        <p>Выберите дату и удобное время. Оплата почасовая.</p>

        <form id="sauna-booking-form">
            <div class="form-group">
                <label>Выберите сауну</label>
                <select name="room_id" id="sauna_id" required>
                    <?php foreach($saunas as $s): ?>
                        <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['price_per_hour']; ?>">
                            <?php echo htmlspecialchars($s['room_number']); ?> (<?php echo $s['price_per_hour']; ?> ₽/час)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Дата</label>
                <input type="date" name="date" id="booking_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div id="slots-container" style="display:none;">
                <label>Доступное время (нажмите, чтобы выбрать часы)</label>
                <div class="time-slots" id="time-slots">
                    <!-- JS generated -->
                </div>
            </div>

            <input type="hidden" name="check_in" id="check_in">
            <input type="hidden" name="check_out" id="check_out">

            <div class="form-group" style="margin-top:20px;">
                <label>Количество человек</label>
                <input type="number" name="persons" value="2" min="1" max="15">
            </div>

            <div class="form-group">
                <label>Ваше имя</label>
                <input type="text" name="client_name" required>
            </div>

            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" required placeholder="+7...">
            </div>

            <div id="price-summary" style="padding: 15px; background: #f8f9fa; border-radius: 8px; margin: 20px 0; display:none;">
                Выбрано часов: <strong id="hours-count">0</strong><br>
                Итого к оплате: <strong id="total-sum">0</strong> ₽
            </div>

            <button type="submit" id="btn-submit" disabled class="btn-primary" style="width:100%;">Забронировать сауну</button>
        </form>
    </div>

    <script>
        const saunaSelect = document.getElementById('sauna_id');
        const dateInput = document.getElementById('booking_date');
        const slotsGrid = document.getElementById('time-slots');
        const summary = document.getElementById('price-summary');

        let selectedSlots = [];

        async function loadSlots() {
            const roomId = saunaSelect.value;
            const date = dateInput.value;
            if(!roomId || !date) return;

            const resp = await fetch(`api/v1.php?action=sauna/slots&room_id=${roomId}&date=${date}`);
            const data = await resp.json();

            slotsGrid.innerHTML = '';
            selectedSlots = [];
            updateSummary();

            data.slots.forEach(slot => {
                const div = document.createElement('div');
                div.className = 'slot' + (slot.busy ? ' busy' : '');
                div.textContent = slot.time;
                if(!slot.busy) {
                    div.onclick = () => toggleSlot(slot.time, div);
                }
                slotsGrid.appendChild(div);
            });
            document.getElementById('slots-container').style.display = 'block';
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
            updateSummary();
        }

        function updateSummary() {
            const price = parseFloat(saunaSelect.selectedOptions[0].dataset.price);
            const count = selectedSlots.length;

            document.getElementById('hours-count').textContent = count;
            document.getElementById('total-sum').textContent = (count * price).toLocaleString();

            summary.style.display = count > 0 ? 'block' : 'none';
            document.getElementById('btn-submit').disabled = count === 0;

            if(count > 0) {
                const date = dateInput.value;
                document.getElementById('check_in').value = `${date} ${selectedSlots[0]}:00`;
                // Simple logic: last slot + 1 hour
                const last = parseInt(selectedSlots[selectedSlots.length-1]);
                document.getElementById('check_out').value = `${date} ${String(last + 1).padStart(2, '0')}:00`;
            }
        }

        saunaSelect.onchange = loadSlots;
        dateInput.onchange = loadSlots;
        window.onload = loadSlots;

        document.getElementById('sauna-booking-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const obj = Object.fromEntries(formData.entries());
            obj.is_hourly = true;

            const resp = await fetch('api/v1.php?action=booking/create', {
                method: 'POST',
                body: JSON.stringify(obj)
            });
            const result = await resp.json();
            if(result.success) {
                alert('Бронирование успешно создано!');
                location.reload();
            } else {
                alert('Ошибка: ' + (result.error || 'не удалось создать бронь'));
            }
        };
    </script>
</body>
</html>
