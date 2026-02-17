<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$action = $_GET['action'] ?? '';

if ($action === 'print_schedule') {
    $patientId = $_GET['patient_id'];
    $patientManager = new \Medical\Core\Managers\PatientManager();
    $scheduleManager = new \Medical\Core\Managers\ScheduleManager();
    $patient = $patientManager->getById($patientId);
    $appointments = $scheduleManager->getByPatient($patientId);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>График процедур - <?php echo $patient['name']; ?></title>
        <style>
            body { font-family: sans-serif; padding: 40px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
            .header { text-align: center; margin-bottom: 30px; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <h1>Листок назначений</h1>
            <p>Пациент: <strong><?php echo htmlspecialchars($patient['name']); ?></strong></p>
            <p>Дата печати: <?php echo date('d.m.Y H:i'); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Время</th>
                    <th>Процедура</th>
                    <th>Кабинет</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app): ?>
                <tr>
                    <td><?php echo $app['date']; ?></td>
                    <td><?php echo $app['time']; ?></td>
                    <td><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['cabinet_id']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
        <p>г. Санаторск, "<?php echo date('d'); ?>" <?php echo date('m'); ?> <?php echo date('Y'); ?> г.</p>
        <p>Санаторий "Здоровье", именуемый в дальнейшем "Исполнитель", с одной стороны, и
           <strong><?php echo htmlspecialchars($app['patient_name']); ?></strong>, именуемый в дальнейшем "Заказчик", с другой стороны, заключили настоящий договор...</p>
        <p><strong>Предмет договора:</strong> Оказание услуги "<?php echo htmlspecialchars($app['procedure_name']); ?>".</p>
        <p><strong>Стоимость услуги:</strong> <?php echo number_format($app['price'], 2, ',', ' '); ?> ₽.</p>
        <p style="margin-top: 100px;">Подписи сторон:</p>
        <div style="display: flex; justify-content: space-between;">
            <div>Исполнитель: ___________</div>
            <div>Заказчик: ___________</div>
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
            <p>Санаторий "Здоровье"</p>
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
