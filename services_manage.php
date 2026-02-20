<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\ServiceManager;

checkRole('admin');

$serviceStore = new JsonStore('data/services.json');
$templateStore = new JsonStore('data/templates.json');
$serviceManager = new ServiceManager($serviceStore, $templateStore);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = $serviceStore->getNextId();
        $services = $serviceStore->read();
        $services[] = [
            'id' => $id,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'performer_id' => (int)($_POST['performer_id'] ?? 0)
        ];
        $serviceStore->save($services);
        $message = 'Служба добавлена';
    } elseif ($action === 'edit') {
        $serviceManager->updateService((int)$_POST['id'], [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'performer_id' => (int)($_POST['performer_id'] ?? 0)
        ]);
        $message = 'Данные службы обновлены';
    } elseif ($action === 'delete') {
        $serviceManager->deleteService((int)$_POST['id']);
        $message = 'Служба удалена';
    }
}

$services = $serviceManager->getAllServices();
$userStore = new JsonStore('data/users.json');
$allPerformers = array_filter($userStore->read(), fn($u) => $u['role'] === 'performer');

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Управление службами</h1>
        <p style="color:var(--win-text-secondary);">Организационная структура больницы</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="animation: slideDown 0.3s ease-out;"><?php echo $message; ?></div>
    <?php endif; ?>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; margin-bottom: 24px;">
        <h2 style="margin-top:0; font-size:18px; margin-bottom:20px;">Зарегистрировать службу</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Название службы</label>
                <input type="text" name="name" required placeholder="Например: Сантехническая служба">
            </div>
            <div class="form-group">
                <label>Зона ответственности</label>
                <textarea name="description" rows="2" required placeholder="Краткое описание выполняемых работ..."></textarea>
            </div>
            <div class="form-group">
                <label>Закрепленный исполнитель</label>
                <select name="performer_id">
                    <option value="0">-- Не назначен --</option>
                    <?php foreach ($allPerformers as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">
                <i class="lucide-plus-circle"></i> Добавить в реестр
            </button>
        </form>
    </section>

    <div class="list-container" style="display: grid; gap: 12px;">
        <?php foreach ($services as $index => $s): ?>
        <div class="card mica list-item" style="animation-delay: <?php echo $index * 0.05; ?>s; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="margin: 0 0 4px 0; font-size: 16px;"><?php echo htmlspecialchars($s['name']); ?></h3>
                    <p style="margin: 0; font-size: 13px; color: var(--win-text-secondary); line-height: 1.5;">
                        <?php echo htmlspecialchars($s['description']); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-icon" style="background:none; border:none; color:var(--win-accent); cursor:pointer; padding:4px;"
                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                        <i class="lucide-edit"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Удалить службу?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:4px;">
                            <i class="lucide-trash-2"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div style="margin-top: 12px; border-top: 1px solid var(--win-border); padding-top: 12px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--win-text-secondary);">
                <span>ID: <?php echo $s['id']; ?></span>
                <span style="display:flex; align-items:center; gap:4px;">
                    <i class="lucide-user" style="width:14px;"></i>
                    <?php
                        $pName = 'Не назначен';
                        if ($s['performer_id'] ?? 0) {
                            foreach ($allPerformers as $ap) if ($ap['id'] == $s['performer_id']) $pName = $ap['name'];
                        }
                        echo htmlspecialchars($pName);
                    ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
    <div class="card mica" style="width:100%; max-width:500px; margin:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Редактировать службу</h2>
            <button onclick="closeEditModal()" style="background:none; border:none; cursor:pointer;"><i class="lucide-x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div class="form-group">
                <label>Название службы</label>
                <input type="text" name="name" id="edit-name" required>
            </div>

            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" id="edit-description" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label>Закрепленный исполнитель</label>
                <select name="performer_id" id="edit-performer_id">
                    <option value="0">-- Не назначен --</option>
                    <?php foreach ($allPerformers as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Отмена</button>
                <button type="submit" class="btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(svc) {
    document.getElementById('edit-id').value = svc.id;
    document.getElementById('edit-name').value = svc.name || '';
    document.getElementById('edit-description').value = svc.description || '';
    document.getElementById('edit-performer_id').value = svc.performer_id || 0;
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
