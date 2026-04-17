<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

function seed() {
    // Rooms
    $rooms = [
        ['id' => 'r1', 'name' => 'Кабинет 1', 'location' => '1 этаж', 'equipment' => 'Sirona Intego', 'status' => 'active'],
        ['id' => 'r2', 'name' => 'Кабинет 2 (Хирургия)', 'location' => '1 этаж', 'equipment' => 'Kavo Primus', 'status' => 'active'],
        ['id' => 'r3', 'name' => 'Кабинет 3', 'location' => '2 этаж', 'equipment' => 'Adec 500', 'status' => 'active']
    ];
    foreach($rooms as $r) Storage::write('rooms', $r['id'], $r);

    // Services
    $services = [
        ['id' => 's1', 'name' => 'Первичный осмотр', 'code' => 'OSM-01', 'duration_minutes' => 30, 'base_price' => 1500, 'category' => 'Диагностика'],
        ['id' => 's2', 'name' => 'Лечение кариеса (средний)', 'code' => 'TER-02', 'duration_minutes' => 60, 'base_price' => 4500, 'category' => 'Терапия'],
        ['id' => 's3', 'name' => 'Удаление зуба', 'code' => 'SUR-01', 'duration_minutes' => 45, 'base_price' => 3000, 'category' => 'Хирургия'],
        ['id' => 's4', 'name' => 'Профессиональная гигиена', 'code' => 'GIG-01', 'duration_minutes' => 60, 'base_price' => 5000, 'category' => 'Гигиена']
    ];
    foreach($services as $s) Storage::write('services', $s['id'], $s);

    // Doctors
    $doctors = [
        ['id' => 'd1', 'full_name' => 'Петров Петр Петрович', 'specialization' => 'Стоматолог-терапевт', 'phone' => '+79001112233', 'room_ids' => ['r1', 'r3'], 'status' => 'active'],
        ['id' => 'd2', 'full_name' => 'Сидоров Сидор Сидорович', 'specialization' => 'Стоматолог-хирург', 'phone' => '+79004445566', 'room_ids' => ['r2'], 'status' => 'active']
    ];
    foreach($doctors as $d) Storage::write('doctors', $d['id'], $d);

    // Patients
    $patients = [];
    $names = ['Иванов Иван', 'Смирнова Анна', 'Кузнецов Олег', 'Попова Елена', 'Васильев Игорь'];
    for ($i=1; $i<=5; $i++) {
        $p = [
            'id' => 'p'.$i,
            'full_name' => $names[$i-1],
            'phone' => '+7999000000'.$i,
            'email' => 'patient'.$i.'@example.com',
            'birth_date' => '198'.($i+2).'-05-15',
            'allergies' => 'Нет',
            'chronic_diseases' => 'Нет',
            'created_at' => date('c'),
            'created_by' => 'admin'
        ];
        Storage::write('patients', $p['id'], $p);
        $patients[] = $p;
    }

    // Appointments
    for ($i=0; $i<10; $i++) {
        $id = uniqid();
        $date = date('Y-m-d', strtotime("+$i days"));
        $start = "10:00";
        $end = "11:00";
        $a = [
            'id' => $id,
            'patient_id' => 'p'.(rand(1, 5)),
            'doctor_id' => 'd'.(rand(1, 2)),
            'service_id' => 's'.(rand(1, 4)),
            'room_id' => 'r'.(rand(1, 3)),
            'date' => $date,
            'time_start' => $start,
            'time_end' => $end,
            'status' => 'planned',
            'created_at' => date('c')
        ];
        Storage::write('appointments', $id, $a);
    }

    // Settings
    $system = [
        'clinic_name' => 'Зубной Эксперт',
        'clinic_address' => 'ул. Ленина, д. 10',
        'clinic_phone' => '+7 (495) 123-45-67',
        'timezone' => 'Europe/Moscow',
        'work_hours' => ['start' => '09:00', 'end' => '21:00'],
        'online_booking_enabled' => true
    ];
    Storage::write('settings', 'system', $system);

    $modules = [
        'finance' => true,
        'pdf' => true,
        'analytics' => true,
        'online_booking' => true,
        'patient_cabinet' => true,
        'tasks' => true,
        'sources' => true,
        'tags' => true,
        'chat' => true,
        'internal_notifications' => true
    ];
    Storage::write('settings', 'modules', $modules);

    echo "Demo data seeded successfully.\n";
}

seed();
