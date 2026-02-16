<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Procedures\ProceduresManager;

$store = new JsonStore(__DIR__ . '/../data');
$procManager = new ProceduresManager($store);

$guests = $store->findAll('guests');
$procedures = $store->findAll('procedures');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    $procManager->assignProcedure([
        'guest_id' => (int)$_POST['guest_id'],
        'procedure_id' => (int)$_POST['procedure_id'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'status' => 'assigned',
        'price' => (float)$_POST['price'] // Store price at time of assignment
    ]);
    header('Location: procedures_doctor.php?success=1');
    exit;
}

$pageTitle = 'Назначение процедур (Врач)';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>👨‍⚕️ Новое назначение</h2>
    <form method="post" id="assign-form">
        <input type="hidden" name="action" value="assign">

        <div class="grid-2">
            <div>
                <label>Выберите пациента (ФИО или телефон)</label>
                <div style="position: relative;">
                    <input type="text" id="patient-search" placeholder="Начните вводить..." autocomplete="off" style="padding-right: 35px;">
                    <button type="button" id="clear-search" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; display: none;">✕</button>
                </div>
                <select name="guest_id" id="guest-id" required size="5" style="display:none; margin-top:5px; width: 100%;" class="form-control">
                    <?php foreach($guests as $g): ?>
                        <option value="<?php echo $g['id']; ?>" data-name="<?php echo htmlspecialchars($g['name']); ?>">
                            <?php echo htmlspecialchars($g['name']); ?> (<?php echo htmlspecialchars($g['phone'] ?? ''); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="selected-patient-wrapper" style="margin-top:10px; padding:10px; background:rgba(0,120,212,0.05); border-radius:8px; display:none; justify-content: space-between; align-items: center;">
                    <div id="selected-patient" style="font-weight:600; color:var(--primary-color);"></div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()" style="padding: 2px 8px; font-size: 12px;">Изменить</button>
                </div>
            </div>

            <div>
                <label>Выберите процедуру</label>
                <select name="procedure_id" id="procedure-id" required onchange="updatePriceAndSlots()">
                    <option value="">-- Выберите процедуру --</option>
                    <?php foreach($procedures as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-dur="<?php echo $p['duration']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['duration']; ?> мин, <?php echo $p['price']; ?> ₽)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="price" id="proc-price">
            </div>
        </div>

        <div class="grid-2" style="margin-top:20px;">
            <div>
                <label>Дата</label>
                <input type="date" name="date" id="proc-date" required value="<?php echo date('Y-m-d'); ?>" onchange="updateSlots()">
            </div>
            <div>
                <label>Свободное время</label>
                <select name="time" id="proc-time" required>
                    <option value="">-- Сначала выберите дату и процедуру --</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn">Назначить процедуру</button>
        </div>
    </form>
</div>

<div class="mica-card">
    <h3>📋 Последние назначения</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Пациент</th>
                    <th>Процедура</th>
                    <th>Дата и время</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $allAssignments = $procManager->getAllAssignments();
                $allAssignments = array_reverse($allAssignments);
                $limit = 10;
                $count = 0;
                $guestMap = []; foreach($guests as $g) $guestMap[$g['id']] = $g['name'];
                $procMap = []; foreach($procedures as $p) $procMap[$p['id']] = $p['name'];

                foreach($allAssignments as $a):
                    if($count >= $limit) break;
                    $count++;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($guestMap[$a['guest_id']] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($procMap[$a['procedure_id']] ?? 'Unknown'); ?></td>
                    <td><?php echo $a['date']; ?> <?php echo $a['time']; ?></td>
                    <td>
                        <span class="status-badge <?php echo ($a['price'] == 0 ? 'status-gray' : ($a['status'] === 'paid' ? 'status-green' : 'status-red')); ?>">
                            <?php
                                if($a['status'] === 'completed') echo 'Выполнена';
                                elseif($a['price'] == 0) echo 'Бесплатно';
                                elseif($a['status'] === 'paid') echo 'Оплачено';
                                else echo 'Ожидает оплаты';
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const patientSearch = document.getElementById('patient-search');
    const clearSearchBtn = document.getElementById('clear-search');
    const guestSelect = document.getElementById('guest-id');
    const selectedPatientDiv = document.getElementById('selected-patient');
    const selectedPatientWrapper = document.getElementById('selected-patient-wrapper');

    patientSearch.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        clearSearchBtn.style.display = val.length > 0 ? 'block' : 'none';

        if (val.length < 2) {
            guestSelect.style.display = 'none';
            return;
        }

        let visibleCount = 0;
        Array.from(guestSelect.options).forEach(opt => {
            if (opt.text.toLowerCase().includes(val)) {
                opt.style.display = 'block';
                visibleCount++;
            } else {
                opt.style.display = 'none';
            }
        });

        guestSelect.style.display = visibleCount > 0 ? 'block' : 'none';
    });

    clearSearchBtn.addEventListener('click', function() {
        patientSearch.value = '';
        this.style.display = 'none';
        guestSelect.style.display = 'none';
        patientSearch.focus();
    });

    guestSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        selectedPatientDiv.textContent = 'Пациент: ' + selectedOption.getAttribute('data-name');
        selectedPatientWrapper.style.display = 'flex';
        this.style.display = 'none';
        patientSearch.parentElement.style.display = 'none';
        patientSearch.value = '';
        clearSearchBtn.style.display = 'none';
    });

    function clearSelection() {
        guestSelect.selectedIndex = -1;
        selectedPatientWrapper.style.display = 'none';
        patientSearch.parentElement.style.display = 'block';
        patientSearch.focus();
    }

    function updatePriceAndSlots() {
        const procSelect = document.getElementById('procedure-id');
        const selected = procSelect.options[procSelect.selectedIndex];
        if (selected.value) {
            document.getElementById('proc-price').value = selected.getAttribute('data-price');
            updateSlots();
        }
    }

    async function updateSlots() {
        const procId = document.getElementById('procedure-id').value;
        const date = document.getElementById('proc-date').value;
        const timeSelect = document.getElementById('proc-time');

        if (!procId || !date) return;

        timeSelect.innerHTML = '<option>Загрузка...</option>';

        try {
            const response = await fetch(`api_slots.php?procedure_id=${procId}&date=${date}`);
            const slots = await response.json();

            timeSelect.innerHTML = '';
            if (slots.length === 0) {
                timeSelect.innerHTML = '<option value="">Нет свободного времени</option>';
            } else {
                slots.forEach(slot => {
                    const opt = document.createElement('option');
                    opt.value = slot;
                    opt.textContent = slot;
                    timeSelect.appendChild(opt);
                });
            }
        } catch (e) {
            timeSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
        }
    }

    <?php if(isset($_GET['success'])): ?>
        alert('Процедура успешно назначена!');
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
