<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$patientManager = new \Medical\Core\Managers\PatientManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markPaid($_POST['id']);
    }
}

$allAppointments = $scheduleManager->getAll();
// Filter only paid procedures that are not yet paid, or show all for search
$query = $_GET['q'] ?? '';
$appointments = array_filter($allAppointments, function($app) use ($query) {
    if ($app['type'] !== 'paid') return false;
    if ($query) {
        return mb_strpos(mb_strtolower($app['patient_name']), mb_strtolower($query)) !== false;
    }
    return $app['status'] === 'unpaid';
});
?>

<h1>Касса - Оплата процедур</h1>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Поиск по ФИО пациента..." style="flex-grow: 1;">
        <button type="submit" class="btn">Найти</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                <th style="padding: 10px;">Пациент</th>
                <th style="padding: 10px;">Процедура</th>
                <th style="padding: 10px;">Дата</th>
                <th style="padding: 10px;">Сумма</th>
                <th style="padding: 10px;">Статус</th>
                <th style="padding: 10px;">Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $app): ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><?php echo htmlspecialchars($app['patient_name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                <td style="padding: 10px;"><?php echo $app['date']; ?></td>
                <td style="padding: 10px;"><strong><?php echo number_format($app['price'], 2, ',', ' '); ?> ₽</strong></td>
                <td style="padding: 10px;">
                    <span class="<?php echo $app['status'] === 'paid' ? 'status-green' : 'status-red'; ?>">
                        <?php echo $app['status'] === 'paid' ? 'Оплачено' : 'Ожидает оплаты'; ?>
                    </span>
                </td>
                <td style="padding: 10px;">
                    <?php if ($app['status'] === 'unpaid'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                            <input type="hidden" name="action" value="pay">
                            <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="btn btn-primary">Оплатить</button>
                        </form>
                    <?php endif; ?>
                    <a href="export.php?action=print_contract&id=<?php echo $app['id']; ?>" target="_blank" class="btn">Договор</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="6" style="padding: 20px; text-align: center; color: #666;">Нет процедур, ожидающих оплаты</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
