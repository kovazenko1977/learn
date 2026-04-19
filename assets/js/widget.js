window.DentalWidget = {
    init: function(config) {
        const container = document.querySelector(config.container);
        if (!container) return;

        container.innerHTML = `
            <div style="font-family: sans-serif; border: 1px solid #ddd; padding: 20px; border-radius: 12px; background: #fff; max-width: 400px;">
                <h3 style="margin-top: 0;">Запись на прием</h3>
                <div id="dw-form">
                    <input type="text" id="dw-name" placeholder="ФИО" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;">
                    <input type="text" id="dw-phone" placeholder="Телефон" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;">
                    <select id="dw-service" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;">
                        <option value="">Выберите услугу</option>
                    </select>
                    <input type="date" id="dw-date" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;">
                    <div id="dw-time-container">
                        <input type="time" id="dw-time" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;">
                    </div>
                    <button id="dw-submit" style="width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Записаться</button>
                    <p id="dw-msg" style="margin-top: 10px; font-size: 14px; display: none;"></p>
                </div>
            </div>
        `;

        fetch(config.apiUrl + '&action=get_services')
            .then(res => res.json())
            .then(services => {
                const select = document.getElementById('dw-service');
                services.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    select.appendChild(opt);
                });
            });

        const loadSlots = () => {
            const date = document.getElementById('dw-date').value;
            const service = document.getElementById('dw-service').value;
            if (date && service) {
                fetch(config.apiUrl + \`&action=get_free_slots&doctor_id=any&date=\${date}&service_id=\${service}\`)
                    .then(res => res.json())
                    .then(slots => {
                        const container = document.getElementById('dw-time-container');
                        if (slots.length > 0) {
                            container.innerHTML = \`<select id="dw-time" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;"></select>\`;
                            const select = document.getElementById('dw-time');
                            slots.forEach(slot => {
                                const opt = document.createElement('option');
                                opt.value = slot;
                                opt.textContent = slot;
                                select.appendChild(opt);
                            });
                        } else {
                            container.innerHTML = \`<input type="time" id="dw-time" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 6px;">\`;
                        }
                    });
            }
        };

        document.getElementById('dw-date').addEventListener('change', loadSlots);
        document.getElementById('dw-service').addEventListener('change', loadSlots);

        document.getElementById('dw-submit').addEventListener('click', () => {
            const data = {
                full_name: document.getElementById('dw-name').value,
                phone: document.getElementById('dw-phone').value,
                service_id: document.getElementById('dw-service').value,
                date: document.getElementById('dw-date').value,
                time_start: document.getElementById('dw-time').value
            };

            const msg = document.getElementById('dw-msg');
            msg.style.display = 'block';
            msg.textContent = 'Отправка...';
            msg.style.color = '#666';

            fetch(config.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    msg.textContent = 'Запись успешно создана!';
                    msg.style.color = 'green';
                    document.getElementById('dw-form').style.opacity = '0.5';
                    document.getElementById('dw-submit').disabled = true;
                } else {
                    msg.textContent = 'Ошибка: ' + (res.error || 'Неизвестная ошибка');
                    msg.style.color = 'red';
                }
            })
            .catch(() => {
                msg.textContent = 'Ошибка соединения';
                msg.style.color = 'red';
            });
        });
    }
};
