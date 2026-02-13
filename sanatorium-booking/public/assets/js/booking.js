document.addEventListener('DOMContentLoaded', function() {
    const apiBase = '../api/v1.php';
    const form = document.getElementById('booking-form');
    const findRoomsBtn = document.getElementById('find-rooms');
    const roomsSelection = document.getElementById('rooms-selection');
    const roomsList = document.getElementById('rooms-list');
    const extraOptions = document.getElementById('extra-options');
    const totalPriceSpan = document.getElementById('total-price');

    let selectedRoomId = null;

    findRoomsBtn.addEventListener('click', async function() {
        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        const persons = document.getElementById('persons').value;

        if (!checkIn || !checkOut) {
            alert('Выберите даты');
            return;
        }

        const res = await fetch(`${apiBase}?action=rooms/available&check_in=${checkIn}&check_out=${checkOut}&persons=${persons}`);
        const rooms = await res.json();

        roomsList.innerHTML = '';
        if (rooms.length === 0) {
            roomsList.innerHTML = '<p>Нет доступных номеров на эти даты</p>';
        } else {
            rooms.forEach(room => {
                const div = document.createElement('div');
                div.className = 'room-item';
                div.style.padding = '10px';
                div.style.border = '1px solid #ccc';
                div.style.margin = '5px 0';
                div.style.cursor = 'pointer';
                div.innerHTML = `<strong>${room.room_number}</strong> - ${room.price_per_day} руб/сут`;
                div.onclick = () => selectRoom(room.id, div);
                div.dataset.id = room.id;
                roomsList.appendChild(div);
            });
        }
        roomsSelection.style.display = 'block';
    });

    function selectRoom(id, el) {
        selectedRoomId = id;
        document.querySelectorAll('.room-item').forEach(item => item.style.background = '#fff');
        el.style.background = '#e7f3ff';
        extraOptions.style.display = 'block';
        updatePrice();
    }

    async function loadOptions() {
        const [procRes, packRes, svcRes] = await Promise.all([
            fetch(`${apiBase}?action=procedures`),
            fetch(`${apiBase}?action=packages`),
            fetch(`${apiBase}?action=services`)
        ]);
        const procedures = await procRes.json();
        const packages = await packRes.json();
        const services = await svcRes.json();

        const procList = document.getElementById('procedures-list');
        procedures.forEach(p => {
            const label = document.createElement('label');
            label.style.display = 'block';
            label.innerHTML = `<input type="checkbox" name="procedures" value="${p.id}" onchange="updatePrice()"> ${p.name} (${p.price} руб)`;
            procList.appendChild(label);
        });

        const svcList = document.getElementById('services-list');
        services.forEach(s => {
            const label = document.createElement('label');
            label.style.display = 'block';
            label.innerHTML = `<input type="checkbox" name="services" value="${s.id}" onchange="updatePrice()"> ${s.name} (${s.price} руб)`;
            svcList.appendChild(label);
        });

        const packSelect = document.getElementById('package_id');
        packages.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.name} (+${p.base_price} руб)`;
            packSelect.appendChild(opt);
        });
    }

    window.updatePrice = async function() {
        if (!selectedRoomId) {
            totalPriceSpan.textContent = '0';
            return;
        }

        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        const packageId = document.getElementById('package_id').value;
        const procedureIds = Array.from(document.querySelectorAll('input[name="procedures"]:checked')).map(el => el.value);
        const serviceIds = Array.from(document.querySelectorAll('input[name="services"]:checked')).map(el => el.value);

        const res = await fetch(`${apiBase}?action=calculate`, {
            method: 'POST',
            body: JSON.stringify({
                room_id: selectedRoomId,
                check_in: checkIn,
                check_out: checkOut,
                package_id: packageId,
                procedure_ids: procedureIds,
                service_ids: serviceIds
            })
        });
        const result = await res.json();
        totalPriceSpan.textContent = result.total_price;
    };

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!selectedRoomId) {
            alert('Выберите номер');
            return;
        }

        const data = {
            room_id: selectedRoomId,
            check_in: document.getElementById('check_in').value,
            check_out: document.getElementById('check_out').value,
            persons: document.getElementById('persons').value,
            phone: document.getElementById('phone').value,
            client_name: document.getElementById('client_name').value,
            package_id: document.getElementById('package_id').value,
            procedure_ids: Array.from(document.querySelectorAll('input[name="procedures"]:checked')).map(el => el.value),
            service_ids: Array.from(document.querySelectorAll('input[name="services"]:checked')).map(el => el.value)
        };

        const res = await fetch(`${apiBase}?action=booking/create`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            alert('Бронирование успешно создано! ID: ' + result.booking_id);
            location.reload();
        } else {
            alert('Ошибка');
        }
    });

    document.getElementById('package_id').addEventListener('change', updatePrice);
    loadOptions();
});
