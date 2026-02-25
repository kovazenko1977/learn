<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('patients_view')) {
    die("У вас недостаточно прав для просмотра реестра пациентов.");
}

$patientManager = new \Medical\Core\Managers\PatientManager();
$staffManager = new \Medical\Core\Managers\StaffManager();
$procedureManager = new \Medical\Core\Managers\ProcedureManager();
$bookingManager = new \Medical\Core\Managers\BookingManager();

if (isset($_GET['ajax'])) {
    $query = $_GET['q'] ?? '';
    $patients = $query ? $patientManager->search($query) : $patientManager->getAll();
    header('Content-Type: application/json');
    echo json_encode(array_values($patients));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'add' && \Medical\Core\Auth::can('patients_edit')) {
            $patientData = [
                'name' => $_POST['name'],
                'birth_date' => $_POST['birth_date'],
                'phone' => $_POST['phone'],
                'card_number' => $_POST['card_number'],
                'residence' => $_POST['residence'] ?? '',
                'treating_doctor' => $_POST['treating_doctor'] ?? '',
                'extra_info' => $_POST['extra_info'] ?? ''
            ];
            $newId = $patientManager->add($patientData);
            if (isset($_GET['ajax_submit'])) {
                echo json_encode(['success' => true, 'id' => $newId]);
                exit;
            }
        } elseif ($_POST['action'] === 'edit' && \Medical\Core\Auth::can('patients_edit')) {
            $patientManager->update($_POST['id'], [
                'name' => $_POST['name'],
                'birth_date' => $_POST['birth_date'],
                'phone' => $_POST['phone'],
                'card_number' => $_POST['card_number'],
                'residence' => $_POST['residence'] ?? '',
                'treating_doctor' => $_POST['treating_doctor'] ?? '',
                'extra_info' => $_POST['extra_info'] ?? ''
            ]);
            if (isset($_GET['ajax_submit'])) {
                echo json_encode(['success' => true]);
                exit;
            }
        } elseif ($_POST['action'] === 'delete' && \Medical\Core\Auth::can('patients_delete')) {
            $patientManager->delete($_POST['id']);
        } elseif ($_POST['action'] === 'import' && \Medical\Core\Auth::can('patients_edit') && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip BOM if present
                $bom = fread($handle, 3);
                if ($bom != "\xEF\xBB\xBF") rewind($handle);

                $headers = fgetcsv($handle, 1000, ",");
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 2) continue;
                    // Map CSV columns to patient data
                    // Expected order from export: ID, Name, BirthDate, Phone, CardNumber, Residence, ExtraInfo
                    $pData = [
                        'name' => $data[1] ?? '',
                        'birth_date' => $data[2] ?? '',
                        'phone' => $data[3] ?? '',
                        'card_number' => $data[4] ?? '',
                        'residence' => $data[5] ?? '',
                        'extra_info' => $data[6] ?? ''
                    ];
                    if (!empty($pData['name'])) {
                        $patientManager->add($pData);
                    }
                }
                fclose($handle);
                header('Location: patients.php?import_success=1');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$query = $_GET['q'] ?? '';
$doctorFilter = $_GET['doctor'] ?? '';

$patients = $query ? $patientManager->search($query) : $patientManager->getAll();

if ($doctorFilter) {
    $patients = array_filter($patients, function($p) use ($doctorFilter) {
        return ($p['treating_doctor'] ?? '') === $doctorFilter;
    });
}
$doctors = array_filter($staffManager->getAll(), function($s) {
    return $s['role'] === 'doctor' || $s['role'] === 'chief';
});
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Реестр пациентов</h1>
    <div style="display: flex; gap: 10px;">
        <a href="export.php?action=export_patients" class="btn">
            <i data-lucide="download" class="icon"></i> Экспорт
        </a>
        <?php if (\Medical\Core\Auth::can('patients_edit')): ?>
            <button class="btn" onclick="document.getElementById('importModal').style.display='block'">
                <i data-lucide="upload" class="icon"></i> Импорт
            </button>
            <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='block'">
                <i data-lucide="user-plus" class="icon"></i> Добавить пациента
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['import_success'])): ?>
    <div style="background: #dff6dd; color: #107c10; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #107c10;">
        Данные пациентов успешно импортированы.
    </div>
