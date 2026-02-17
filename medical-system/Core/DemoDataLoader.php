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
    ['name' => 'Грязелечение', 'is_paid' => false, 'price' => 0, 'duration' => 30, 'assigned_staff' => [], 'default_cabinet' => '101', 'prep_time' => 10],
    ['name' => 'Массаж спины', 'is_paid' => true, 'price' => 1500, 'duration' => 20, 'assigned_staff' => [], 'default_cabinet' => '202', 'prep_time' => 5],
    ['name' => 'Электрофорез', 'is_paid' => false, 'price' => 0, 'duration' => 15, 'assigned_staff' => [], 'default_cabinet' => '103', 'prep_time' => 5],
    ['name' => 'Ингаляция', 'is_paid' => false, 'price' => 0, 'duration' => 10, 'assigned_staff' => [], 'default_cabinet' => '104', 'prep_time' => 2],
    ['name' => 'Подводный душ-массаж', 'is_paid' => true, 'price' => 2500, 'duration' => 40, 'assigned_staff' => [], 'default_cabinet' => '205', 'prep_time' => 15],
];

foreach ($procs as $p) {
    $procManager->add($p);
}

// 2. Patients
$patientManager = new PatientManager();
$patientsStore = new JsonStore('patients');
$patientsStore->save([]); // Reset

$patientNames = ['Белов Артем Игоревич', 'Соколова Мария Павловна', 'Васильев Олег Сергеевич', 'Морозова Анна Дмитриевна'];
$pIds = [];
foreach ($patientNames as $name) {
    $pIds[] = $patientManager->add([
        'name' => $name,
        'birth_date' => '12-04-1975',
        'phone' => '+7 910 555 01 23',
        'card_number' => 'MED-' . rand(10000, 99999)
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
            'cabinet_id' => $p['default_cabinet'] ?? ('10' . ($idx + 1)),
            'price' => $p['price'],
            'status' => ($p['is_paid'] ?? false) ? 'unpaid' : 'free',
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
