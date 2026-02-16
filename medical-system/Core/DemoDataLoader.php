<?php

require_once __DIR__ . '/Autoloader.php';
\Medical\Core\Autoloader::register();

use Medical\Core\JsonStore;
use Medical\Core\Managers\PatientManager;
use Medical\Core\Managers\ProcedureManager;
use Medical\Core\Managers\ScheduleManager;

echo "Загрузка демо-данных...\n";

// 1. Procedures
$procManager = new ProcedureManager();
$procDirectory = new JsonStore('procedures_directory');
$procDirectory->save([]); // Reset

$procs = [
    ['name' => 'Грязелечение', 'type' => 'free', 'price' => 0, 'duration' => 30],
    ['name' => 'Массаж спины', 'type' => 'paid', 'price' => 1500, 'duration' => 20],
    ['name' => 'Электрофорез', 'type' => 'free', 'price' => 0, 'duration' => 15],
    ['name' => 'Ингаляция', 'type' => 'free', 'price' => 0, 'duration' => 10],
    ['name' => 'Подводный душ-массаж', 'type' => 'paid', 'price' => 2500, 'duration' => 40],
];

foreach ($procs as $p) {
    $procManager->add($p);
}

// 2. Patients
$patientManager = new PatientManager();
$patientsStore = new JsonStore('patients');
$patientsStore->save([]); // Reset

$patientNames = ['Иванов Иван Иванович', 'Петров Петр Петрович', 'Сидорова Анна Сергеевна', 'Кузнецова Елена Павловна'];
$pIds = [];
foreach ($patientNames as $name) {
    $pIds[] = $patientManager->add([
        'name' => $name,
        'birth_date' => '1980-05-15',
        'phone' => '+7 900 123 45 67',
        'card_number' => 'SB-' . rand(1000, 9999)
    ]);
}

// 3. Appointments
$scheduleManager = new ScheduleManager();
$appStore = new JsonStore('appointments');
$appStore->save([]); // Reset

$allProcs = $procManager->getAll();
foreach ($pIds as $idx => $pid) {
    // Assign 2 random procedures to each patient
    for ($i = 0; $i < 2; $i++) {
        $p = $allProcs[array_rand($allProcs)];
        $scheduleManager->assign([
            'patient_id' => $pid,
            'patient_name' => $patientNames[$idx],
            'procedure_id' => $p['id'],
            'procedure_name' => $p['name'],
            'date' => date('Y-m-d'),
            'time' => '09:' . sprintf('%02d', (15 * ($i + $idx))),
            'cabinet_id' => '10' . ($idx + 1),
            'type' => $p['type'],
            'price' => $p['price'],
            'is_paid' => false,
            'attended' => false
        ]);
    }
}

echo "Демо-данные успешно загружены.\n";
