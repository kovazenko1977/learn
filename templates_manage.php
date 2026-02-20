<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\ServiceManager;

checkRole(['admin', 'service_lead']);

$serviceStore = new JsonStore('data/services.json');
$templateStore = new JsonStore('data/templates.json');
$serviceManager = new ServiceManager($serviceStore, $templateStore);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = $templateStore->getNextId();
        $templates = $templateStore->read();
        $templates[] = [
            'id' => $id,
            'service_id' => (int)$_POST['service_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description']
        ];
        $templateStore->save($templates);
        $message = 'Шаблон успешно создан';
    } elseif ($action === 'edit') {
        $serviceManager->updateTemplate((int)$_POST['id'], [
            'service_id' => (int)$_POST['service_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description']
        ]);
        $message = 'Шаблон обновлен';
    } elseif ($action === 'delete') {
        $serviceManager->deleteTemplate((int)$_POST['id']);
        $message = 'Шаблон удален';
    }
}

$templates = $serviceManager->getAllTemplates();
$services = $serviceManager->getAllServices();

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Шаблоны заявок</h1>
        <p style="color:var(--win-text-secondary);">Типовые решения для быстрой подачи</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="animation: slideDown 0.3s ease-out;"><?php echo $message; ?></div>
    <?php endif; ?>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; margin-bottom: 24px;">
        <h2 style="margin-top:0; font-size:18px; margin-bottom:20px;">Создать шаблон</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Название (заголовок)</label>
                <input type="text" name="title" required placeholder="Например: Перегорела лампа">
            </div>
            <div class="form-group">
                <label>Целевая служба</label>
                <select name="service_id" required>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Текст по умолчанию</label>
                <textarea name="description" rows="3" required placeholder="Опишите суть проблемы для этого шаблона..."></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">
                <i data-lucide="layout"></i> Сохранить шаблон
            </button>
        </form>
    </section>

    <div class="list-container" style="display: grid; gap: 12px;">
        <?php foreach ($templates as $index => $t): ?>
        <div class="card mica list-item" style="animation-delay: <?php echo $index * 0.05; ?>s; padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;"><?php echo htmlspecialchars($t['title']); ?></div>
                    <div style="font-size: 12px; color: var(--win-accent); margin-bottom: 8px; font-weight: 500;">
                        <?php
                            foreach ($services as $s) if ($s['id'] == $t['service_id']) echo htmlspecialchars($s['name']);
                        ?>
                    </div>
                    <div style="font-size: 13px; color: var(--win-text-secondary); font-style: italic;">
                        &ldquo;<?php echo htmlspecialchars($t['description']); ?>&rdquo;
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-icon" style="background:none; border:none; color:var(--win-accent); cursor:pointer; padding:4px;"
                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                        <i data-lucide="edit"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Удалить шаблон?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:4px;">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
    <div class="card mica" style="width:100%; max-width:600px; margin:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Редактировать шаблон</h2>
            <button onclick="closeEditModal()" style="background:none; border:none; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div class="form-group">
                <label>Название шаблона</label>
                <input type="text" name="title" id="edit-title" required>
            </div>

            <div class="form-group">
                <label>Служба</label>
                <select name="service_id" id="edit-service_id" required>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Текст заявки</label>
                <textarea name="description" id="edit-description" rows="4" required></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Отмена</button>
                <button type="submit" class="btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(tmpl) {
    document.getElementById('edit-id').value = tmpl.id;
    document.getElementById('edit-title').value = tmpl.title || '';
    document.getElementById('edit-service_id').value = tmpl.service_id || '';
    document.getElementById('edit-description').value = tmpl.description || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
