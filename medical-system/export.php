<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$action = $_GET['action'] ?? '';
$tm = new \Medical\Core\Managers\TemplateManager();

$settings = [];
$settingsPath = __DIR__ . '/data/settings.json';
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true) ?? [];
}

if ($action === 'print_schedule') {
    $patientId = $_GET['patient_id'];
    $patientManager = new \Medical\Core\Managers\PatientManager();
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $patient = $patientManager->getById($patientId);
    $appointments = $scheduleManager->getByPatient($patientId);

    usort($appointments, function($a, $b) {
        $ta = strtotime($a['date'] . ' ' . $a['time']);
        $tb = strtotime($b['date'] . ' ' . $b['time']);
        return $ta <=> $tb;
    });

    $grouped = [];
    foreach ($appointments as $app) {
        $grouped[$app['date']][] = $app;
    }

    ob_start();
    if (empty($grouped)) {
        echo '<p style="text-align: center; padding: 50px; color: #666;">Назначенных процедур не найдено.</p>';
    } else {
        foreach ($grouped as $date => $dayProcs) {
            echo '<div style="margin-bottom: 25px; break-inside: avoid;">';
            echo '<div style="background: #f3f3f3; padding: 8px 15px; font-weight: bold; border-left: 5px solid #0078d4; margin-bottom: 10px; font-size: 14pt;">' . $date . '</div>';
            echo '<div style="padding: 0 15px;">';
            foreach ($dayProcs as $app) {
                echo '<div style="padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">';
                echo '<div style="display: flex; gap: 20px; align-items: center;">';
                echo '<span style="font-weight: 600; color: #0078d4; font-size: 12pt;">' . $app['time'] . '</span>';
                echo '<span style="font-weight: 500;">' . htmlspecialchars($app['procedure_name']) . '</span>';
                echo '</div>';
                echo '<span>Кабинет: <strong>' . htmlspecialchars($app['cabinet_id']) . '</strong></span>';
                echo '</div>';
            }
            echo '</div></div>';
        }
    }
    $content = ob_get_clean();

    echo $tm->render('schedule', [
        'patient_name' => $patient['name'],
        'patient_id' => $patient['id'],
        'content' => $content
    ]);

} elseif ($action === 'print_contract') {
    $id = $_GET['id'];
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $app = $scheduleManager->getById($id);

    echo $tm->render('contract', [
        'patient_name' => $app['patient_name'],
        'content' => 'Услуга: ' . htmlspecialchars($app['procedure_name']) . ' на сумму ' . number_format($app['price'], 2, ',', ' ') . ' ₽'
    ]);

} elseif ($action === 'epicrisis') {
    $patientId = $_GET['patient_id'];
    $patientManager = new \Medical\Core\Managers\PatientManager();
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $patient = $patientManager->getById($patientId);
    $appointments = $scheduleManager->getByPatient($patientId);
    $attended = array_filter($appointments, function($a) { return !empty($a['attended']); });

    ob_start();
    echo '<h2>Проведенное лечение</h2><ul>';
    foreach ($attended as $app) {
        echo '<li>' . $app['date'] . ': ' . htmlspecialchars($app['procedure_name']) . '</li>';
    }
    echo '</ul>';

    if (isset($patient['history']) && !empty($patient['history'])) {
        $last = end($patient['history']);
        echo '<h2>Диагноз</h2><p><strong>' . htmlspecialchars($last['diagnosis_code']) . '</strong>: ' . htmlspecialchars($last['diagnosis_text']) . '</p>';
    }
    $content = ob_get_clean();

    echo $tm->render('epicrisis', [
        'patient_name' => $patient['name'],
        'patient_id' => $patient['id'],
        'content' => $content
    ]);

} elseif ($action === 'analytics_csv') {
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $apps = $scheduleManager->getAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Дата', 'Время', 'Пациент', 'Процедура', 'Врач', 'Кабинет', 'Статус', 'Цена']);
    foreach ($apps as $a) {
        fputcsv($output, [
            $a['date'],
            $a['time'],
            $a['patient_name'],
            $a['procedure_name'],
            $a['doctor'],
            $a['cabinet_id'],
            $a['status'],
            $a['price']
        ]);
    }
    fclose($output);
} elseif ($action === 'export_patients') {
    $patientManager = new \Medical\Core\Managers\PatientManager();
    $patients = $patientManager->getAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="patients_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'ФИО', 'Дата рождения', 'Телефон', 'Номер карты', 'Адрес', 'Доп. инфо']);
    foreach ($patients as $p) {
        fputcsv($output, [
            $p['id'],
            $p['name'],
            $p['birth_date'] ?? '',
            $p['phone'] ?? '',
            $p['card_number'] ?? '',
            $p['residence'] ?? '',
            $p['extra_info'] ?? ''
        ]);
    }
    fclose($output);
} elseif ($action === 'export_procedures') {
    $procStore = new \Medical\Core\JsonStore('procedures_directory');
    $procs = $procStore->getAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="procedures_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Наименование', 'Длительность (мин)', 'Подготовка (мин)', 'Цена', 'Платная (1/0)', 'Кабинет по умолчанию']);
    foreach ($procs as $p) {
        fputcsv($output, [
            $p['id'],
            $p['name'],
            $p['duration'] ?? '',
            $p['prep_time'] ?? '',
            $p['price'] ?? '',
            $p['is_paid'] ?? '0',
            $p['default_cabinet'] ?? ''
        ]);
    }
    fclose($output);
}
