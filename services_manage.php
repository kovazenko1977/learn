<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;

checkRole('admin');

$serviceStore = new JsonStore('data/services.json');
$services = $serviceStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = $serviceStore->getNextId();
        $services[] = [
            'id' => $id,
            'name' => $_POST['name'],
            'description' => $_POST['description']
        ];
        $serviceStore->save($services);
        $message = 'Служба добавлена';
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $services = array_filter($services, fn($s) => $s['id'] !== $id);
        $serviceStore->save(array_values($services));
        $message = 'Служба удалена';
    }
}

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
                <form method="POST" onsubmit="return confirm('Удалить службу?');">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                    <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:4px;">
                        <i class="lucide-x"></i>
                    </button>
                </form>
            </div>
            <div style="margin-top: 12px; border-top: 1px solid var(--win-border); pt: 8px; display: flex; gap: 12px; font-size: 12px; color: var(--win-text-secondary);">
                <span>ID: <?php echo $s['id']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
