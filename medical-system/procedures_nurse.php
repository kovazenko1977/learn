<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin(['admin', 'chief', 'nurse']);

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();

$currentUser = \Medical\Core\Auth::getUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attend') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markAttended($_POST['id'], $currentUser['name']);
    }
}

$dateInput = $_GET['date'] ?? date('Y-m-d');
$cabinetInput = $_GET['cabinet'] ?? '';

// Normalize date to DD-MM-YYYY for storage lookup
$date = date('d-m-Y', strtotime($dateInput));

$allAppointments = $scheduleManager->getByDate($date);

// Filter: nurse only sees procedures for today and her cabinet
$appointments = array_filter($allAppointments, function($app) use ($cabinetInput) {
    if ($cabinetInput && $app['cabinet_id'] !== $cabinetInput) return false;
    return true;
});

// Get unique cabinets for the filter
$cabinets = array_unique(array_column($allAppointments, 'cabinet_id'));
sort($cabinets);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Прием пациентов</h1>
</div>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; align-items: center; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-weight: 500;">Дата:</label>
            <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime($date)); ?>" style="width: 160px;">
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-weight: 500;">Кабинет:</label>
            <select name="cabinet" style="width: 160px;">
                <option value="">Все кабинеты</option>
                <?php foreach ($cabinets as $cab): ?>
                    <option value="<?php echo htmlspecialchars($cab); ?>" <?php echo $cabinetInput === $cab ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cab); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="refresh-cw" class="icon"></i> Обновить
        </button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Время</th>
                <th>Кабинет</th>
                <th>Пациент</th>
                <th>Процедура</th>
                <th>Статус</th>
                <th style="text-align: right;">Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $app): ?>
            <tr style="<?php echo $app['attended'] ? 'opacity: 0.6;' : ''; ?>">
                <td style="font-weight: 700; color: var(--win-accent);"><?php echo $app['time']; ?></td>
                <td><span style="font-size: 0.85rem; color: var(--win-text-secondary);"><?php echo htmlspecialchars($app['cabinet_id']); ?></span></td>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($app['patient_name']); ?></td>
                <td><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                <td>
                    <?php
                        $class = 'status-gray';
                        $text = 'Бесплатно';
                        if ($app['status'] === 'unpaid') { $class = 'status-red'; $text = 'Не оплачено'; }
                        if ($app['status'] === 'paid') { $class = 'status-green'; $text = 'Оплачено'; }
                    ?>
                    <span class="<?php echo $class; ?>"><?php echo $text; ?></span>
                </td>
                <td style="text-align: right;">
                    <?php if (!$app['attended']): ?>
                        <?php if ($app['status'] === 'unpaid'): ?>
                            <span style="color: var(--win-text-secondary); font-size: 0.85rem; font-style: italic;">Ожидание оплаты</span>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="attend">
                                <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i data-lucide="check" class="icon" style="margin:0;"></i> Отметить
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; align-items: flex-end;">
                            <span class="status-green" style="margin-bottom: 4px;">Принят</span>
                            <span style="font-size: 0.75rem; color: var(--win-text-secondary);">в <?php echo date('H:i', strtotime($app['attended_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--win-text-secondary);">На выбранную дату назначений нет</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
