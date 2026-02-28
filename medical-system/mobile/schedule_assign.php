<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('procedures_assign')) {
    die("У вас недостаточно прав.");
}

$patientManager = new \Medical\Core\Managers\PatientManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();
$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$staffManager = new \Medical\Core\Managers\StaffManager();

$patientId = $_GET['patient_id'] ?? '';
$patient = $patientId ? $patientManager->getById($patientId) : null;
$procedures = $procedureManager->getAll();
$staff = $staffManager->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $procId = $_POST['procedure_id'];
        $proc = $procedureManager->getById($procId);

        $data = [
            'patient_id' => $_POST['patient_id'],
            'patient_name' => $patient['name'] ?? '?',
            'procedure_id' => $procId,
            'procedure_name' => $proc['name'],
            'cabinet_id' => $_POST['cabinet_id'],
            'price' => (float)($proc['type'] === 'free' ? 0 : ($proc['price'] ?? 0)),
            'status' => $proc['type'] === 'free' ? 'free' : 'unpaid',
            'time' => $_POST['time'],
            'doctor' => \Medical\Core\Auth::getUser()['name']
        ];

        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'] ?: $startDate;
        $frequency = $_POST['frequency'] ?? 'once';

        $res = $scheduleManager->bulkAssign($data, $startDate, $endDate, $frequency);

        header("Location: attendance.php?patient_id=" . $_POST['patient_id'] . "&assigned=1");
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; display: flex; align-items: center; gap: 12px; background: #F3EDF7;">
    <a href="patient_details.php?id=<?php echo $patientId; ?>" style="color: inherit;"><i data-lucide="arrow-left"></i></a>
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Назначение процедур</h2>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <div style="margin-bottom: 20px; font-weight: 500; font-size: 18px; color: var(--md-primary);">
        Пациент: <?php echo htmlspecialchars($patient['name'] ?? 'Не выбран'); ?>
    </div>

    <form method="POST" id="assignForm">
        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
        <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">

        <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Процедура</label>
        <select name="procedure_id" id="proc_select" class="md-input" required onchange="updateDefaultCabinet()">
            <option value="">Выберите процедуру...</option>
            <?php foreach ($procedures as $p): ?>
                <option value="<?php echo $p['id']; ?>" data-cabinet="<?php echo htmlspecialchars($p['default_cabinet'] ?? ''); ?>">
                    <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['type'] === 'free' ? 'Беспл.' : 'Платн.'; ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Кабинет</label>
        <input type="text" name="cabinet_id" id="cabinet_id" class="md-input" required>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Дата начала</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo date('Y-m-d'); ?>" class="md-input" required onchange="updateSlots()">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Время</label>
                <div style="position: relative;">
                    <input type="time" name="time" id="time_input" class="md-input" required>
                    <button type="button" onclick="updateSlots()" style="position: absolute; right: 0; top: 0; height: 56px; padding: 0 12px; background: none; border: none; color: var(--md-primary);">
                        <i data-lucide="refresh-cw" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="slots_container" style="display: none; margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 8px;">Доступные слоты на выбранную дату:</label>
            <div id="slots_list" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
        </div>

        <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Режим назначения</label>
        <select name="frequency" id="freq_select" class="md-input" onchange="toggleEndDate()">
            <option value="once">Однократно</option>
            <option value="daily">Ежедневно</option>
            <option value="every_other">Через день</option>
        </select>

        <div id="end_date_container" style="display: none;">
            <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Дата окончания</label>
            <input type="date" name="end_date" class="md-input">
        </div>

        <button type="submit" class="md-btn md-btn-primary" style="width: 100%; height: 48px; border-radius: 24px; margin-top: 16px;">Назначить</button>
    </form>
</div>

<script>
function updateDefaultCabinet() {
    const sel = document.getElementById('proc_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.cabinet) {
        document.getElementById('cabinet_id').value = opt.dataset.cabinet;
        updateSlots();
    }
}

function toggleEndDate() {
    const freq = document.getElementById('freq_select').value;
    document.getElementById('end_date_container').style.display = freq === 'once' ? 'none' : 'block';
}

function updateSlots() {
    const procId = document.getElementById('proc_select').value;
    const cabinet = document.getElementById('cabinet_id').value;
    const date = document.getElementById('start_date').value;

    if (!procId || !cabinet || !date) return;

    const container = document.getElementById('slots_container');
    const list = document.getElementById('slots_list');

    container.style.display = 'block';
    list.innerHTML = '<span style="font-size: 12px;">Загрузка слотов...</span>';

    fetch(`../procedures_doctor.php?ajax_action=get_slots&procedure_id=${procId}&cabinet_id=${cabinet}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            const free = data.free || [];
            list.innerHTML = '';
            if (free.length === 0) {
                list.innerHTML = '<span style="font-size: 12px; color: var(--md-error);">Нет свободных слотов</span>';
            } else {
                free.forEach(time => {
                    const btn = document.createElement('div');
                    btn.textContent = time;
                    btn.style.cssText = 'padding: 6px 12px; background: #E8DEF8; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer;';
                    btn.onclick = () => {
                        document.getElementById('time_input').value = time;
                        // Visual feedback
                        Array.from(list.children).forEach(c => c.style.background = '#E8DEF8');
                        btn.style.background = 'var(--md-primary)';
                        btn.style.color = '#fff';
                    };
                    list.appendChild(btn);
                });
            }
        })
        .catch(() => {
            list.innerHTML = '<span style="font-size: 12px; color: var(--md-error);">Ошибка загрузки</span>';
        });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
