<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;
use Hop\Core\ServiceManager;
use Hop\Core\NotificationManager;

checkRole(['initiator', 'performer', 'service_lead', 'controller', 'admin', 'manager']);

$settingsStore = new JsonStore('data/settings.json');
$notificationStore = new JsonStore('data/notifications.json');
$userStore = new JsonStore('data/users.json');
$notifier = new NotificationManager($settingsStore, $notificationStore, $userStore);

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

    $photos = [];
    // Handle base64 compressed images from frontend
    if (isset($_POST['compressed_photos']) && is_array($_POST['compressed_photos'])) {
        foreach ($_POST['compressed_photos'] as $base64) {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $data = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]);
                if ($type === 'jpeg') $type = 'jpg';
                if (in_array($type, ['jpg', 'png', 'webp'])) {
                    $decodedData = base64_decode($data);
                    $path = 'uploads/' . uniqid() . '.' . $type;
                    file_put_contents($path, $decodedData);
                    $photos[] = $path;
                }
            }
        }
    }

    // Fallback to traditional upload if any
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $path = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $path);
            $photos[] = $path;
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
        'photo' => $photos[0] ?? '',
        'photos' => $photos
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

    <form id="request-form" method="POST" enctype="multipart/form-data" style="animation: slideUp 0.6s ease-out;">
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
                        <label>Фотографии (можно несколько)</label>
                        <div class="photo-upload-zone" onclick="document.getElementById('photo-upload').click()">
                            <input type="file" id="photo-upload" accept="image/*" capture="environment" multiple style="display: none;" onchange="handleFiles(this.files)">
                            <div id="upload-placeholder">
                                <i data-lucide="camera" style="width: 32px; height: 32px; margin-bottom: 8px;"></i>
                                <div style="font-weight: 600;">Нажмите для снимка</div>
                                <div style="font-size: 12px; color: var(--win-text-secondary);">сжатие выполняется автоматически</div>
                            </div>
                            <div id="file-previews" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;"></div>
                        </div>
                        <div id="compressed-inputs"></div>
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

let selectedFiles = [];

async function handleFiles(files) {
    const previewContainer = document.getElementById('file-previews');
    const inputContainer = document.getElementById('compressed-inputs');

    for (let file of files) {
        if (!file.type.startsWith('image/')) continue;

        const reader = new FileReader();
        reader.onload = async (e) => {
            const img = new Image();
            img.src = e.target.result;
            img.onload = () => {
                const compressed = compressImage(img);

                const preview = document.createElement('div');
                preview.style.width = '80px';
                preview.style.height = '80px';
                preview.style.borderRadius = '8px';
                preview.style.backgroundSize = 'cover';
                preview.style.backgroundImage = `url(${compressed})`;
                preview.style.border = '2px solid var(--win-accent)';
                previewContainer.appendChild(preview);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'compressed_photos[]';
                hidden.value = compressed;
                inputContainer.appendChild(hidden);

                lucide.createIcons();
            };
        };
        reader.readAsDataURL(file);
    }
    document.getElementById('upload-placeholder').style.display = 'none';
}

function compressImage(img) {
    const canvas = document.createElement('canvas');
    const MAX_WIDTH = 1200;
    const MAX_HEIGHT = 1200;
    let width = img.width;
    let height = img.height;

    if (width > height) {
        if (width > MAX_WIDTH) {
            height *= MAX_WIDTH / width;
            width = MAX_WIDTH;
        }
    } else {
        if (height > MAX_HEIGHT) {
            width *= MAX_HEIGHT / height;
            height = MAX_HEIGHT;
        }
    }
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, width, height);
    return canvas.toDataURL('image/jpeg', 0.7);
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

document.getElementById('request-form').addEventListener('submit', async (e) => {
    if (!navigator.onLine) {
        e.preventDefault();
        saveToOfflineQueue();
        alert('Вы оффлайн. Заявка сохранена в очереди и будет отправлена автоматически при восстановлении связи.');
        window.location.href = 'index.php';
        return;
    }
    localStorage.removeItem('hop_request_draft');
});

function saveToOfflineQueue() {
    const formData = new FormData(document.getElementById('request-form'));
    const data = {};
    formData.forEach((value, key) => {
        if (key === 'compressed_photos[]') {
            if (!data[key]) data[key] = [];
            data[key].push(value);
        } else {
            data[key] = value;
        }
    });

    const queue = JSON.parse(localStorage.getItem('hop_offline_queue') || '[]');
    queue.push(data);
    localStorage.setItem('hop_offline_queue', JSON.stringify(queue));
    localStorage.removeItem('hop_request_draft');
}

async function processOfflineQueue() {
    if (!navigator.onLine) return;
    const queue = JSON.parse(localStorage.getItem('hop_offline_queue') || '[]');
    if (queue.length === 0) return;

    console.log('Processing offline queue...', queue.length);
    const remaining = [];

    for (let item of queue) {
        const formData = new FormData();
        for (let key in item) {
            if (Array.isArray(item[key])) {
                item[key].forEach(v => formData.append(key, v));
            } else {
                formData.append(key, item[key]);
            }
        }

        try {
            const resp = await fetch('create.php', {
                method: 'POST',
                body: formData
            });
            if (!resp.ok) throw new Error('Upload failed');
        } catch (e) {
            remaining.push(item);
        }
    }

    localStorage.setItem('hop_offline_queue', JSON.stringify(remaining));
    if (remaining.length === 0 && queue.length > 0) {
        alert('Все отложенные заявки успешно отправлены!');
        window.location.reload();
    }
}

window.addEventListener('online', processOfflineQueue);

window.addEventListener('load', loadDraft);
</script>

<?php include 'includes/footer.php'; ?>
