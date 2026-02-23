<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('procedures_nurse')) {
    die("У вас недостаточно прав для доступа к кабинету медсестры.");
}

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();

$currentUser = \Medical\Core\Auth::getUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'attend' && \Medical\Core\Auth::can('procedures_nurse')) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $scheduleManager->markAttended($_POST['id'], $currentUser['name']);
    }
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$cabinetInput = $_GET['cabinet'] ?? '';

$allAppointments = $scheduleManager->getByDateRange($startDate, $endDate);

// Filter: nurse only sees procedures for period and her cabinet
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
            <label style="font-weight: 500;">С даты:</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" style="width: 160px;">
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-weight: 500;">По дату:</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" style="width: 160px;">
        </div>
        <div style="border-left: 1px solid var(--win-border); padding-left: 20px; display: flex; gap: 15px; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom: 8px; font-size: 0.8rem;">Дней вперед</label>
                <input type="number" id="days_ahead" min="0" max="365" placeholder="0" style="width: 80px;">
            </div>
            <div style="padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="save_period" style="width: 18px; height: 18px; cursor: pointer;">
                <label for="save_period" style="font-size: 0.85rem; cursor: pointer;">Запомнить</label>
            </div>
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
                <th>Дата/Время</th>
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
                <td style="font-weight: 700; color: var(--win-accent);">
                    <?php echo date('d.m', strtotime($app['date'])); ?> <?php echo $app['time']; ?>
                </td>
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
