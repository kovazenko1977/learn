<?php

require_once __DIR__ . '/Autoloader.php';
\Medical\Core\Autoloader::register();

use Medical\Core\JsonStore;
use Medical\Core\Managers\PatientManager;
use Medical\Core\Managers\ProcedureManager;
use Medical\Core\Managers\ScheduleManager;

echo "Загрузка обновленных демо-данных...\n";

// 1. Procedures with Staff
$procManager = new ProcedureManager();
$procDirectory = new JsonStore('procedures_directory');
$procDirectory->save([]); // Reset

$procs = [
    ['name' => 'Грязелечение', 'type' => 'free', 'price' => 0, 'duration' => 30, 'staff' => ['Медсестра']],
    ['name' => 'Массаж спины', 'type' => 'paid', 'price' => 1500, 'duration' => 20, 'staff' => ['Медсестра', 'Администратор']],
    ['name' => 'Электрофорез', 'type' => 'free', 'price' => 0, 'duration' => 15, 'staff' => ['Медсестра']],
    ['name' => 'Ингаляция', 'type' => 'free', 'price' => 0, 'duration' => 10, 'staff' => ['Медсестра']],
    ['name' => 'Подводный душ-массаж', 'type' => 'paid', 'price' => 2500, 'duration' => 40, 'staff' => ['Медсестра']],
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
            'birth_date' => '15-05-1980',
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
    for ($i = 0; $i < 3; $i++) {
        $p = $allProcs[array_rand($allProcs)];
        $doctor = ($i % 2 == 0) ? 'Лечащий врач' : 'Администратор';

        $scheduleManager->assign([
            'patient_id' => $pid,
            'patient_name' => $patientNames[$idx],
            'procedure_id' => $p['id'],
            'procedure_name' => $p['name'],
            'date' => date('d-m-Y'),
            'time' => '10:' . sprintf('%02d', (20 * ($i + $idx))),
            'cabinet_id' => '10' . ($idx + 1),
            'type' => $p['type'],
            'price' => $p['price'],
            'is_paid' => ($i == 0 && $p['type'] == 'paid'), // One pre-paid for variety
            'attended' => ($i == 0),
            'attended_at' => ($i == 0) ? date('Y-m-d H:i:s') : null,
            'performed_by' => ($i == 0) ? 'Медсестра' : null,
            'doctor' => $doctor
        ]);
    }

    // Add some history and comments
    $patientManager->addHistoryEntry($pid, [
        'doctor' => 'Лечащий врач',
        'diagnosis_code' => 'I10',
        'diagnosis_text' => 'Эссенциальная [первичная] гипертензия',
        'notes' => 'Пациент жалуется на головные боли. Назначен курс процедур.'
    ]);

    $patientManager->addComment($pid, [
        'author' => 'Администратор',
        'role' => 'admin',
        'text' => 'Пациент просит назначать процедуры во второй половине дня.'
    ]);
}

echo "Демо-данные успешно обновлены.\n";
