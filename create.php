<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\ServiceManager;
use Hop\Core\NotificationManager;

checkRole(['initiator', 'admin']);

$settingsStore = new JsonStore('data/settings.json');
$notificationStore = new JsonStore('data/notifications.json');
$notifier = new NotificationManager($settingsStore, $notificationStore);

$servicesStore = new JsonStore('data/services.json');
$templatesStore = new JsonStore('data/templates.json');
$serviceManager = new ServiceManager($servicesStore, $templatesStore);

$requestStore = new JsonStore('data/requests.json');
$requestManager = new RequestManager($requestStore, $notifier);

$locStore = new JsonStore('data/locations.json');
$locations = $locStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $photoPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $photoPath = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
        } else {
            $message = 'Недопустимый тип файла.';
        }
    }

    $serviceId = (int)$_POST['service_id'];
    $performerId = null;
    $initialStatus = 'new';

    $services = $serviceManager->getAllServices();
    foreach ($services as $s) {
        if ($s['id'] === $serviceId && !empty($s['performer_id'])) {
            $performerId = $s['performer_id'];
            $initialStatus = 'assigned';
            break;
        }
    }

    $requestId = $requestManager->create([
        'initiator_id' => $_SESSION['user_id'],
        'description' => $_POST['description'],
        'service_id' => $serviceId,
        'performer_id' => $performerId,
        'status' => $initialStatus,
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
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Создание заявки</h1>
        <p style="color:var(--win-text-secondary);">Пожалуйста, опишите проблему для технической службы</p>
    </div>

    <form method="POST" enctype="multipart/form-data" style="animation: slideUp 0.6s ease-out;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px;">
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <section class="card mica" style="padding: 32px;">
                    <h2 style="margin-top:0; font-size:18px; margin-bottom:24px; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="file-text" style="color:var(--win-accent);"></i> Суть обращения
                    </h2>

                    <div class="form-group">
                        <label>Описание проблемы</label>
                        <textarea name="description" id="description" rows="6" required placeholder="Например: В 305 кабинете протекает кран, вода капает на пол..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Фотография (рекомендуется)</label>
                        <div class="photo-upload-zone" onclick="document.getElementById('photo-upload').click()">
                            <input type="file" name="photo" id="photo-upload" accept="image/*" capture="environment" style="display: none;" onchange="updateFileName(this)">
                            <div id="upload-placeholder">
                                <i data-lucide="camera" style="width: 32px; height: 32px; margin-bottom: 8px;"></i>
                                <div style="font-weight: 600;">Нажмите для снимка</div>
                                <div style="font-size: 12px; color: var(--win-text-secondary);">или выберите файл</div>
                            </div>
                            <div id="file-selected" style="display:none;">
                                <i data-lucide="check-circle" style="width: 32px; height: 32px; color: var(--status-completed); margin-bottom: 8px;"></i>
                                <div id="filename-text" style="font-weight: 600;">Файл выбран</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card mica" style="padding: 32px;">
                    <h2 style="margin-top:0; font-size:18px; margin-bottom:24px; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="map-pin" style="color:var(--win-accent);"></i> Местоположение
                    </h2>
                    <div class="form-grid">
                        <?php if (!empty($locations)): ?>
                        <div class="form-group">
                            <label>Корпус</label>
                            <select name="building" id="building-select" onchange="updateFloors()" required>
                                <option value="">-- Выберите корпус --</option>
                                <?php foreach ($locations as $l): ?>
                                    <option value="<?php echo htmlspecialchars($l['name']); ?>" data-floors='<?php echo json_encode($l['floors']); ?>'>
                                        <?php echo htmlspecialchars($l['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Этаж</label>
                            <select name="floor" id="floor-select" required>
                                <option value="">-- Выберите этаж --</option>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label>Корпус</label>
                            <input type="text" name="building" required placeholder="А, Б, В...">
                        </div>
                        <div class="form-group">
                            <label>Этаж</label>
                            <input type="text" name="floor" required placeholder="1-9...">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Кабинет / Номер</label>
                            <input type="text" name="room" required placeholder="305, Палата 12...">
                        </div>
                    </div>
                </section>
            </div>

            <aside style="display: flex; flex-direction: column; gap: 24px;">
                <section class="card mica">
                    <h3 style="margin-top:0; font-size:15px; margin-bottom:16px;">Быстрый выбор</h3>
                    <div class="form-group">
                        <label>Шаблон заявки</label>
                        <select id="template-select" onchange="applyTemplate()">
                            <option value="">-- Не использовать --</option>
                            <?php foreach ($templates as $t): ?>
                                <option value="<?php echo $t['id']; ?>" data-desc="<?php echo htmlspecialchars($t['description']); ?>" data-svc="<?php echo $t['service_id']; ?>">
                                    <?php echo htmlspecialchars($t['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <section class="card mica">
                    <h3 style="margin-top:0; font-size:15px; margin-bottom:16px;">Параметры</h3>
                    <div class="form-group">
                        <label>Служба</label>
                        <select name="service_id" id="service_id" required>
                            <?php foreach ($services as $svc): ?>
                                <option value="<?php echo $svc['id']; ?>"><?php echo htmlspecialchars($svc['name']); ?></option>
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

                <button type="submit" class="btn-primary" style="padding: 20px; font-size: 18px; font-weight: 800; box-shadow: 0 10px 20px rgba(0, 120, 212, 0.2);">
                    <i data-lucide="send"></i> Отправить
                </button>
            </aside>
        </div>
    </form>
</div>

<style>
.photo-upload-zone {
    border: 2px dashed var(--win-border);
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: rgba(0,0,0,0.01);
}
.photo-upload-zone:hover {
    border-color: var(--win-accent);
    background: rgba(0, 120, 212, 0.02);
}
@media (max-width: 900px) {
    .container > form > div { grid-template-columns: 1fr !important; }
}
</style>

<script>
function updateFloors() {
    const bSelect = document.getElementById('building-select');
    const fSelect = document.getElementById('floor-select');
    if (!bSelect || !fSelect) return;

    const option = bSelect.options[bSelect.selectedIndex];
    fSelect.innerHTML = '<option value="">-- Выберите этаж --</option>';

    if (option && option.dataset.floors) {
        const floors = JSON.parse(option.dataset.floors);
        floors.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f;
            fSelect.appendChild(opt);
        });
    }
}

function updateFileName(input) {
    if (input.files && input.files.length > 0) {
        document.getElementById('upload-placeholder').style.display = 'none';
        document.getElementById('file-selected').style.display = 'block';
        document.getElementById('filename-text').textContent = input.files[0].name;
    }
}

function applyTemplate() {
    const select = document.getElementById('template-select');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        document.getElementById('description').value = option.dataset.desc;
        document.getElementById('service_id').value = option.dataset.svc;
        saveDraft();
    }
}

const formFields = ['description', 'service_id', 'building', 'floor', 'room', 'priority'];

function saveDraft() {
    const draft = {};
    formFields.forEach(id => {
        const el = document.getElementsByName(id)[0] || document.getElementById(id);
        if (el) draft[id] = el.value;
    });
    localStorage.setItem('hop_request_draft', JSON.stringify(draft));
}

function loadDraft() {
    const draftJson = localStorage.getItem('hop_request_draft');
    if (draftJson) {
        const draft = JSON.parse(draftJson);
        formFields.forEach(id => {
            if (draft[id]) {
                const el = document.getElementsByName(id)[0] || document.getElementById(id);
                if (el) el.value = draft[id];
            }
        });
    }
}

document.querySelectorAll('input, textarea, select').forEach(el => {
    el.addEventListener('input', saveDraft);
});

document.querySelector('form').addEventListener('submit', () => {
    localStorage.removeItem('hop_request_draft');
});

window.addEventListener('load', loadDraft);
</script>

<?php include 'includes/footer.php'; ?>
