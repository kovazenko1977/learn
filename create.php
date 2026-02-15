<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\ServiceManager;

checkRole(['initiator', 'admin']);

$servicesStore = new JsonStore('data/services.json');
$templatesStore = new JsonStore('data/templates.json');
$serviceManager = new ServiceManager($servicesStore, $templatesStore);

$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photoPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $photoPath = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
        } else {
            $message = 'Недопустимый тип файла. Разрешены только изображения.';
        }
    }

    $requestId = $requestManager->create([
        'initiator_id' => $_SESSION['user_id'],
        'description' => $_POST['description'],
        'service_id' => (int)$_POST['service_id'],
        'location' => [
            'building' => $_POST['building'],
            'floor' => $_POST['floor'],
            'room' => $_POST['room']
        ],
        'priority' => $_POST['priority'],
        'photo' => $photoPath
    ]);

    if ($requestId) {
        header('Location: index.php?success=1');
        exit;
    }
}

$services = $serviceManager->getAllServices();
$templates = $serviceManager->getAllTemplates();

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Создать заявку</h1>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <section class="card">
            <div class="form-group">
                <label>Типовая заявка (шаблон)</label>
                <select id="template-select" onchange="applyTemplate()">
                    <option value="">-- Выберите шаблон --</option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?php echo $t['id']; ?>" data-desc="<?php echo htmlspecialchars($t['description']); ?>" data-svc="<?php echo $t['service_id']; ?>">
                            <?php echo $t['title']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Описание проблемы</label>
                <textarea name="description" id="description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label>Служба</label>
                <select name="service_id" id="service_id" required>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Приоритет</label>
                <select name="priority">
                    <option value="low">Низкий</option>
                    <option value="medium" selected>Средний</option>
                    <option value="high">Высокий</option>
                    <option value="critical">Критический</option>
                </select>
            </div>
        </section>

        <section class="card">
            <h2>Местоположение</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Корпус</label>
                    <input type="text" name="building" required>
                </div>
                <div class="form-group">
                    <label>Этаж</label>
                    <input type="text" name="floor" required>
                </div>
                <div class="form-group">
                    <label>Кабинет / Палата</label>
                    <input type="text" name="room" required>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Фотофиксация</h2>
            <div class="form-group">
                <input type="file" name="photo" accept="image/*" capture="environment">
                <p style="font-size:12px; color:var(--win-text-secondary); margin-top:8px;">
                    Сделайте фото проблемы для быстрой оценки
                </p>
            </div>
        </section>

        <button type="submit" class="btn-primary">Отправить заявку</button>
    </form>
</div>

<script>
function applyTemplate() {
    const select = document.getElementById('template-select');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        document.getElementById('description').value = option.dataset.desc;
        document.getElementById('service_id').value = option.dataset.svc;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
