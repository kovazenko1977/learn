/**
 * HIS Booking Widget - Frontend Logic
 */

const state = {
    step: 1,
    program_id: null,
    check_in: null,
    room_id: null,
    guest: {}
};

document.addEventListener('DOMContentLoaded', () => {
    initStepNavigation();
    initSelectors();
});

function initStepNavigation() {
    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => {
            const next = parseInt(btn.dataset.next);
            if (validateStep(state.step)) {
                goToStep(next);
            }
        });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => {
            const prev = parseInt(btn.dataset.prev);
            goToStep(prev);
        });
    });
}

function initSelectors() {
    // Program selector
    document.querySelectorAll('#program-selector .card-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('#program-selector .card-item').forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
            state.program_id = item.dataset.id;
        });
    });

    // Date change
    document.getElementById('check-in').addEventListener('change', (e) => {
        state.check_in = e.target.value;
    });
}

function goToStep(num) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById(`step-${num}`).classList.add('active');
    state.step = num;

    if (num === 2) loadRooms();
    if (num === 4) showSummary();
}

function validateStep(num) {
    if (num === 1) {
        if (!state.program_id) { alert('Выберите программу'); return false; }
        if (!state.check_in) { alert('Выберите дату заезда'); return false; }
    }
    if (num === 2) {
        if (!state.room_id) { alert('Выберите номер'); return false; }
    }
    if (num === 3) {
        const name = document.getElementById('guest-name').value;
        const email = document.getElementById('guest-email').value;
        const phone = document.getElementById('guest-phone').value;
        if (!name || !email || !phone) {
            alert('Пожалуйста, заполните основные контактные данные');
            return false;
        }
        state.guest = {
            name, email, phone,
            medical: document.getElementById('guest-medical').value
        };
    }
    return true;
}

function loadRooms() {
    const container = document.getElementById('room-selector');
    container.innerHTML = '<p>Загрузка доступных номеров...</p>';

    // In real app, this would be an API call with check-in and program_id
    fetch('api/rooms.php')
        .then(res => res.json())
        .then(rooms => {
            container.innerHTML = '';
            rooms.forEach(room => {
                const div = document.createElement('div');
                div.className = 'card-item';
                div.dataset.id = room.id;
                div.innerHTML = `
                    <strong>${room.name}</strong>
                    <div class="small">${room.price} руб/сутки</div>
                `;
                div.addEventListener('click', () => {
                    document.querySelectorAll('#room-selector .card-item').forEach(i => i.classList.remove('selected'));
                    div.classList.add('selected');
                    state.room_id = room.id;
                    state.room_name = room.name;
                    state.room_price = room.price;

                    // Fetch program info to calculate total
                    fetch('api/programs.php')
                        .then(res => res.json())
                        .then(programs => {
                            const prg = programs.find(p => p.id === state.program_id);
                            if (prg) {
                                state.duration = prg.duration;
                                state.total_cost = state.room_price * state.duration;
                            }
                        });
                });
                container.appendChild(div);
            });
        });
}

function showSummary() {
    const container = document.getElementById('booking-summary');
    container.innerHTML = `
        <div class="summary-item"><strong>Лечебная программа:</strong> ${state.program_id}</div>
        <div class="summary-item"><strong>Дата заезда:</strong> ${state.check_in}</div>
        <div class="summary-item"><strong>Тип размещения:</strong> ${state.room_name}</div>
        <div class="summary-item"><strong>Длительность:</strong> ${state.duration} дней</div>
        <div class="summary-item"><strong>Итого к оплате:</strong> <span style="font-size: 1.2em; color: var(--primary); font-weight: bold;">${state.total_cost} руб.</span></div>
        <hr>
        <div class="summary-item"><strong>Гость:</strong> ${state.guest.name}</div>
        <div class="summary-item"><strong>Email:</strong> ${state.guest.email}</div>
        <div class="summary-item"><strong>Телефон:</strong> ${state.guest.phone}</div>
        ${state.guest.medical ? `<div class="summary-item"><strong>Мед. данные:</strong> ${state.guest.medical}</div>` : ''}
    `;
}

document.getElementById('confirm-booking').addEventListener('click', () => {
    const btn = document.getElementById('confirm-booking');
    btn.disabled = true;
    btn.innerText = 'Оформление...';

    fetch('api/bookings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('steps-container').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <h2 style="color: green;">Бронирование успешно!</h2>
                    <p>Номер вашего заказа: <strong>${data.id}</strong></p>
                    <p>Подтверждение отправлено на ${state.guest.email}</p>
                    <button class="btn btn-primary" onclick="location.reload()">Вернуться на главную</button>
                </div>
            `;
        } else {
            alert('Ошибка при бронировании: ' + data.error);
            btn.disabled = false;
            btn.innerText = 'Подтвердить и оплатить';
        }
    });
});
