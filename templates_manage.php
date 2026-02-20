<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;

checkRole(['admin', 'service_lead']);

$templateStore = new JsonStore('data/templates.json');
$templates = $templateStore->read();

$serviceStore = new JsonStore('data/services.json');
$services = $serviceStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = $templateStore->getNextId();
        $templates[] = [
            'id' => $id,
            'service_id' => (int)$_POST['service_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description']
        ];
        $templateStore->save($templates);
        $message = 'Шаблон успешно создан';
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $templates = array_filter($templates, fn($t) => $t['id'] !== $id);
        $templateStore->save(array_values($templates));
        $message = 'Шаблон удален';
    }
}

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
                <i class="lucide-layout"></i> Сохранить шаблон
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
                <form method="POST" onsubmit="return confirm('Удалить шаблон?');">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                    <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:4px;">
                        <i class="lucide-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
