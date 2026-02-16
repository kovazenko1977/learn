<?php
require_once __DIR__ . '/header.php';
requireRole(['doctor', 'admin']);

use Medical\Core\ProceduresManager;

$procManager = new ProceduresManager($store);
$procedures = $procManager->getProcedures();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    checkCsrf();
    $patientData = [
        'patient_name' => $_POST['patient_name'],
        'phone' => $_POST['phone']
    ];

    $prescriptions = [];
    if (isset($_POST['selected_procs']) && is_array($_POST['selected_procs'])) {
        foreach ($_POST['selected_procs'] as $index => $procId) {
            $prescriptions[] = [
                'procedure_id' => $procId,
                'date' => $_POST['dates'][$index],
                'time' => $_POST['times'][$index],
                'status' => 'pending'
            ];
        }
    }

    if (!empty($prescriptions)) {
        $procManager->batchAssign($patientData, $prescriptions);
        echo "<script>alert('Назначения успешно сохранены'); window.location.href='doctor.php';</script>";
    }
}

// Ajax handler for slots
if (isset($_GET['action']) && $_GET['action'] === 'get_slots') {
    $date = $_GET['date'];
    $pId = $_GET['procedure_id'];
    echo json_encode($procManager->getAvailableSlots($date, $pId));
    exit;
}
?>

<div class="win-card mica-effect">
    <h2><i class="lucide-clipboard-list"></i> Назначение процедур</h2>

    <form method="POST" id="prescriptionForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="assign" value="1">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label>ФИО Пациента</label>
                <input type="text" name="patient_name" class="form-win" required placeholder="Иванов Иван Иванович">
            </div>
            <div>
                <label>Телефон</label>
                <input type="text" name="phone" class="form-win" required placeholder="+375...">
            </div>
        </div>

        <div id="procedureList">
            <h4 style="margin-top: 30px;">Список назначений</h4>
            <div class="proc-item win-card" style="padding: 15px; border: 1px dashed #ccc;">
                <div style="display: grid; grid-template-columns: 2fr 1.5fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label>Процедура</label>
                        <select name="selected_procs[]" class="form-win proc-select" required onchange="updateSlots(this)">
                            <option value="">Выберите процедуру</option>
                            <?php foreach ($procedures as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['price']; ?> руб.)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Дата</label>
                        <input type="date" name="dates[]" class="form-win date-input" required value="<?php echo date('Y-m-d'); ?>" onchange="updateSlots(this)">
                    </div>
                    <div>
                        <label>Время</label>
                        <select name="times[]" class="form-win time-select" required>
                            <option value="">Сначала выберите дату</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" class="btn-win-sec" onclick="removeProc(this)" style="color: var(--danger);">✕</button>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn-win-sec" onclick="addProc()" style="margin-top: 15px;">+ Добавить еще процедуру</button>

        <div style="margin-top: 40px; text-align: right;">
            <button type="submit" class="btn-win" style="padding: 12px 30px;">Сохранить все назначения</button>
        </div>
    </form>
</div>

<script>
function addProc() {
    const list = document.getElementById('procedureList');
    const first = list.querySelector('.proc-item').cloneNode(true);
    first.querySelector('.proc-select').value = '';
    first.querySelector('.time-select').innerHTML = '<option value="">Сначала выберите дату</option>';
    list.appendChild(first);
}

function removeProc(btn) {
    const items = document.querySelectorAll('.proc-item');
    if (items.length > 1) {
        btn.closest('.proc-item').remove();
    } else {
        alert('Должно быть хотя бы одно назначение');
    }
}

async function updateSlots(el) {
    const row = el.closest('.proc-item');
    const procId = row.querySelector('.proc-select').value;
    const date = row.querySelector('.date-input').value;
    const timeSelect = row.querySelector('.time-select');

    if (procId && date) {
        timeSelect.innerHTML = '<option>Загрузка...</option>';
        const resp = await fetch(`?action=get_slots&date=${date}&procedure_id=${procId}`);
        const slots = await resp.json();

        timeSelect.innerHTML = '';
        if (slots.length === 0) {
            timeSelect.innerHTML = '<option value="">Нет свободных мест</option>';
        } else {
            slots.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                timeSelect.appendChild(opt);
            });
        }
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