<?php endif; ?>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; align-items: center;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Поиск по ФИО, телефону или № карты..." style="flex-grow: 1;">
        <select name="doctor" style="width: 200px;">
            <option value="">Все врачи</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?php echo htmlspecialchars($d['name']); ?>" <?php echo (isset($_GET['doctor']) && $_GET['doctor'] === $d['name']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search" class="icon"></i> Найти
        </button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ФИО</th>
                <th style="text-align: center;">Статус</th>
                <th>Дата рождения</th>
                <th>№ Карты</th>
                <th>Телефон</th>
                <th style="text-align: right;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($patients as $p):
                $activeBooking = $bookingManager->getActiveByPatient($p['id']);
            ?>
            <tr>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($p['name']); ?></td>
                <td style="text-align: center;">
                    <?php if ($activeBooking): ?>
                        <?php if ($activeBooking['status'] === 'checked_in'): ?>
                            <div class="status-icon living" title="Проживает в номере">
                                <i data-lucide="home" class="icon-sm"></i>
                            </div>
                        <?php else: ?>
                            <div class="status-icon booked" title="Забронирован номер">
                                <i data-lucide="calendar-days" class="icon-sm"></i>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #ccc;">-</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d-m-Y', strtotime($p['birth_date'])); ?></td>
                <td><code><?php echo htmlspecialchars($p['card_number'] ?? '-'); ?></code></td>
                <td><?php echo htmlspecialchars($p['phone'] ?? '-'); ?></td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <a href="patient_card.php?id=<?php echo $p['id']; ?>" class="btn btn-sm" title="Карточка">
                            <i data-lucide="contact" class="icon" style="margin:0;"></i>
                        </a>
                        <?php if (\Medical\Core\Auth::can('patients_edit')): ?>
                            <button class="btn btn-sm" title="Редактировать" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES); ?>)'>
                                <i data-lucide="edit-3" class="icon" style="margin:0;"></i>
                            </button>
                        <?php endif; ?>
                        <?php if (\Medical\Core\Auth::can('patients_delete')): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить пациента и все его данные?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Удалить">
                                    <i data-lucide="trash-2" class="icon" style="margin:0;"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if (\Medical\Core\Auth::can('procedures_assign')): ?>
                            <button class="btn btn-sm btn-ghost" title="Записать к врачу" onclick='openAssignDoctorModal(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES); ?>)'>
                                <i data-lucide="stethoscope" class="icon" style="margin:0; color: #8b44d5;"></i>
                            </button>
                            <a href="procedures_doctor.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary" title="Назначить процедуры">
                                <i data-lucide="plus-square" class="icon" style="margin:0;"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($patients)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--win-text-secondary);">Пациенты не найдены</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 440px; margin: 80px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Редактировать пациента</h2>
        <form id="editPatientForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">ФИО</label>
                <input type="text" name="name" id="edit_name" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Дата рождения</label>
                <input type="date" name="birth_date" id="edit_birth_date" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Телефон</label>
                <input type="text" name="phone" id="edit_phone" style="width: 100%;" placeholder="+375 (__) ___-__-__">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">№ Истории болезни</label>
                <input type="text" name="card_number" id="edit_card_number" style="width: 100%;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Место жительства</label>
                <input type="text" name="residence" id="edit_residence" style="width: 100%;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Лечащий врач</label>
                <select name="treating_doctor" id="edit_treating_doctor" style="width: 100%;">
                    <option value="">-- Не назначен --</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['specialization']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 32px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Дополнительная информация</label>
                <textarea name="extra_info" id="edit_extra_info" style="width: 100%; height: 100px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('editModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 440px; margin: 80px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Импорт пациентов</h2>
        <p style="font-size: 0.9rem; color: var(--win-text-secondary); margin-bottom: 20px;">
            Выберите CSV файл для импорта. Формат должен соответствовать файлу экспорта (ID, ФИО, Дата рождения, Телефон, № карты, Адрес, Доп. инфо).
        </p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="import">
            <div style="margin-bottom: 32px;">
                <input type="file" name="csv_file" accept=".csv" required style="width: 100%;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('importModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Загрузить</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Doctor Modal -->
