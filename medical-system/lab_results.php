<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();
$id = $_GET['patient_id'] ?? '';
$patient = $id ? $patientManager->getById($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $patientId = $_POST['patient_id'];
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
                        'file' => $fileName
                    ]);
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Результаты исследований</h1>

<?php if (!$patient): ?>
    <div class="card mica-effect">
        <p>Выберите пациента для загрузки результатов.</p>
    </div>
<?php else: ?>
    <div class="card mica-effect">
        <h3>Загрузка результата для: <?php echo htmlspecialchars($patient['name']); ?></h3>
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

            <button type="submit" class="btn btn-primary">Загрузить и сохранить в историю</button>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
