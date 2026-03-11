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
$pkgManager = new \Medical\Core\Managers\PackageManager();
$packages = $pkgManager->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_assign_package') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $items = $_POST['package_items'] ?? [];
        $pkgName = $_POST['package_name'] ?? '';
        foreach ($items as $item) {
            $proc = $procedureManager->getById($item['procedure_id']);
            if (!$proc) continue;
            $assignment = [
                'patient_id' => $_POST['patient_id'],
                'patient_name' => $patient['name'] ?? '?',
                'procedure_id' => $item['procedure_id'],
                'procedure_name' => $proc['name'],
                'date' => $item['date'],
                'time' => $item['time'],
                'cabinet_id' => $item['cabinet_id'],
                'price' => ($proc['is_paid'] ?? false) ? ($proc['price'] ?? 0) : 0,
                'status' => ($proc['is_paid'] ?? false) ? 'unpaid' : 'free',
                'attended' => false,
                'doctor' => \Medical\Core\Auth::getUser()['name'],
                'package_name' => $pkgName
            ];
            $scheduleManager->assign($assignment);
        }
        header("Location: attendance.php?patient_id=" . $_POST['patient_id'] . "&assigned=1");
        exit;
    }
}

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

    <!-- Package Assignment -->
    <div class="md-card" style="margin: 0 0 24px 0; background: #E8DEF8;">
        <h3 style="margin-top: 0; font-size: 16px;">Назначить пакет</h3>
        <div style="margin-bottom: 12px;">
            <select id="mobile_package_selector" class="md-input" style="height: 48px; background: #fff;">
                <option value="">Выберите пакет...</option>
                <?php foreach ($packages as $pkg): ?>
                    <option value="<?php echo $pkg['id']; ?>" data-items='<?php echo json_encode($pkg['items'], ENT_QUOTES); ?>'><?php echo htmlspecialchars($pkg['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom: 12px;">
            <input type="date" id="mobile_package_start" value="<?php echo date('Y-m-d'); ?>" class="md-input" style="height: 48px; background: #fff;">
        </div>
        <button type="button" class="md-btn md-btn-primary" style="width: 100%;" onclick="openMobilePackagePreview()">Подготовить пакет</button>
    </div>

    <div style="text-align: center; color: var(--md-secondary); font-size: 12px; margin-bottom: 24px;">ИЛИ ОТДЕЛЬНУЮ ПРОЦЕДУРУ</div>

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

<!-- Package Preview Modal -->
<div id="mobilePackageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 2000; flex-direction: column;">
    <div class="md-header">
        <button onclick="document.getElementById('mobilePackageModal').style.display='none'" style="background:none; border:none; padding: 0 16px;"><i data-lucide="x"></i></button>
        <h1 style="font-size: 18px; margin: 0;">Настройка пакета</h1>
    </div>
    <form method="POST" style="flex-grow: 1; display: flex; flex-direction: column; overflow: hidden;">
        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
        <input type="hidden" name="action" value="confirm_assign_package">
        <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">
        <input type="hidden" name="package_name" id="mobile_preview_pkg_name">

        <div id="mobile_package_items" style="flex-grow: 1; overflow-y: auto; padding: 16px;"></div>

        <div style="padding: 16px; border-top: 1px solid #eee; background: #fff;">
            <button type="submit" class="md-btn md-btn-primary" style="width: 100%; height: 48px; border-radius: 24px;">Подтвердить все</button>
        </div>
    </form>
</div>

<script>
const mobileProcs = <?php echo json_encode($procedures); ?>;

function openMobilePackagePreview() {
    const sel = document.getElementById('mobile_package_selector');
    const pkgId = sel.value;
    if (!pkgId) return;

    const startDateStr = document.getElementById('mobile_package_start').value;
    const pkgName = sel.options[sel.selectedIndex].text.trim();
    document.getElementById('mobile_preview_pkg_name').value = pkgName;
    const items = JSON.parse(sel.options[sel.selectedIndex].dataset.items);
    const container = document.getElementById('mobile_package_items');
    container.innerHTML = '';

    let idx = 0;
    items.forEach(item => {
        const proc = mobileProcs.find(p => p.id === item.procedure_id);
        if (!proc) return;

        let curDate = new Date(startDateStr);
        for (let i = 0; i < item.quantity; i++) {
            const dStr = curDate.toISOString().split('T')[0];
            const card = document.createElement('div');
            card.className = 'md-card';
            card.style.margin = '0 0 12px 0';
            card.style.padding = '12px';

            card.innerHTML = `
                <input type="hidden" name="package_items[${idx}][procedure_id]" value="${proc.id}">
                <div style="font-weight: 600; margin-bottom: 8px;">${proc.name}</div>
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 8px;">
                    <input type="date" name="package_items[${idx}][date]" value="${dStr}" class="md-input" style="height: 40px; font-size: 13px; padding: 0 8px;">
                    <div style="position: relative;">
                        <input type="time" name="package_items[${idx}][time]" id="m_time_${idx}" class="md-input" style="height: 40px; font-size: 13px; padding: 0 8px;">
                        <button type="button" onclick="showMobilePkgSlots('${proc.id}', '${proc.default_cabinet}', '${dStr}', 'm_time_${idx}')" style="position: absolute; right: 0; top: 0; height: 40px; width: 32px; background: none; border: none; color: var(--md-primary);">
                            <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                        </button>
                    </div>
                </div>
                <input type="text" name="package_items[${idx}][cabinet_id]" value="${proc.default_cabinet || ''}" class="md-input" style="height: 40px; font-size: 13px; margin-top: 8px; margin-bottom: 0; padding: 0 8px;">
            `;
            container.appendChild(card);

            fetchEarliestMobile(proc.id, proc.default_cabinet, dStr, `m_time_${idx}`);

            curDate.setDate(curDate.getDate() + 1);
            idx++;
        }
    });

    document.getElementById('mobilePackageModal').style.display = 'flex';
    lucide.createIcons();
}

function fetchEarliestMobile(procId, cabinet, date, targetId) {
    if (!cabinet) return;
    fetch(`../procedures_doctor.php?ajax_action=get_slots&procedure_id=${procId}&cabinet_id=${cabinet}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById(targetId).value = data.earliest || (data.free && data.free[0]) || '09:00';
        });
}

function showMobilePkgSlots(procId, cabinet, date, targetId) {
    const picker = document.getElementById('mobile_slot_picker');
    const container = document.getElementById('mobile_slot_chips');
    const title = document.getElementById('mobile_slot_title');

    title.innerText = `Свободно: ${date}`;
    container.innerHTML = '<div style="width:100%; text-align:center; padding: 20px;"><div class="md-spinner" style="width:24px; height:24px; border-width:2px; margin: 0 auto;"></div></div>';
    picker.style.display = 'flex';

    fetch(`../procedures_doctor.php?ajax_action=get_slots&procedure_id=${procId}&cabinet_id=${cabinet}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            const free = data.free || [];
            container.innerHTML = '';
            if (free.length === 0) {
                container.innerHTML = '<div style="width:100%; text-align:center; padding: 20px; color: var(--md-error);">Нет свободных мест</div>';
            } else {
                free.forEach(time => {
                    const chip = document.createElement('div');
                    chip.textContent = time;
                    chip.style.cssText = 'padding: 8px 16px; background: #E8DEF8; color: #1D192B; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;';
                    chip.onclick = () => {
                        document.getElementById(targetId).value = time;
                        picker.style.display = 'none';
                    };
                    container.appendChild(chip);
                });
            }
        });
}
</script>

<div id="mobile_slot_picker" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 3000; align-items: center; justify-content: center; padding: 24px;">
    <div class="md-card" style="width: 100%; margin: 0; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="mobile_slot_title" style="margin: 0; font-size: 18px;">Свободные слоты</h3>
            <button onclick="document.getElementById('mobile_slot_picker').style.display='none'" style="background:none; border:none;"><i data-lucide="x"></i></button>
        </div>
        <div id="mobile_slot_chips" style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 300px; overflow-y: auto;"></div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
