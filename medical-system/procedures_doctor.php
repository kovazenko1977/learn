<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();
$scheduleManager = new \Medical\Core\Managers\ScheduleManager();

if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_slots') {
    $cabinetId = $_GET['cabinet_id'];
    $date = $_GET['date'];
    $slots = $scheduleManager->getOccupiedSlots($cabinetId, $date);
    header('Content-Type: application/json');
    echo json_encode($slots);
    exit;
}

$patientId = $_GET['patient_id'] ?? '';
$patient = $patientId ? $patientManager->getById($patientId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $procId = $_POST['procedure_id'];
        $proc = $procedureManager->getById($procId);

        $isPaidProc = $proc['is_paid'] ?? false;

        $assignment = [
            'patient_id' => $_POST['patient_id'],
            'patient_name' => $patientManager->getById($_POST['patient_id'])['name'],
            'procedure_id' => $procId,
            'procedure_name' => $proc['name'],
            'date' => $_POST['date'],
            'time' => $_POST['time'],
            'cabinet_id' => $_POST['cabinet_id'],
            'price' => $proc['price'] ?? 0,
            'status' => $isPaidProc ? 'unpaid' : 'free',
            'attended' => false,
            'doctor' => \Medical\Core\Auth::getUser()['name']
        ];

        // Normalize dates from Y-m-d (input) to d-m-Y (storage)
        $startDate = date('d-m-Y', strtotime($_POST['date']));
        $assignment['date'] = $startDate;

        $isBulk = !empty($_POST['end_date']);
        if ($isBulk) {
            $endDate = date('d-m-Y', strtotime($_POST['end_date']));
            $result = $scheduleManager->bulkAssign($assignment, $startDate, $endDate, $_POST['frequency'] ?? 'daily');

            $errors = [];
            foreach ($result as $d => $res) {
                if (isset($res['error'])) $errors[] = "$d: " . $res['error'];
            }
            if (!empty($errors)) {
                $error = "Ошибки при массовом назначении: " . implode(', ', $errors);
            } else {
                header("Location: procedures_doctor.php?patient_id=" . $_POST['patient_id']);
                exit;
            }
        } else {
            $result = $scheduleManager->assign($assignment);
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                header("Location: procedures_doctor.php?patient_id=" . $_POST['patient_id']);
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$procedures = $procedureManager->getAll();
$allAppointments = $scheduleManager->getAll();

// Filter for doctor: only what concerns him (assigned by him)
$currentUser = \Medical\Core\Auth::getUser();
$myAppointments = array_filter($allAppointments, function($app) use ($currentUser) {
    return isset($app['doctor']) && $app['doctor'] === $currentUser['name'];
});

$patientAppointments = $patientId ? $scheduleManager->getByPatient($patientId) : [];
?>

<h1>Назначение процедур</h1>

<?php if (isset($error)): ?>
    <div class="card mica-effect" style="color: #d83b01; border-color: #d83b01; margin-bottom: 20px;">
        <strong>Ошибка:</strong> <?php echo $error; ?>
    </div>
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
                        <select name="procedure_id" id="procedure_select" style="width: 100%;" required>
                            <option value="">-- Выберите процедуру --</option>
                            <?php foreach ($procedures as $proc): ?>
                                <option value="<?php echo $proc['id']; ?>"
                                        data-cabinet="<?php echo htmlspecialchars($proc['default_cabinet'] ?? ''); ?>"
                                        data-start="<?php echo $proc['work_start'] ?? '08:00'; ?>"
                                        data-end="<?php echo $proc['work_end'] ?? '17:00'; ?>">
                                    <?php echo htmlspecialchars($proc['name']); ?>
                                    (<?php echo $proc['work_start'] ?? '08:00'; ?>-<?php echo $proc['work_end'] ?? '17:00'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block;">С даты</label>
                            <input type="date" name="date" id="start_date" value="<?php echo date('Y-m-d'); ?>" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="display:block;">По дату (необяз.)</label>
                            <input type="date" name="end_date" id="end_date" style="width: 100%;">
                        </div>
                    </div>

                    <div id="bulk_options" style="display: none; margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px;">
                        <label style="display:block;">Периодичность</label>
                        <select name="frequency" style="width: 100%;">
                            <option value="daily">Ежедневно</option>
                            <option value="every_other">Через день</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Кабинет</label>
                        <input type="text" name="cabinet_id" id="cabinet_id" placeholder="Напр. 101" style="width: 100%;" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Время</label>
                        <input type="time" name="time" id="time_input" style="width: 100%;" required>
                    </div>

                    <div id="timeline_container" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 5px;">Загруженность кабинета (Рабочие часы выделены белым)</label>
                        <div id="timeline" style="height: 30px; background: #e5e5e5; border-radius: 4px; position: relative; overflow: hidden; border: 1px solid var(--win-border);">
                            <div id="working_hours_bg" style="position: absolute; height: 100%; background: #fff; z-index: 1;"></div>
                            <div id="busy_slots_container" style="position: absolute; width: 100%; height: 100%; z-index: 2;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: #666; margin-top: 2px;">
                            <span>08:00</span>
                            <span>12:00</span>
                            <span>16:00</span>
                            <span>20:00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 45px; font-weight: 600;">Назначить процедуру</button>
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
                        <?php foreach (array_reverse($patientAppointments) as $app): ?>
                        <tr style="border-bottom: 1px solid var(--win-border);">
                            <td style="padding: 10px;"><?php echo $app['date']; ?> <?php echo $app['time']; ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                            <td style="padding: 10px; font-size: 0.8em;"><?php echo htmlspecialchars($app['doctor'] ?? '-'); ?></td>
                            <td style="padding: 10px;">
                                <?php
                                    $class = 'status-gray';
                                    $text = 'Бесплатно';
                                    if (($app['status'] ?? '') === 'unpaid') { $class = 'status-red'; $text = 'Не оплачено'; }
                                    if (($app['status'] ?? '') === 'paid') { $class = 'status-green'; $text = 'Оплачено'; }
                                    if ($app['attended'] ?? false) { $text .= ' (Проведена)'; }
                                ?>
                                <span class="<?php echo $class; ?>"><?php echo $text; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <a href="export.php?action=print_schedule&patient_id=<?php echo $patientId; ?>" target="_blank" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-lucide="printer" class="icon" style="margin: 0;"></i> Печать карты процедур
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    const procedureSelect = document.getElementById('procedure_select');
    const cabinetInput = document.getElementById('cabinet_id');
    const dateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const bulkOptions = document.getElementById('bulk_options');
    const timeline = document.getElementById('timeline');
    const workingHoursBg = document.getElementById('working_hours_bg');
    const busySlotsContainer = document.getElementById('busy_slots_container');

    function updateTimeline() {
        const selectedOption = procedureSelect.options[procedureSelect.selectedIndex];
        const workStart = selectedOption?.getAttribute('data-start') || '08:00';
        const workEnd = selectedOption?.getAttribute('data-end') || '17:00';

        // Update working hours background
        const startDay = 8 * 60;
        const totalDay = 12 * 60; // 08:00 to 20:00

        const [wsH, wsM] = workStart.split(':').map(Number);
        const [weH, weM] = workEnd.split(':').map(Number);

        const wsMin = (wsH * 60 + wsM) - startDay;
        const weMin = (weH * 60 + weM) - startDay;

        workingHoursBg.style.left = Math.max(0, (wsMin / totalDay) * 100) + '%';
        workingHoursBg.style.width = Math.max(0, ((weMin - wsMin) / totalDay) * 100) + '%';

        const cabinet = cabinetInput.value;
        let date = dateInput.value; // Y-m-d
        if (!cabinet || !date) return;

        // Convert Y-m-d to d-m-Y for backend
        const [y, m, d] = date.split('-');
        const formattedDate = `${d}-${m}-${y}`;

        fetch(`?ajax_action=get_slots&cabinet_id=${cabinet}&date=${formattedDate}`)
            .then(r => r.json())
            .then(slots => {
                busySlotsContainer.innerHTML = '';
                // 08:00 to 20:00 is 12 hours = 720 minutes
                const startDay = 8 * 60;
                const totalDay = 12 * 60;

                slots.forEach(slot => {
                    const [hS, mS] = slot.start.split(':').map(Number);
                    const [hE, mE] = slot.end.split(':').map(Number);

                    const startMin = (hS * 60 + mS) - startDay;
                    const endMin = (hE * 60 + mE) - startDay;

                    if (startMin < 0 && endMin <= 0) return;

                    const left = Math.max(0, (startMin / totalDay) * 100);
                    const width = ((endMin - Math.max(0, startMin)) / totalDay) * 100;

                    const block = document.createElement('div');
                    block.style.position = 'absolute';
                    block.style.left = left + '%';
                    block.style.width = width + '%';
                    block.style.height = '100%';
                    block.style.background = 'rgba(255, 241, 0, 0.8)'; // Yellow for busy
                    block.title = `${slot.procedure} (${slot.start} - ${slot.end})`;
                    busySlotsContainer.appendChild(block);
                });
            });
    }

    procedureSelect?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const cabinet = selectedOption.getAttribute('data-cabinet');
        if (cabinet) {
            cabinetInput.value = cabinet;
            updateTimeline();
        }
    });

    cabinetInput?.addEventListener('change', updateTimeline);
    dateInput?.addEventListener('change', updateTimeline);

    endDateInput?.addEventListener('input', function() {
        bulkOptions.style.display = this.value ? 'block' : 'none';
    });

    // Initial timeline
    updateTimeline();
</script>

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
                    <?php if ($app['attended'] ?? false): ?>
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
