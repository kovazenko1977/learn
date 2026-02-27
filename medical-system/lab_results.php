<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('lab_view')) {
    die("У вас недостаточно прав для просмотра результатов исследований.");
}

$patientManager = new \Medical\Core\Managers\PatientManager();
$id = $_GET['patient_id'] ?? '';
$patient = $id ? $patientManager->getById($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && \Medical\Core\Auth::can('lab_upload')) {
    if (!\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        die("CSRF Error");
    }

    $patientId = $_POST['patient_id'];

    if ($_POST['action'] === 'upload') {
        if (isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['result_file']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                echo "Ошибка: Недопустимый тип файла.";
            } else {
                $uploadDir = __DIR__ . '/uploads/';
                $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['result_file']['tmp_name'], $targetPath)) {
                    $patientManager->addHistoryEntry($patientId, [
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
    } elseif ($_POST['action'] === 'entry') {
        $labType = $_POST['lab_type'];
        $data = $_POST['data'] ?? [];

        $patientManager->addHistoryEntry($patientId, [
            'doctor' => \Medical\Core\Auth::getUser()['name'],
            'diagnosis_code' => 'LAB_ENTRY',
            'diagnosis_text' => 'Результаты анализа: ' . $labType,
            'notes' => 'Структурированные данные',
            'lab_type' => $labType,
            'lab_data' => $data,
            'type' => 'structured'
        ]);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Результаты исследований</h1>
    <?php if ($patient): ?>
        <a href="patient_card.php?id=<?php echo $id; ?>" class="btn">
            <i data-lucide="arrow-left" class="icon"></i> В карту пациента
        </a>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
    <div class="card mica-effect">
        <p>Выберите пациента для загрузки результатов.</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div>
            <div class="card mica-effect">
                <h3>Загрузка файла для: <?php echo htmlspecialchars($patient['name']); ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="patient_id" value="<?php echo $id; ?>">

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Наименование исследования</label>
                        <input type="text" name="title" placeholder="Напр. Анализ крови" style="width: 100%;" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Файл (PDF, Изображение)</label>
                        <input type="file" name="result_file" style="width: 100%;" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Загрузить файл</button>
                </form>
            </div>

            <div class="card mica-effect">
                <h3>Ввод структурированных данных</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                    <input type="hidden" name="action" value="entry">
                    <input type="hidden" name="patient_id" value="<?php echo $id; ?>">

                    <div style="margin-bottom: 15px;">
                        <label style="display:block;">Тип анализа</label>
                        <select name="lab_type" id="lab_type_select" style="width: 100%;" onchange="toggleLabForm(this.value)">
                            <option value="Общий анализ крови">Общий анализ крови</option>
                            <option value="Общий анализ мочи">Общий анализ мочи</option>
                            <option value="Цитологическое заключение">Цитологическое заключение</option>
                        </select>
                    </div>

                    <div id="blood_test_form" class="lab-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                            <div><label>Гемоглобин (г/л)</label><input type="text" name="data[hemoglobin]" style="width: 100%;"></div>
                            <div><label>Лейкоциты (10⁹/л)</label><input type="text" name="data[leukocytes]" style="width: 100%;"></div>
                            <div><label>Эритроциты (10¹²/л)</label><input type="text" name="data[erythrocytes]" style="width: 100%;"></div>
                            <div><label>СОЭ (мм/ч)</label><input type="text" name="data[soe]" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div id="urine_test_form" class="lab-form" style="display:none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                            <div><label>Цвет</label><input type="text" name="data[color]" style="width: 100%;"></div>
                            <div><label>Прозрачность</label><input type="text" name="data[transparency]" style="width: 100%;"></div>
                            <div><label>Белок</label><input type="text" name="data[protein]" style="width: 100%;"></div>
                            <div><label>Сахар</label><input type="text" name="data[sugar]" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div id="cytology_form" class="lab-form" style="display:none;">
                        <div style="margin-bottom: 15px;">
                            <label>Заключение</label>
                            <textarea name="data[conclusion]" style="width: 100%; height: 80px;"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Сохранить данные</button>
                </form>
            </div>
        </div>

        <div class="card mica-effect">
            <h3>История и сравнение результатов</h3>
            <?php
            $history = $patient['history'] ?? [];
            $labHistory = array_filter($history, function($h) {
                return ($h['diagnosis_code'] === 'LAB_ENTRY' || $h['diagnosis_code'] === 'LAB');
            });

            if (empty($labHistory)):
            ?>
                <p style="color: var(--win-text-secondary); text-align: center; padding: 20px;">Результатов пока нет</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Тип исследования</th>
                                <th>Показатели / Файл</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($labHistory) as $lh): ?>
                            <tr>
                                <td><?php echo $lh['date']; ?></td>
                                <td><strong><?php echo htmlspecialchars($lh['lab_type'] ?? $lh['diagnosis_text']); ?></strong></td>
                                <td>
                                    <?php if (($lh['type'] ?? '') === 'structured'): ?>
                                        <div style="font-size: 0.8rem; line-height: 1.4;">
                                            <?php foreach ($lh['lab_data'] as $k => $v): ?>
                                                <?php if (!empty($v)): ?>
                                                    <div><span style="color: var(--win-text-secondary);"><?php echo $k; ?>:</span> <?php echo htmlspecialchars($v); ?></div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif (!empty($lh['file'])): ?>
                                        <a href="uploads/<?php echo $lh['file']; ?>" target="_blank" class="btn btn-sm btn-ghost" style="color: var(--win-accent);">
                                            <i data-lucide="file" style="width:14px; height:14px;"></i> Просмотр файла
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleLabForm(type) {
    document.querySelectorAll('.lab-form').forEach(f => f.style.display = 'none');
    if (type === 'Общий анализ крови') document.getElementById('blood_test_form').style.display = 'block';
    if (type === 'Общий анализ мочи') document.getElementById('urine_test_form').style.display = 'block';
    if (type === 'Цитологическое заключение') document.getElementById('cytology_form').style.display = 'block';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
