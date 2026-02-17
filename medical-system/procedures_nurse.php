<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();

$currentUser = \Medical\Core\Auth::getUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attend') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markAttended($_POST['id'], $currentUser['name']);
    }
}

$dateInput = $_GET['date'] ?? date('Y-m-d');
// Normalize date to DD-MM-YYYY for storage lookup
$date = date('d-m-Y', strtotime($dateInput));

$allAppointments = $scheduleManager->getByDate($date);

// Filter: nurse only sees procedures for today
$appointments = $allAppointments;
?>

<h1>Процедурный кабинет - Прием пациентов</h1>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center;">
        <label>Дата приема:</label>
        <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime($date)); ?>" class="form-control">
        <button type="submit" class="btn btn-primary">Обновить список</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                <th style="padding: 10px;">Время</th>
                <th style="padding: 10px;">Пациент</th>
                <th style="padding: 10px;">Процедура</th>
                <th style="padding: 10px;">Статус оплаты</th>
                <th style="padding: 10px;">Отметка</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $app): ?>
            <tr style="border-bottom: 1px solid var(--win-border); <?php echo $app['attended'] ? 'opacity: 0.6;' : ''; ?>">
                <td style="padding: 10px;"><strong><?php echo $app['time']; ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($app['patient_name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                <td style="padding: 10px;">
                    <?php
                        $class = 'status-gray';
                        $text = 'Бесплатно';
                        if ($app['status'] === 'unpaid') { $class = 'status-red'; $text = 'Не оплачено'; }
                        if ($app['status'] === 'paid') { $class = 'status-green'; $text = 'Оплачено'; }
                    ?>
                    <span class="<?php echo $class; ?>"><?php echo $text; ?></span>
                </td>
                <td style="padding: 10px;">
                    <?php if (!$app['attended']): ?>
                        <?php if ($app['status'] === 'unpaid'): ?>
                            <span style="color: #d83b01; font-size: 0.8em;">Нужна оплата!</span>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="attend">
                                <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                <button type="submit" class="btn btn-primary">Отметить прием</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="status-green">Принят в <?php echo date('H:i', strtotime($app['attended_at'])); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="5" style="padding: 20px; text-align: center; color: #666;">На этот день назначений нет</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
