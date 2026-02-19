<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('finance_pay')) {
    die("У вас недостаточно прав для доступа к кассе.");
}

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$patientManager = new \Medical\Core\Managers\PatientManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay' && \Medical\Core\Auth::can('finance_pay')) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markPaid($_POST['id']);
    }
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$query = $_GET['q'] ?? '';

$allAppointments = $scheduleManager->getAll();
$totalRevenue = 0;

$appointments = array_filter($allAppointments, function($app) use ($query, $startDate, $endDate, &$totalRevenue) {
    // Date range check
    $appDate = $app['date'];
    if ($appDate < $startDate || $appDate > $endDate) return false;

    // Search query check
    if ($query && mb_strpos(mb_strtolower($app['patient_name']), mb_strtolower($query)) === false) {
        return false;
    }

    // A procedure is "payable" if status is unpaid or paid
    $isPayable = (($app['status'] ?? '') === 'unpaid' || ($app['status'] ?? '') === 'paid');
    if (!$isPayable) return false;

    if ($app['status'] === 'paid') {
        $totalRevenue += (float)($app['price'] ?? 0);
    }

    return true;
});

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Касса - Оплата процедур</h1>
    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
        <div class="card mica-effect" style="margin: 0; padding: 10px 20px; border-left: 4px solid #107c10;">
            <div style="font-size: 0.8rem; color: var(--win-text-secondary);">Выручка за период</div>
            <div style="font-size: 1.2rem; font-weight: 700; color: #107c10;"><?php echo number_format($totalRevenue, 0, ',', ' '); ?> ₽</div>
        </div>
    <?php endif; ?>
</div>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex-grow: 1; min-width: 200px;">
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">Поиск пациента</label>
            <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="ФИО..." style="width: 100%;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">С даты</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">По дату</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search" class="icon"></i> Найти
        </button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Пациент</th>
                <th>Процедура</th>
                <th>Дата</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th style="text-align: right;">Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $app): ?>
            <tr>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($app['patient_name']); ?></td>
                <td><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                <td><?php echo $app['date']; ?></td>
                <td style="font-weight: 700; color: var(--win-accent);"><?php echo number_format($app['price'], 0, ',', ' '); ?> ₽</td>
                <td>
                    <span class="<?php echo $app['status'] === 'paid' ? 'status-green' : 'status-red'; ?>">
                        <?php echo $app['status'] === 'paid' ? 'Оплачено' : 'Ожидает оплаты'; ?>
                    </span>
                </td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <?php if ($app['status'] === 'unpaid'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="pay">
                                <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Оплатить</button>
                            </form>
                        <?php endif; ?>
                        <a href="export.php?action=print_contract&id=<?php echo $app['id']; ?>" target="_blank" class="btn btn-sm" title="Печать договора">
                            <i data-lucide="file-text" class="icon" style="margin: 0;"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--win-text-secondary);">Нет процедур, ожидающих оплаты</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
