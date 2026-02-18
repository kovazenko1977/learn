<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'add') {
            $patientData = [
                'name' => $_POST['name'],
                'birth_date' => $_POST['birth_date'],
                'phone' => $_POST['phone'],
                'card_number' => $_POST['card_number']
            ];
            $patientManager->add($patientData);
        } elseif ($_POST['action'] === 'edit' && \Medical\Core\Auth::isAdmin()) {
            $patientManager->update($_POST['id'], [
                'name' => $_POST['name'],
                'birth_date' => $_POST['birth_date'],
                'phone' => $_POST['phone'],
                'card_number' => $_POST['card_number']
            ]);
        } elseif ($_POST['action'] === 'delete' && \Medical\Core\Auth::isAdmin()) {
            $patientManager->delete($_POST['id']);
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$query = $_GET['q'] ?? '';
$patients = $query ? $patientManager->search($query) : $patientManager->getAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Реестр пациентов</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='block'">
        <i data-lucide="user-plus" class="icon"></i> Добавить пациента
    </button>
</div>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Поиск по ФИО, телефону или № карты..." style="flex-grow: 1;">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search" class="icon"></i> Найти
        </button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ФИО</th>
                <th>Дата рождения</th>
                <th>№ Карты</th>
                <th>Телефон</th>
                <th style="text-align: right;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($patients as $p): ?>
            <tr>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo date('d-m-Y', strtotime($p['birth_date'])); ?></td>
                <td><code><?php echo htmlspecialchars($p['card_number'] ?? '-'); ?></code></td>
                <td><?php echo htmlspecialchars($p['phone'] ?? '-'); ?></td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <a href="patient_card.php?id=<?php echo $p['id']; ?>" class="btn btn-sm" title="Карточка">
                            <i data-lucide="contact" class="icon" style="margin:0;"></i>
                        </a>
                        <?php if (\Medical\Core\Auth::isAdmin()): ?>
                            <button class="btn btn-sm" title="Редактировать" onclick='openEditModal(<?php echo json_encode($p); ?>)'>
                                <i data-lucide="edit-3" class="icon" style="margin:0;"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить пациента и все его данные?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Удалить">
                                    <i data-lucide="trash-2" class="icon" style="margin:0;"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="procedures_doctor.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary" title="Назначить">
                            <i data-lucide="plus-square" class="icon" style="margin:0;"></i>
                        </a>
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
        <form method="POST">
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
                <input type="text" name="phone" id="edit_phone" style="width: 100%;">
            </div>
            <div style="margin-bottom: 32px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">№ Истории болезни</label>
                <input type="text" name="card_number" id="edit_card_number" style="width: 100%;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('editModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Simple Add Modal -->
<div id="addModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 440px; margin: 80px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Новый пациент</h2>
        <form method="POST">
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
                <input type="text" name="phone" style="width: 100%;" placeholder="+7 (___) ___-__-__">
            </div>
            <div style="margin-bottom: 32px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">№ Истории болезни</label>
                <input type="text" name="card_number" style="width: 100%;" placeholder="0000/2024">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('addModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(patient) {
        document.getElementById('edit_id').value = patient.id;
        document.getElementById('edit_name').value = patient.name;
        document.getElementById('edit_birth_date').value = patient.birth_date;
        document.getElementById('edit_phone').value = patient.phone || '';
        document.getElementById('edit_card_number').value = patient.card_number || '';
        document.getElementById('editModal').style.display = 'block';
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
