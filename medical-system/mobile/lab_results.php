<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('lab_view')) {
    die("У вас недостаточно прав.");
}

$patientManager = new \Medical\Core\Managers\PatientManager();
$id = $_GET['patient_id'] ?? '';
$patient = $id ? $patientManager->getById($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'upload' && \Medical\Core\Auth::can('lab_upload')) {
             if (isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['result_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions)) {
                    $uploadDir = __DIR__ . '/../uploads/';
                    $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['result_file']['tmp_name'], $uploadDir . $fileName)) {
                        $patientManager->addHistoryEntry($id, [
                            'doctor' => \Medical\Core\Auth::getUser()['name'],
                            'diagnosis_code' => 'LAB',
                            'diagnosis_text' => 'Результаты исследований: ' . $_POST['title'],
                            'notes' => 'Файл: ' . $fileName,
                            'file' => $fileName,
                            'type' => 'upload'
                        ]);
                    }
                }
             }
        } elseif ($_POST['action'] === 'entry' && \Medical\Core\Auth::can('lab_upload')) {
            $patientManager->addHistoryEntry($id, [
                'doctor' => \Medical\Core\Auth::getUser()['name'],
                'diagnosis_code' => 'LAB_ENTRY',
                'diagnosis_text' => 'Результаты анализа: ' . $_POST['lab_type'],
                'notes' => 'Структурированные данные',
                'lab_type' => $_POST['lab_type'],
                'lab_data' => $_POST['data'] ?? [],
                'type' => 'structured'
            ]);
        }
        header("Location: lab_results.php?patient_id=$id&success=1");
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; display: flex; align-items: center; gap: 12px; background: #F3EDF7;">
    <a href="patient_details.php?id=<?php echo $id; ?>" style="color: inherit;"><i data-lucide="arrow-left"></i></a>
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Результаты анализов</h2>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <?php if (!$patient): ?>
        <div style="text-align: center; padding: 48px; color: var(--md-secondary);">Выберите пациента в реестре</div>
    <?php else: ?>
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 500; color: var(--md-primary);"><?php echo htmlspecialchars($patient['name']); ?></h3>

        <div style="display: flex; gap: 8px; margin-bottom: 24px;">
            <button onclick="switchTab('view')" id="tab-btn-view" style="flex: 1; padding: 8px; border: none; background: none; border-bottom: 2px solid var(--md-primary); color: var(--md-primary); font-weight: 500;">История</button>
            <button onclick="switchTab('add')" id="tab-btn-add" style="flex: 1; padding: 8px; border: none; background: none; color: var(--md-secondary); font-weight: 500;">Добавить</button>
        </div>

        <div id="lab-tab-view">
            <?php
            $labHistory = array_filter($patient['history'] ?? [], function($h) {
                return ($h['diagnosis_code'] === 'LAB_ENTRY' || $h['diagnosis_code'] === 'LAB');
            });
            if (empty($labHistory)):
            ?>
                <p style="text-align: center; color: var(--md-secondary); padding: 32px;">Данных нет</p>
            <?php else: ?>
                <?php foreach (array_reverse($labHistory) as $lh): ?>
                    <div class="md-card" style="margin: 0 0 12px 0; padding: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--md-secondary); margin-bottom: 4px;">
                            <span><?php echo $lh['date']; ?></span>
                            <span><?php echo ($lh['type']??'') === 'upload' ? 'Файл' : 'Данные'; ?></span>
                        </div>
                        <div style="font-weight: 600; font-size: 15px; margin-bottom: 8px;"><?php echo htmlspecialchars($lh['lab_type'] ?? $lh['diagnosis_text']); ?></div>

                        <?php if (($lh['type'] ?? '') === 'structured'): ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-size: 13px;">
                                <?php foreach ($lh['lab_data'] as $k => $v): ?>
                                    <?php if (!empty($v)): ?>
                                        <div style="color: var(--md-secondary);"><?php echo $k; ?>:</div>
                                        <div style="font-weight: 500; text-align: right;"><?php echo htmlspecialchars($v); ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!empty($lh['file'])): ?>
                            <a href="../uploads/<?php echo $lh['file']; ?>" target="_blank" class="md-btn" style="width: 100%; height: 32px; font-size: 12px; background: #E8DEF8;">
                                <i data-lucide="file-text" style="width:14px; height:14px; margin-right: 6px;"></i> Открыть документ
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="lab-tab-add" style="display: none;">
            <div class="md-card" style="margin: 0 0 16px 0;">
                <h4 style="margin: 0 0 12px 0;">Загрузка документа</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="upload">
                    <input type="text" name="title" placeholder="Название исследования..." class="md-input" required>
                    <input type="file" name="result_file" class="md-input" style="padding: 12px; height: auto;" required>
                    <button type="submit" class="md-btn md-btn-primary" style="width: 100%;">Загрузить</button>
                </form>
            </div>

            <div class="md-card" style="margin: 0;">
                <h4 style="margin: 0 0 12px 0;">Ввод показателей</h4>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="entry">
                    <select name="lab_type" class="md-input" onchange="toggleFields(this.value)">
                        <option value="Общий анализ крови">Общий анализ крови</option>
                        <option value="Общий анализ мочи">Общий анализ мочи</option>
                        <option value="Биохимия">Биохимия</option>
                    </select>

                    <div id="blood-fields">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="flex: 1; font-size: 14px;">Гемоглобин:</span>
                            <input type="text" name="data[Гемоглобин]" class="md-input" style="width: 80px; margin: 0; height: 40px;">
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="flex: 1; font-size: 14px;">Лейкоциты:</span>
                            <input type="text" name="data[Лейкоциты]" class="md-input" style="width: 80px; margin: 0; height: 40px;">
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="flex: 1; font-size: 14px;">СОЭ:</span>
                            <input type="text" name="data[СОЭ]" class="md-input" style="width: 80px; margin: 0; height: 40px;">
                        </div>
                    </div>

                    <button type="submit" class="md-btn md-btn-primary" style="width: 100%; margin-top: 12px;">Сохранить</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function switchTab(tab) {
    document.getElementById('lab-tab-view').style.display = tab === 'view' ? 'block' : 'none';
    document.getElementById('lab-tab-add').style.display = tab === 'add' ? 'block' : 'none';

    document.getElementById('tab-btn-view').style.color = tab === 'view' ? 'var(--md-primary)' : 'var(--md-secondary)';
    document.getElementById('tab-btn-view').style.borderBottom = tab === 'view' ? '2px solid var(--md-primary)' : 'none';

    document.getElementById('tab-btn-add').style.color = tab === 'add' ? 'var(--md-primary)' : 'var(--md-secondary)';
    document.getElementById('tab-btn-add').style.borderBottom = tab === 'add' ? '2px solid var(--md-primary)' : 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