<div id="assignDoctorModal" style="display:none; position: fixed; z-index: 1100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);">
    <div class="card mica-effect" style="width: 500px; margin: 60px auto; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin:0;">Запись к врачу</h2>
            <button type="button" onclick="document.getElementById('assignDoctorModal').style.display='none'" style="background:none; border:none; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <p id="assign_patient_name" style="font-weight: 600; color: var(--win-accent); margin-bottom: 20px;"></p>

        <form id="assignDoctorForm" method="POST" action="procedures_doctor.php">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="assign">
            <input type="hidden" name="patient_id" id="assign_patient_id">

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 8px;">Выберите врача</label>
                <select name="doctor" id="assign_doctor_select" style="width: 100%;" required>
                    <option value="">-- Выберите врача --</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['name']); ?>" data-cabinet="<?php echo htmlspecialchars($d['specialization'] === 'Терапевт' ? '101' : '102'); ?>">
                            <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['specialization']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 8px;">Тип приема</label>
                <select name="procedure_id" id="assign_proc_select" style="width: 100%;" required>
                    <?php
                    $docProcs = array_filter($procedureManager->getAll(), function($pr) {
                        return mb_stripos($pr['name'], 'Прием') !== false;
                    });
                    foreach ($docProcs as $pr): ?>
                        <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display:block; margin-bottom: 8px;">Дата</label>
                    <input type="date" name="date" id="assign_date" value="<?php echo date('Y-m-d'); ?>" style="width: 100%;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 8px;">Кабинет</label>
                    <input type="text" name="cabinet_id" id="assign_cabinet" style="width: 100%;" required>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px;">Время приема</label>
                <div style="display: flex; gap: 8px;">
                    <input type="time" name="time" id="assign_time" style="flex-grow:1;" required>
                    <button type="button" class="btn btn-sm" onclick="fetchDoctorSlots()">Свободно</button>
                </div>
            </div>

            <div id="doctor_slots_container" style="display: none; margin-bottom: 20px; padding: 12px; background: rgba(0,0,0,0.03); border-radius: 8px; border: 1px dashed var(--win-border);">
                <div id="doctor_slots_chips" style="display: flex; flex-wrap: wrap; gap: 6px; max-height: 120px; overflow-y: auto;"></div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px;">Записать пациента</button>
        </form>
    </div>
</div>

<!-- Simple Add Modal -->
<div id="addModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 440px; margin: 80px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Новый пациент</h2>
        <form id="addPatientForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">ФИО</label>
                <input type="text" name="name" style="width: 100%;" required placeholder="Иванов Иван Иванович">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Дата рождения</label>
                <input type="date" name="birth_date" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Телефон</label>
                <input type="text" name="phone" style="width: 100%;" placeholder="+375 (__) ___-__-__" value="+375 ">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">№ Истории болезни</label>
                <input type="text" name="card_number" style="width: 100%;" placeholder="0000/2024">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Место жительства</label>
                <input type="text" name="residence" style="width: 100%;" placeholder="Город, улица...">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Лечащий врач</label>
                <select name="treating_doctor" style="width: 100%;">
                    <option value="">-- Не назначен --</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['specialization']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 32px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Дополнительная информация</label>
                <textarea name="extra_info" style="width: 100%; height: 100px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('addModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Schedule Modal -->
