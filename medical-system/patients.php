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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Реестр пациентов</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='block'">+ Добавить пациента</button>
</div>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Поиск по ФИО, телефону или № карты..." style="flex-grow: 1;">
        <button type="submit" class="btn">Найти</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--win-border); text-align: left;">
                <th style="padding: 10px;">ФИО</th>
                <th style="padding: 10px;">Дата рождения</th>
                <th style="padding: 10px;">№ Карты</th>
                <th style="padding: 10px;">Телефон</th>
                <th style="padding: 10px;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($patients as $p): ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><?php echo htmlspecialchars($p['name']); ?></td>
                <td style="padding: 10px;"><?php echo date('d-m-Y', strtotime($p['birth_date'])); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($p['card_number'] ?? '-'); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($p['phone'] ?? '-'); ?></td>
                <td style="padding: 10px;">
                    <div style="display: flex; gap: 5px;">
                        <a href="patient_card.php?id=<?php echo $p['id']; ?>" class="btn btn-sm" title="Карточка"><i data-lucide="contact" class="icon"></i></a>
                        <a href="procedures_doctor.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary" title="Назначить"><i data-lucide="plus-square" class="icon"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Simple Add Modal -->
<div id="addModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div class="card mica-effect" style="width: 400px; margin: 100px auto;">
        <h2>Новый пациент</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 15px;">
                <label style="display:block;">ФИО</label>
                <input type="text" name="name" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display:block;">Дата рождения</label>
                <input type="date" name="birth_date" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display:block;">Телефон</label>
                <input type="text" name="phone" style="width: 100%;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display:block;">№ Истории болезни</label>
                <input type="text" name="card_number" style="width: 100%;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('addModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
