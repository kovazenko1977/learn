<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$user = \Medical\Core\Auth::getUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attend') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markAttended($_POST['id'], $user['name']);
    }
}

$date = $_GET['date'] ?? date('Y-m-d');
$patientId = $_GET['patient_id'] ?? '';

if ($patientId) {
    $appointments = $scheduleManager->getByPatient($patientId);
    // Sort by date/time
    usort($appointments, function($a, $b) {
        return ($b['date'] . ' ' . $b['time']) <=> ($a['date'] . ' ' . $a['time']);
    });
    $title = "Процедуры пациента";
} else {
    $appointments = $scheduleManager->getByDate($date);
    $title = "График приема";
}

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; background: #F3EDF7;">
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;"><?php echo $title; ?></h2>
    <?php if (!$patientId): ?>
        <form method="GET" style="margin-top: 12px;">
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" class="md-input" style="height: 40px; margin-bottom: 0;" onchange="this.form.submit()">
        </form>
    <?php endif; ?>
</div>

<div style="padding-bottom: 100px;">
    <?php foreach ($appointments as $app):
        $isToday = $app['date'] === date('Y-m-d');
    ?>
        <div class="md-card" style="margin: 12px 16px; border-left: 4px solid <?php echo $app['attended'] ? '#107c10' : ($app['status'] === 'unpaid' ? '#B3261E' : '#6750A4'); ?>;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                <div style="font-weight: 700; color: var(--md-primary); font-size: 18px;"><?php echo $app['time']; ?></div>
                <div style="font-size: 12px; color: var(--md-secondary);"><?php echo date('d.m.Y', strtotime($app['date'])); ?></div>
            </div>

            <div style="font-weight: 500; font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($app['procedure_name']); ?></div>
            <div style="font-size: 14px; margin-bottom: 12px;">Пациент: <strong><?php echo htmlspecialchars($app['patient_name']); ?></strong></div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12px; color: var(--md-secondary);">Кабинет <?php echo htmlspecialchars($app['cabinet_id']); ?></div>

                <?php if ($app['attended']): ?>
                    <div style="display: flex; align-items: center; gap: 4px; color: #107c10; font-weight: 500; font-size: 14px;">
                        <i data-lucide="check-circle" style="width:16px; height:16px;"></i> Выполнено
                    </div>
                <?php elseif ($app['status'] === 'unpaid'): ?>
                    <div style="color: #B3261E; font-size: 14px; font-weight: 500;">Ожидает оплаты</div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                        <input type="hidden" name="action" value="attend">
                        <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                        <button type="submit" class="md-btn md-btn-primary" style="height: 32px; padding: 0 16px; font-size: 12px;">Отметить</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($appointments)): ?>
        <div style="text-align: center; padding: 48px; color: var(--md-secondary);">
            <i data-lucide="calendar-x" style="width: 48px; height: 48px; margin-bottom: 16px;"></i>
            <p>Назначений нет</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
