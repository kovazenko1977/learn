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
        $message = 'Шаблон создан';
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
    <div class="page-header">
        <h1>Управление шаблонами</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="padding:12px; background:var(--status-completed); color:#fff; border-radius:8px; margin-bottom:16px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>Новый шаблон</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Название шаблона</label>
                <input type="text" name="title" required placeholder="Например: Не работает интернет">
            </div>
            <div class="form-group">
                <label>Служба</label>
                <select name="service_id" required>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo $svc['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Текст заявки по умолчанию</label>
                <textarea name="description" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn-primary">Добавить шаблон</button>
        </form>
    </section>

    <section class="card">
        <h2>Список шаблонов</h2>
        <div class="table-responsive">
            <table class="table" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <th style="text-align:left; padding:8px;">Заголовок</th>
                        <th style="text-align:left; padding:8px;">Служба</th>
                        <th style="text-align:right; padding:8px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <td style="padding:8px;"><?php echo htmlspecialchars($t['title']); ?></td>
                        <td style="padding:8px;"><?php
                            foreach ($services as $s) if ($s['id'] == $t['service_id']) echo $s['name'];
                        ?></td>
                        <td style="padding:8px; text-align:right;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Удалить?')">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
