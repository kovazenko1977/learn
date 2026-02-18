<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$action = $_GET['action'] ?? '';

$settings = [];
$settingsPath = __DIR__ . '/data/settings.json';
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true) ?? [];
}
$orgName = $settings['org_name'] ?? 'WES МЕД';

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
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Карта процедур - <?php echo $patient['name']; ?></title>
        <style>
            @page { size: A4; margin: 15mm; }
            body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #333; line-height: 1.4; }
            .header { border-bottom: 3px solid #0078d4; padding-bottom: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
            .header h1 { margin: 0; color: #0078d4; font-size: 24pt; }
            .patient-info { font-size: 12pt; }
            .day-section { margin-bottom: 25px; break-inside: avoid; }
            .day-title { background: #f3f3f3; padding: 8px 15px; font-weight: bold; border-left: 5px solid #0078d4; margin-bottom: 10px; font-size: 14pt; }
            .proc-grid { display: grid; grid-template-columns: 80px 1fr 100px; gap: 10px; padding: 0 15px; }
            .proc-time { font-weight: 600; color: #0078d4; font-size: 12pt; }
            .proc-name { font-weight: 500; }
            .proc-cabinet { text-align: right; color: #666; }
            .proc-item { padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
            .proc-item:last-child { border-bottom: none; }
            .footer { margin-top: 50px; font-size: 9pt; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <div>
                <h1>Карта процедур</h1>
                <div class="patient-info">Пациент: <strong><?php echo htmlspecialchars($patient['name']); ?></strong></div>
            </div>
            <div style="text-align: right; font-size: 10pt;">
                <?php echo htmlspecialchars($orgName); ?><br>
                Дата: <?php echo date('d.m.Y'); ?>
            </div>
        </div>

        <?php if (empty($grouped)): ?>
            <p style="text-align: center; padding: 50px; color: #666;">Назначенных процедур не найдено.</p>
        <?php else: ?>
            <?php foreach ($grouped as $date => $dayProcs): ?>
                <div class="day-section">
                    <div class="day-title"><?php echo $date; ?></div>
                    <div style="padding: 0 15px;">
                        <?php foreach ($dayProcs as $app): ?>
                            <div class="proc-item">
                                <div style="display: flex; gap: 20px; align-items: center;">
                                    <span class="proc-time"><?php echo $app['time']; ?></span>
                                    <span class="proc-name"><?php echo htmlspecialchars($app['procedure_name']); ?></span>
                                </div>
                                <span class="proc-cabinet">Кабинет: <strong><?php echo htmlspecialchars($app['cabinet_id']); ?></strong></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="footer">
            Пожалуйста, приходите за 5 минут до начала процедуры. Желаем приятного отдыха и скорейшего выздоровления!
        </div>
    </body>
    </html>
    <?php
} elseif ($action === 'print_contract') {
    $id = $_GET['id'];
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $app = $scheduleManager->getById($id);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Договор на платные услуги</title>
        <style>
            body { font-family: serif; line-height: 1.6; padding: 50px; }
            .title { text-align: center; font-weight: bold; margin-bottom: 20px; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="title">ДОГОВОР № <?php echo $app['id']; ?> ОБ ОКАЗАНИИ ПЛАТНЫХ МЕДИЦИНСКИХ УСЛУГ</div>
        <p>г. <?php echo htmlspecialchars($settings['org_address'] ?? 'Санаторск'); ?>, "<?php echo date('d'); ?>" <?php echo date('m'); ?> <?php echo date('Y'); ?> г.</p>
        <p><?php echo htmlspecialchars($orgName); ?>, именуемый в дальнейшем "Исполнитель", с одной стороны, и
           <strong><?php echo htmlspecialchars($app['patient_name']); ?></strong>, именуемый в дальнейшем "Заказчик", с другой стороны, заключили настоящий договор...</p>
        <p><strong>Предмет договора:</strong> Оказание услуги "<?php echo htmlspecialchars($app['procedure_name']); ?>".</p>
        <p><strong>Стоимость услуги:</strong> <?php echo number_format($app['price'], 2, ',', ' '); ?> ₽.</p>

        <div style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div>
                <strong>Исполнитель:</strong><br>
                <?php echo htmlspecialchars($orgName); ?><br>
                Адрес: <?php echo htmlspecialchars($settings['org_address'] ?? ''); ?><br>
                УНП/ИНН: <?php echo htmlspecialchars($settings['org_unp'] ?? ''); ?><br>
                Банк: <?php echo htmlspecialchars($settings['org_bank'] ?? ''); ?><br>
                Р/с: <?php echo htmlspecialchars($settings['org_account'] ?? ''); ?><br><br>
                ___________ / <?php echo htmlspecialchars($settings['org_director'] ?? ''); ?> /
            </div>
            <div>
                <strong>Заказчик:</strong><br>
                <?php echo htmlspecialchars($app['patient_name']); ?><br><br><br>
                ___________ / <?php echo htmlspecialchars($app['patient_name']); ?> /
            </div>
        </div>
    </body>
    </html>
    <?php
} elseif ($action === 'epicrisis') {
    $patientId = $_GET['patient_id'];
    $patientManager = new \Medical\Core\Managers\PatientManager();
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $patient = $patientManager->getById($patientId);
    $appointments = $scheduleManager->getByPatient($patientId);
    $attended = array_filter($appointments, function($a) { return !empty($a['attended']); });
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Выписной эпикриз - <?php echo $patient['name']; ?></title>
        <style>
            body { font-family: sans-serif; padding: 50px; line-height: 1.5; }
            .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 30px; }
            h2 { margin-top: 30px; border-bottom: 1px solid #ccc; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <h1>ВЫПИСНОЙ ЭПИКРИЗ</h1>
            <p><?php echo htmlspecialchars($orgName); ?></p>
        </div>

        <p><strong>Пациент:</strong> <?php echo htmlspecialchars($patient['name']); ?></p>
        <p><strong>Дата рождения:</strong> <?php echo htmlspecialchars($patient['birth_date']); ?></p>
        <p><strong>Период пребывания:</strong> <?php echo date('d.m.Y', strtotime($patient['created_at'])); ?> — <?php echo date('d.m.Y'); ?></p>

        <h2>Диагноз при выписке</h2>
        <?php if (isset($patient['history']) && !empty($patient['history'])):
            $last = end($patient['history']);
        ?>
            <p><strong><?php echo htmlspecialchars($last['diagnosis_code']); ?></strong>: <?php echo htmlspecialchars($last['diagnosis_text']); ?></p>
        <?php else: ?>
            <p>Данные отсутствуют</p>
        <?php endif; ?>

        <h2>Проведенное лечение</h2>
        <ul>
            <?php foreach ($attended as $app): ?>
                <li><?php echo $app['date']; ?>: <?php echo htmlspecialchars($app['procedure_name']); ?></li>
            <?php endforeach; ?>
        </ul>

        <h2>Рекомендации</h2>
        <p>Рекомендовано наблюдение у врача по месту жительства, продолжение курса лечебной физкультуры, рациональное питание.</p>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div>Лечащий врач: _______________</div>
            <div>М.П.</div>
        </div>
    </body>
    </html>
    <?php
} elseif ($action === 'analytics_csv') {
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $apps = $scheduleManager->getAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
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
}
