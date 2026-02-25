(function() {
    const container = document.getElementById('wes-booking-widget');
    if (!container) return;

    const baseUrl = container.getAttribute('data-url');

    container.innerHTML = `
        <div style="font-family: 'Segoe UI', sans-serif; border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #fff; max-width: 400px;">
            <h3 style="margin-top:0">Онлайн-бронирование</h3>
            <div id="booking-form-step1">
                <label style="display:block; margin-bottom:5px; font-size:14px;">Дата заезда</label>
                <input type="date" id="bw-checkin" style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

                <label style="display:block; margin-bottom:5px; font-size:14px;">Дата выезда</label>
                <input type="date" id="bw-checkout" style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">

                <button id="bw-search" style="width:100%; padding:10px; background:#0078d4; color:#fff; border:none; border-radius:4px; cursor:pointer;">Проверить наличие</button>
            </div>
            <div id="booking-form-step2" style="display:none">
                <div id="bw-available-rooms" style="margin-bottom:15px; max-height: 200px; overflow-y: auto;"></div>
                <div id="bw-contact-info" style="display:none">
                    <input type="text" id="bw-name" placeholder="Ваше имя" style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;">
                    <input type="text" id="bw-phone" placeholder="Ваш телефон" style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;">
                    <button id="bw-confirm" style="width:100%; padding:10px; background:#107c10; color:#fff; border:none; border-radius:4px; cursor:pointer;">Забронировать</button>
                </div>
            </div>
            <div id="bw-message" style="margin-top:15px; font-size:14px; text-align:center;"></div>
        </div>
    `;

    const step1 = container.querySelector('#booking-form-step1');
    const step2 = container.querySelector('#booking-form-step2');
    const msg = container.querySelector('#bw-message');
    let selectedRoomId = null;

    container.querySelector('#bw-search').addEventListener('click', async () => {
        const checkin = container.querySelector('#bw-checkin').value;
        const checkout = container.querySelector('#bw-checkout').value;

        if (!checkin || !checkout) {
            msg.innerText = 'Выберите даты';
            return;
        }

        msg.innerText = 'Поиск...';

        try {
            const response = await fetch(`${baseUrl}api/v1.php?action=check_availability&in=${checkin}&out=${checkout}`);
            const data = await response.json();

            if (data.rooms && data.rooms.length > 0) {
                step1.style.display = 'none';
                step2.style.display = 'block';
                msg.innerText = 'Выберите подходящий номер:';

                const roomList = container.querySelector('#bw-available-rooms');
                roomList.innerHTML = data.rooms.map(r => `
                    <div class="bw-room-item" data-id="${r.id}" style="padding:10px; border:1px solid #eee; margin-bottom:5px; border-radius:4px; cursor:pointer;">
                        <strong>${r.name}</strong><br>
                        <span style="font-size:12px; color:#666">${r.type} • ${r.capacity} мест</span><br>
                        <span style="color:#0078d4">${r.price} ₽/сут</span>
                    </div>
                `).join('');

                roomList.querySelectorAll('.bw-room-item').forEach(item => {
                    item.addEventListener('click', () => {
                        roomList.querySelectorAll('.bw-room-item').forEach(i => i.style.borderColor = '#eee');
                        item.style.borderColor = '#0078d4';
                        selectedRoomId = item.getAttribute('data-id');
                        container.querySelector('#bw-contact-info').style.display = 'block';
                    });
                });
            } else {
                msg.innerText = 'На данные даты свободных номеров нет.';
            }
        } catch (e) {
            msg.innerText = 'Ошибка при поиске. Попробуйте позже.';
        }
    });

    container.querySelector('#bw-confirm').addEventListener('click', async () => {
        const name = container.querySelector('#bw-name').value;
        const phone = container.querySelector('#bw-phone').value;
        const checkin = container.querySelector('#bw-checkin').value;
        const checkout = container.querySelector('#bw-checkout').value;

        if (!name || !phone) {
            msg.innerText = 'Введите контактные данные';
            return;
        }

        msg.innerText = 'Оформление...';

        try {
            const response = await fetch(`${baseUrl}api/v1.php?action=create_web_booking`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    room_id: selectedRoomId,
                    name,
                    phone,
                    checkin,
                    checkout
                })
            });
            const data = await response.json();
            if (data.success) {
                step2.style.display = 'none';
                msg.innerHTML = '<span style="color:#107c10; font-weight:600">Ваша заявка принята!</span><br>Менеджер свяжется с вами для подтверждения.';
            } else {
                msg.innerText = 'Ошибка при оформлении: ' + data.message;
            }
        } catch (e) {
            msg.innerText = 'Ошибка соединения с сервером.';
        }
    });

})();
