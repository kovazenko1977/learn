<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $patientData = [
        'name' => $_POST['name'],
        'birth_date' => $_POST['birth_date'],
        'phone' => $_POST['phone'],
        'card_number' => $_POST['card_number']
    ];
        $patientManager->add($patientData);
    }
}

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

<?php include __DIR__ . '/includes/footer.php'; ?>
