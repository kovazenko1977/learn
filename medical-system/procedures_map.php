<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('procedures_assign') && !\Medical\Core\Auth::can('procedures_nurse')) {
    die("У вас недостаточно прав для просмотра карты загрузки.");
}

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();

$dateInput = $_GET['date'] ?? date('Y-m-d');
$date = date('d-m-Y', strtotime($dateInput));

$allAppointments = $scheduleManager->getByDate($date);
$cabinets = array_unique(array_column($allAppointments, 'cabinet_id'));
if (empty($cabinets)) {
    // Try to get cabinets from procedures if no appointments today
    foreach($procedureManager->getAll() as $p) {
        if (!empty($p['default_cabinet'])) $cabinets[] = $p['default_cabinet'];
    }
    $cabinets = array_unique($cabinets);
}
sort($cabinets);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Карта загрузки кабинетов</h1>
    <form method="GET" style="display: flex; gap: 12px; align-items: center;">
        <label>Дата:</label>
        <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime($date)); ?>" onchange="this.form.submit()">
        <button type="submit" class="btn btn-primary">Показать</button>
    </form>
</div>

<div class="card mica-effect" style="overflow-x: auto; padding: 0;">
    <div style="min-width: 1000px; position: relative;">
        <!-- Timeline Header -->
        <div style="display: flex; border-bottom: 1px solid var(--win-border); background: rgba(0,0,0,0.02);">
            <div style="width: 150px; padding: 15px; font-weight: 700; border-right: 1px solid var(--win-border);">Кабинет</div>
            <div style="flex-grow: 1; display: flex; position: relative; height: 50px;">
                <?php for($h=8; $h<=20; $h++): ?>
                    <div style="flex: 1; border-right: 1px dashed var(--win-border); position: relative;">
                        <span style="position: absolute; left: 5px; top: 5px; font-size: 0.75rem; color: var(--win-text-secondary);"><?php echo sprintf("%02d:00", $h); ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Cabinets Rows -->
        <?php foreach ($cabinets as $cab):
            $cabApps = array_filter($allAppointments, function($a) use ($cab) { return $a['cabinet_id'] === $cab; });
        ?>
            <div style="display: flex; border-bottom: 1px solid var(--win-border); min-height: 60px;">
                <div style="width: 150px; padding: 15px; font-weight: 600; border-right: 1px solid var(--win-border); background: rgba(0,0,0,0.01);">
                    Кабинет <?php echo htmlspecialchars($cab); ?>
                </div>
                <div style="flex-grow: 1; position: relative; background: #fff;">
                    <?php
                    $dayStart = 8 * 60;
                    $dayTotal = 12 * 60; // 8:00 to 20:00

                    foreach ($cabApps as $app):
                        $time = $app['time'];
                        list($h, $m) = explode(':', $time);
                        $startMin = ($h * 60 + $m) - $dayStart;

                        // Try to find procedure duration
                        $proc = $procedureManager->getById($app['procedure_id']);
                        $duration = $proc['duration'] ?? 20;

                        $left = ($startMin / $dayTotal) * 100;
                        $width = ($duration / $dayTotal) * 100;

                        if ($left < 0 || $left > 100) continue;
                    ?>
                        <div class="proc-block <?php echo ($app['attended'] ?? false) ? 'attended' : ''; ?>"
                             style="position: absolute; left: <?php echo $left; ?>%; width: <?php echo $width; ?>%; height: 80%; top: 10%; background: var(--win-accent); color: white; border-radius: 4px; padding: 4px; font-size: 0.7rem; overflow: hidden; white-space: nowrap; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                             title="<?php echo htmlspecialchars($app['procedure_name']); ?> - <?php echo htmlspecialchars($app['patient_name']); ?> (<?php echo $app['time']; ?>)">
                            <div style="font-weight: 700;"><?php echo $app['time']; ?></div>
                            <?php echo htmlspecialchars($app['procedure_name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.proc-block:hover {
    transform: scaleY(1.1);
    z-index: 10;
    background: var(--win-accent-hover);
}
.proc-block.attended {
    background: #107c10 !important;
}
</style>

<div class="card mica-effect" style="margin-top: 24px;">
    <h3>Легенда</h3>
    <div style="display: flex; gap: 20px; align-items: center; font-size: 0.9rem;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 16px; height: 16px; background: var(--win-accent); border-radius: 3px;"></div>
            <span>Запланированная процедура</span>
        </div>
        <div style="color: var(--win-text-secondary);">
            * Показаны записи с 08:00 до 20:00. Нажмите на блок, чтобы увидеть детали.
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
