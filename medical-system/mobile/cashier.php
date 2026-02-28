<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('finance_view')) {
    die("У вас недостаточно прав.");
}

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$analytics = new \Medical\Core\Managers\AnalyticsManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markPaid($_POST['id']);
        header("Location: cashier.php?success=paid");
        exit;
    }
}

$date = $_GET['date'] ?? date('Y-m-d');
$hidePaid = isset($_GET['hide_paid']) && $_GET['hide_paid'] == 1;

$appointments = $scheduleManager->getByDate($date);
$appointments = array_filter($appointments, function($app) use ($hidePaid) {
    if (($app['price'] ?? 0) <= 0) return false;
    if ($hidePaid && ($app['status'] ?? '') === 'paid') return false;
    if (($app['status'] ?? '') === 'cancelled') return false;
    return true;
});

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; background: #F3EDF7;">
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Касса / Платежи</h2>

    <div style="display: flex; gap: 8px; margin-top: 12px; overflow-x: auto;">
        <form method="GET" style="display: flex; gap: 8px; width: 100%;">
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" class="md-input" style="height: 40px; margin-bottom: 0; flex: 1;" onchange="this.form.submit()">
            <label style="display: flex; align-items: center; gap: 4px; font-size: 13px; white-space: nowrap; background: #fff; padding: 0 12px; border-radius: 20px; border: 1px solid #CAC4D0;">
                <input type="checkbox" name="hide_paid" value="1" <?php echo $hidePaid ? 'checked' : ''; ?> onchange="this.form.submit()"> Скрыть
            </label>
        </form>
    </div>
</div>

<div style="padding-bottom: 100px;">
    <?php if (empty($appointments)): ?>
        <div style="text-align: center; padding: 48px; color: var(--md-secondary);">
            <i data-lucide="receipt" style="width: 48px; height: 48px; margin-bottom: 16px;"></i>
            <p>Платежей не найдено</p>
        </div>
    <?php else: ?>
        <?php foreach ($appointments as $app): ?>
            <div class="md-card" style="margin: 12px 16px; border-left: 4px solid <?php echo ($app['status']??'') === 'paid' ? '#107c10' : '#B3261E'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: var(--md-primary); font-size: 16px;"><?php echo htmlspecialchars($app['patient_name']); ?></div>
                    <div style="font-size: 14px; font-weight: 600;"><?php echo number_format($app['price'], 2); ?> ₽</div>
                </div>
                <div style="font-size: 14px; margin-bottom: 12px;"><?php echo htmlspecialchars($app['procedure_name']); ?></div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 12px; color: var(--md-secondary);"><?php echo $app['time']; ?> • Каб. <?php echo htmlspecialchars($app['cabinet_id']); ?></div>

                    <?php if (($app['status'] ?? '') === 'paid'): ?>
                        <div style="color: #107c10; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="check" style="width:14px; height:14px;"></i> Оплачено
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="pay">
                            <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="md-btn md-btn-primary" style="height: 32px; padding: 0 16px; font-size: 12px;">Оплатить</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
