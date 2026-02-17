<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();
$scheduleManager = new \Medical\Core\Managers\ScheduleManager();

$patientId = $_GET['patient_id'] ?? '';
$patient = $patientId ? $patientManager->getById($patientId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $procId = $_POST['procedure_id'];
    $proc = $procedureManager->getById($procId);

    $assignment = [
        'patient_id' => $_POST['patient_id'],
        'patient_name' => $patientManager->getById($_POST['patient_id'])['name'],
        'procedure_id' => $procId,
        'procedure_name' => $proc['name'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'cabinet_id' => $_POST['cabinet_id'],
        'type' => $proc['type'], // paid/free
        'price' => $proc['price'] ?? 0,
        'is_paid' => false,
        'attended' => false,
        'doctor' => \Medical\Core\Auth::getUser()['name']
    ];

    $result = $scheduleManager->assign($assignment);
    if (isset($result['error'])) {
        $error = $result['error'];
    } else {
        header("Location: procedures_doctor.php?patient_id=" . $_POST['patient_id']);
        exit;
    }
    }
}

$procedures = $procedureManager->getAll();
$allAppointments = $scheduleManager->getAll();

// Filter for doctor: only what concerns him (assigned by him)
$currentUser = \Medical\Core\Auth::getUser();
$myAppointments = array_filter($allAppointments, function($app) use ($currentUser) {
    return $app['doctor'] === $currentUser['name'];
});

$patientAppointments = $patientId ? $scheduleManager->getByPatient($patientId) : [];
?>

<h1>Назначение процедур</h1>

<?php if (isset($error)): ?>
    <div class="card mica-effect" style="color: #d83b01; border-color: #d83b01;"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!$patient): ?>
    <div class="card mica-effect">
        <p>Выберите пациента из <a href="patients.php">реестра</a> для назначения процедур.</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        <div>
            <div class="card mica-effect">
                <h3>Новое назначение для: <?php echo htmlspecialchars($patient['name']); ?></h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Процедура</label>
                        <select name="procedure_id" style="width: 100%;" required>
                            <?php foreach ($procedures as $proc): ?>
                                <option value="<?php echo $proc['id']; ?>"><?php echo htmlspecialchars($proc['name']); ?> (<?php echo $proc['type'] === 'paid' ? 'платно' : 'бесплатно'; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Дата</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" style="width: 100%;" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Время</label>
                        <input type="time" name="time" style="width: 100%;" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Кабинет</label>
                        <input type="text" name="cabinet_id" placeholder="Напр. 101" style="width: 100%;" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Назначить</button>
                </form>
            </div>
        </div>

        <div>
            <div class="card mica-effect">
                <h3>История назначений пациента</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                            <th style="padding: 10px;">Дата/Время</th>
                            <th style="padding: 10px;">Процедура</th>
                            <th style="padding: 10px;">Врач</th>
                            <th style="padding: 10px;">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patientAppointments as $app): ?>
                        <tr style="border-bottom: 1px solid var(--win-border);">
                            <td style="padding: 10px;"><?php echo $app['date']; ?> <?php echo $app['time']; ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                            <td style="padding: 10px; font-size: 0.8em;"><?php echo htmlspecialchars($app['doctor']); ?></td>
                            <td style="padding: 10px;">
                                <?php
                                    $class = 'status-gray';
                                    $text = 'Бесплатно';
                                    if ($app['status'] === 'unpaid') { $class = 'status-red'; $text = 'Не оплачено'; }
                                    if ($app['status'] === 'paid') { $class = 'status-green'; $text = 'Оплачено'; }
                                    if ($app['attended']) { $text .= ' (Проведена)'; }
                                ?>
                                <span class="<?php echo $class; ?>"><?php echo $text; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <a href="export.php?action=print_schedule&patient_id=<?php echo $patientId; ?>" target="_blank" class="btn"><i data-lucide="printer" class="icon"></i> Печать графика</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card mica-effect" style="margin-top: 40px;">
    <h2>Мои последние назначения</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                <th style="padding: 10px;">Пациент</th>
                <th style="padding: 10px;">Процедура</th>
                <th style="padding: 10px;">Дата/Время</th>
                <th style="padding: 10px;">Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $myRecent = array_slice(array_reverse($myAppointments), 0, 10);
            foreach ($myRecent as $app):
            ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($app['patient_name']); ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                <td style="padding: 10px;"><?php echo $app['date']; ?> <?php echo $app['time']; ?></td>
                <td style="padding: 10px;">
                    <?php if ($app['attended']): ?>
                        <span class="status-green">Проведена (<?php echo htmlspecialchars($app['performed_by'] ?? 'медсестра'); ?>)</span>
                    <?php else: ?>
                        <span class="status-gray">Ожидает</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