<div id="quickScheduleModal" style="display:none; position: fixed; z-index: 1100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);">
    <div class="card mica-effect" style="width: 440px; margin: 100px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Назначить время приема</h2>
        <p id="schedulePatientName" style="font-weight: 600; margin-bottom: 20px;"></p>
        <form id="quickScheduleForm" method="POST" action="procedures_doctor.php">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="assign">
            <input type="hidden" name="patient_id" id="schedule_patient_id">
            <input type="hidden" name="procedure_id" id="initial_proc_id">

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Врач</label>
                <input type="text" name="doctor_name_display" id="schedule_doctor_name" class="form-control" readonly style="background: #f0f0f0; width: 100%;">
                <input type="hidden" name="doctor" id="schedule_doctor_val">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Дата приема</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Время приема</label>
                <input type="time" name="time" required style="width: 100%;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Кабинет</label>
                <input type="text" name="cabinet_id" id="schedule_cabinet" required style="width: 100%;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="location.reload()">Пропустить</button>
                <button type="submit" class="btn btn-primary">Назначить</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showLoader() {
        const loader = document.getElementById('global-preloader');
        if (loader) {
            loader.classList.remove('hidden');
            loader.querySelector('.loader-text').innerText = 'Обработка...';
        }
    }
    function hideLoader() {
        const loader = document.getElementById('global-preloader');
        if (loader) loader.classList.add('hidden');
    }

    // Identify the "Initial Appointment" procedure
    const initialProc = <?php
        $iproc = array_values(array_filter($procedureManager->getAll(), function($p) {
            return mb_stripos($p['name'], 'Прием') !== false && mb_stripos($p['name'], 'терапевт') !== false;
        }))[0] ?? ['id' => '', 'default_cabinet' => ''];
        echo json_encode($iproc);
    ?>;

    document.getElementById('addPatientForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoader();
        const formData = new FormData(this);
        fetch('patients.php?ajax_submit=1', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                const doctor = formData.get('treating_doctor');
                if (doctor) {
                    document.getElementById('addModal').style.display = 'none';
                    document.getElementById('schedule_patient_id').value = data.id;
                    document.getElementById('schedulePatientName').innerText = formData.get('name');
                    document.getElementById('schedule_doctor_name').value = doctor;
                    document.getElementById('schedule_doctor_val').value = doctor;
                    document.getElementById('initial_proc_id').value = initialProc.id;
                    document.getElementById('schedule_cabinet').value = initialProc.default_cabinet || '101';
                    document.getElementById('quickScheduleModal').style.display = 'block';
                } else {
                    location.reload();
                }
            } else {
                alert('Ошибка при сохранении: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(err => {
            hideLoader();
            alert('Сетевая ошибка');
        });
    });

    document.getElementById('editPatientForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoader();
        fetch('patients.php?ajax_submit=1', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка при обновлении');
            }
        })
        .catch(err => {
            hideLoader();
            alert('Сетевая ошибка');
        });
    });

    function openEditModal(patient) {
        document.getElementById('edit_id').value = patient.id;
        document.getElementById('edit_name').value = patient.name;
        document.getElementById('edit_birth_date').value = patient.birth_date;
        document.getElementById('edit_phone').value = patient.phone || '';
        document.getElementById('edit_card_number').value = patient.card_number || '';
        document.getElementById('edit_residence').value = patient.residence || '';
        document.getElementById('edit_treating_doctor').value = patient.treating_doctor || '';
        document.getElementById('edit_extra_info').value = patient.extra_info || '';
        document.getElementById('editModal').style.display = 'block';
    }

    function openAssignDoctorModal(patient) {
        document.getElementById('assign_patient_id').value = patient.id;
        document.getElementById('assign_patient_name').innerText = patient.name;
        if (patient.treating_doctor) {
            document.getElementById('assign_doctor_select').value = patient.treating_doctor;
            const opt = document.querySelector(`#assign_doctor_select option[value="${patient.treating_doctor}"]`);
            if (opt) document.getElementById('assign_cabinet').value = opt.dataset.cabinet;
        }
        document.getElementById('assignDoctorModal').style.display = 'block';
        lucide.createIcons();
    }

    document.getElementById('assign_doctor_select').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.dataset.cabinet) {
            document.getElementById('assign_cabinet').value = opt.dataset.cabinet;
        }
    });

    function fetchDoctorSlots() {
        const doc = document.getElementById('assign_doctor_select').value;
        const cabinet = document.getElementById('assign_cabinet').value;
        const date = document.getElementById('assign_date').value;
        const procId = document.getElementById('assign_proc_select').value;

        if (!cabinet || !date || !procId) {
            alert('Выберите врача, процедуру и дату');
            return;
        }

        const container = document.getElementById('doctor_slots_container');
        const chips = document.getElementById('doctor_slots_chips');
        container.style.display = 'block';
        chips.innerHTML = '<span style="font-size: 0.8rem; color: #666;">Загрузка...</span>';

        fetch(`procedures_doctor.php?ajax_action=get_slots&cabinet_id=${cabinet}&date=${date}&procedure_id=${procId}`)
            .then(r => r.json())
            .then(data => {
                const free = data.free || [];
                chips.innerHTML = '';
                if (free.length === 0) {
                    chips.innerHTML = '<span style="font-size: 0.8rem; color: #d83b01;">Нет свободных слотов</span>';
                } else {
                    free.forEach(time => {
                        const chip = document.createElement('div');
                        chip.textContent = time;
                        chip.style.cssText = 'padding: 4px 10px; background: #8b44d5; color: white; border-radius: 12px; font-size: 0.8rem; cursor: pointer;';
                        chip.onclick = () => {
                            document.getElementById('assign_time').value = time;
                            container.style.display = 'none';
                        };
                        chips.appendChild(chip);
                    });
                }
            });
    }
</script>

<style>
.status-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
}
.status-icon.booked { background: #fff8e1; color: #b7791f; border: 1px solid #fbd38d; }
.status-icon.living { background: #dff6dd; color: #107c10; border: 1px solid #107c10; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
