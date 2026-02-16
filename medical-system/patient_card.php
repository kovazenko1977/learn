<?php
require_once __DIR__ . '/includes/header.php';
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();
$id = $_GET['id'] ?? '';
$patient = $patientManager->getById($id);

if (!$patient) {
    echo "Пациент не найден";
    include __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_history') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $entry = [
        'doctor' => \Medical\Core\Auth::getUser()['name'],
        'diagnosis_code' => $_POST['diagnosis_code'],
        'diagnosis_text' => $_POST['diagnosis_text'],
        'notes' => $_POST['notes']
    ];
        $patientManager->addHistoryEntry($id, $entry);
        header("Location: patient_card.php?id=$id");
        exit;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Карточка пациента: <?php echo htmlspecialchars($patient['name']); ?></h1>
    <a href="patients.php" class="btn">Назад к списку</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    <div>
        <div class="card mica-effect">
            <h3>Личные данные</h3>
            <p><strong>Дата рождения:</strong> <?php echo htmlspecialchars($patient['birth_date']); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($patient['phone'] ?? '-'); ?></p>
            <p><strong>№ Карты:</strong> <?php echo htmlspecialchars($patient['card_number'] ?? '-'); ?></p>
            <hr style="border:0; border-top: 1px solid var(--win-border); margin: 15px 0;">
            <a href="procedures_doctor.php?patient_id=<?php echo $id; ?>" class="btn btn-primary" style="display: block; text-align: center; margin-bottom: 10px;">Назначить процедуры</a>
            <a href="export.php?action=epicrisis&patient_id=<?php echo $id; ?>" target="_blank" class="btn" style="display: block; text-align: center;">Выписной эпикриз</a>
        </div>
    </div>

    <div>
        <div class="card mica-effect">
            <h3>История болезни (Электронная карта)</h3>
            <button class="btn" onclick="document.getElementById('historyModal').style.display='block'" style="margin-bottom: 20px;">+ Добавить запись</button>

            <div class="history-list">
                <?php if (isset($patient['history']) && is_array($patient['history'])): ?>
                    <?php foreach (array_reverse($patient['history']) as $entry): ?>
                        <div style="padding: 15px; border: 1px solid var(--win-border); border-radius: 8px; margin-bottom: 15px; background: rgba(255,255,255,0.5);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong><?php echo $entry['date']; ?></strong>
                                <span style="font-size: 0.8em; color: #666;"><?php echo htmlspecialchars($entry['doctor']); ?></span>
                            </div>
                            <div style="margin-bottom: 5px;">
                                <span class="status-gray" style="font-family: monospace;"><?php echo htmlspecialchars($entry['diagnosis_code']); ?></span>
                                <strong><?php echo htmlspecialchars($entry['diagnosis_text']); ?></strong>
                            </div>
                            <p style="font-size: 0.9em;"><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Записей пока нет.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div class="card mica-effect" style="width: 500px; margin: 100px auto;">
        <h2>Новая запись в историю болезни</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_history">
            <div style="margin-bottom: 15px; position: relative;">
                <label style="display:block;">Диагноз (МКБ-10)</label>
                <input type="text" id="mkb_search" placeholder="Поиск по МКБ-10..." style="width: 100%; margin-bottom: 5px;" onkeyup="searchMKB(this.value)">
                <div id="mkb_results" style="display:none; position: absolute; z-index: 1001; background: white; border: 1px solid #ccc; width: 100%; max-height: 150px; overflow-y: auto;"></div>
                <div style="display: flex; gap: 5px;">
                    <input type="text" name="diagnosis_code" id="diag_code" placeholder="Код" style="width: 100px;" required readonly>
                    <input type="text" name="diagnosis_text" id="diag_text" placeholder="Наименование диагноза" style="flex-grow: 1;" required readonly>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display:block;">Жалобы, осмотр, рекомендации</label>
                <textarea name="notes" style="width: 100%; height: 150px;" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('historyModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
let mkbData = [];
fetch('assets/data/mkb10.json').then(r => r.json()).then(d => mkbData = d);

function searchMKB(query) {
    const resultsDiv = document.getElementById('mkb_results');
    if (query.length < 2) {
        resultsDiv.style.display = 'none';
        return;
    }
    const filtered = mkbData.filter(i => i.code.toLowerCase().includes(query.toLowerCase()) || i.name.toLowerCase().includes(query.toLowerCase()));
    if (filtered.length > 0) {
        resultsDiv.innerHTML = filtered.map(i => `<div style="padding: 5px; cursor: pointer;" onclick="selectMKB('${i.code}', '${i.name}')"><strong>${i.code}</strong> ${i.name}</div>`).join('');
        resultsDiv.style.display = 'block';
    } else {
        resultsDiv.style.display = 'none';
    }
}

function selectMKB(code, name) {
    document.getElementById('diag_code').value = code;
    document.getElementById('diag_text').value = name;
    document.getElementById('mkb_results').style.display = 'none';
    document.getElementById('mkb_search').value = '';
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
