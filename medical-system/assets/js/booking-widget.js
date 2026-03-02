(function() {
    const container = document.getElementById('wes-booking-widget');
    if (!container) return;

    const baseUrl = container.getAttribute('data-url');
    let config = {};

    async function init() {
        try {
            const resp = await fetch(`${baseUrl}api/v1.php?action=get_settings`);
            config = await resp.json();
            renderStep1();
        } catch (e) {
            container.innerHTML = '<p style="color:red">Ошибка инициализации виджета</p>';
        }
    }

    function renderStep1() {
        container.innerHTML = `
            <div style="font-family: 'Segoe UI', sans-serif; border: 1px solid #ddd; border-radius: 12px; padding: 24px; background: #fff; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0; color: ${config.primary_color}; font-size: 20px;">${config.title}</h3>
                <div id="booking-form-step1">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-size:13px; font-weight: 600; color: #666;">Дата заезда</label>
                            <input type="date" id="bw-checkin" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-size:13px; font-weight: 600; color: #666;">Дата выезда</label>
                            <input type="date" id="bw-checkout" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">
                        </div>
                    </div>

                    ${config.show_guests ? `
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-size:13px; font-weight: 600; color: #666;">Количество гостей</label>
                        <select id="bw-guests" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">
                            <option value="1">1 человек</option>
                            <option value="2" selected>2 человека</option>
                            <option value="3">3 человека</option>
                            <option value="4">4 человека</option>
                        </select>
                    </div>
                    ` : ''}

                    <button id="bw-search" style="width:100%; padding:14px; background:${config.primary_color}; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight: 600; font-size: 15px; transition: opacity 0.2s;">Проверить наличие</button>
                </div>
                <div id="booking-form-step2" style="display:none">
                    <div id="bw-available-rooms" style="margin-bottom:20px; max-height: 250px; overflow-y: auto; padding: 5px;"></div>
                    <div id="bw-contact-info" style="display:none; border-top: 1px solid #eee; padding-top: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 20px;">
                            <input type="text" id="bw-name" placeholder="Ваше полное имя" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">
                            <input type="text" id="bw-phone" placeholder="Контактный телефон" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">

                            ${config.show_email ? `<input type="email" id="bw-email" placeholder="E-mail для связи" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">` : ''}
                            ${config.show_birthdate ? `
                                <div>
                                    <label style="display:block; margin-bottom:4px; font-size:12px; color: #999;">Дата рождения</label>
                                    <input type="date" id="bw-birthdate" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">
                                </div>
                            ` : ''}

                            ${config.show_packages && config.packages ? `
                                <select id="bw-package" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; box-sizing: border-box;">
                                    <option value="">-- Выберите лечебную программу --</option>
                                    ${config.packages.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                                </select>
                            ` : ''}

                            ${config.show_notes ? `<textarea id="bw-notes" placeholder="Ваши пожелания..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; height: 80px; box-sizing: border-box; resize: none;"></textarea>` : ''}
                        </div>
                        <button id="bw-confirm" style="width:100%; padding:14px; background:#107c10; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight: 600; font-size: 15px;">Забронировать сейчас</button>
                    </div>
                </div>
                <div id="bw-message" style="margin-top:20px; font-size:14px; text-align:center; color: #666;"></div>
            </div>
        `;

        container.querySelector('#bw-search').addEventListener('click', handleSearch);
    }

    async function handleSearch() {
        const checkin = container.querySelector('#bw-checkin').value;
        const checkout = container.querySelector('#bw-checkout').value;
        const msg = container.querySelector('#bw-message');
        const step1 = container.querySelector('#booking-form-step1');
        const step2 = container.querySelector('#booking-form-step2');

        if (!checkin || !checkout) {
            msg.innerText = 'Пожалуйста, выберите даты заезда и выезда';
            msg.style.color = 'red';
            return;
        }

        msg.innerText = 'Ищем свободные номера...';
        msg.style.color = '#666';

        try {
            const response = await fetch(`${baseUrl}api/v1.php?action=check_availability&in=${checkin}&out=${checkout}`);
            const data = await response.json();

            if (data.rooms && data.rooms.length > 0) {
                step1.style.display = 'none';
                step2.style.display = 'block';
                msg.innerText = 'Доступные номера на выбранный период:';

                const roomList = container.querySelector('#bw-available-rooms');
                roomList.innerHTML = data.rooms.map(r => `
                    <div class="bw-room-item" data-id="${r.id}" style="padding:15px; border:2px solid #f0f0f0; margin-bottom:10px; border-radius:10px; cursor:pointer; transition: all 0.2s; position: relative;">
                        <strong style="display:block; font-size: 16px; margin-bottom: 4px;">${r.name}</strong>
                        <span style="font-size:13px; color:#666">${r.type === 'single' ? 'Одноместный' : (r.type === 'double' ? 'Двухместный' : (r.type === 'suite' ? 'Люкс' : r.type))} • ${r.capacity} мест</span>
                        <div style="margin-top: 10px; font-weight: 700; color: ${config.primary_color}; font-size: 17px;">${r.price} ₽ <span style="font-weight: normal; font-size: 13px; color: #999;">/ сут.</span></div>
                        <div class="check-icon" style="display:none; position: absolute; top: 15px; right: 15px; color: #107c10;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                    </div>
                `).join('');

                roomList.querySelectorAll('.bw-room-item').forEach(item => {
                    item.addEventListener('click', () => {
                        roomList.querySelectorAll('.bw-room-item').forEach(i => {
                            i.style.borderColor = '#f0f0f0';
                            i.style.background = '#fff';
                            i.querySelector('.check-icon').style.display = 'none';
                        });
                        item.style.borderColor = config.primary_color;
                        item.style.background = 'rgba(0,120,212,0.02)';
                        item.querySelector('.check-icon').style.display = 'block';

                        selectedRoomId = item.getAttribute('data-id');
                        container.querySelector('#bw-contact-info').style.display = 'block';
                        container.querySelector('#bw-confirm').scrollIntoView({ behavior: 'smooth', block: 'end' });
                    });
                });

                container.querySelector('#bw-confirm').addEventListener('click', handleConfirm);

            } else {
                msg.innerText = 'К сожалению, на эти даты все номера заняты.';
                msg.style.color = '#d13438';
            }
        } catch (e) {
            msg.innerText = 'Произошла ошибка при поиске. Попробуйте обновить страницу.';
        }
    }

    async function handleConfirm() {
        const name = container.querySelector('#bw-name').value;
        const phone = container.querySelector('#bw-phone').value;
        const email = container.querySelector('#bw-email')?.value || '';
        const bdate = container.querySelector('#bw-birthdate')?.value || '';
        const notes = container.querySelector('#bw-notes')?.value || '';
        const pkg = container.querySelector('#bw-package')?.value || '';
        const guests = container.querySelector('#bw-guests')?.value || '1';

        const checkin = container.querySelector('#bw-checkin').value;
        const checkout = container.querySelector('#bw-checkout').value;
        const msg = container.querySelector('#bw-message');

        if (!name || !phone) {
            alert('Пожалуйста, заполните ФИО и контактный телефон');
            return;
        }

        msg.innerText = 'Оформляем вашу заявку...';

        try {
            const response = await fetch(`${baseUrl}api/v1.php?action=create_web_booking`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    room_id: selectedRoomId,
                    name,
                    phone,
                    email,
                    birthdate: bdate,
                    notes,
                    package_id: pkg,
                    guests,
                    checkin,
                    checkout
                })
            });
            const data = await response.json();
            if (data.success) {
                container.querySelector('#booking-form-step2').style.display = 'none';
                msg.innerHTML = `
                    <div style="background: #dff6dd; border: 1px solid #107c10; padding: 20px; border-radius: 10px; margin-top: 10px;">
                        <h4 style="color:#107c10; margin-top:0">${config.success_msg}</h4>
                        <p style="font-size: 13px; color: #333; margin-bottom: 0;">Ваш номер заявки: <strong>#${data.booking_id.substr(-5).toUpperCase()}</strong></p>
                    </div>
                `;
            } else {
                msg.innerText = 'Ошибка: ' + data.message;
                msg.style.color = 'red';
            }
        } catch (e) {
            msg.innerText = 'Сбой при отправке заявки. Проверьте соединение.';
        }
    }

    let selectedRoomId = null;
    init();

})();
