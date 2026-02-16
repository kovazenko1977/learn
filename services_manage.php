<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;

checkRole('admin');

$serviceStore = new JsonStore('data/services.json');
$services = $serviceStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <div class="page-header">
        <h1>Управление службами</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="padding:12px; background:var(--status-completed); color:#fff; border-radius:8px; margin-bottom:16px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>Новая служба</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Название службы</label>
                <input type="text" name="name" required placeholder="Например: Отдел вентиляции">
            </div>
            <div class="form-group">
                <label>Описание деятельности</label>
                <textarea name="description" rows="2" required></textarea>
            </div>
            <button type="submit" class="btn-primary">Добавить службу</button>
        </form>
    </section>

    <section class="card">
        <h2>Список служб</h2>
        <div class="table-responsive">
            <table class="table" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <th style="text-align:left; padding:8px;">Название</th>
                        <th style="text-align:left; padding:8px;">Описание</th>
                        <th style="text-align:right; padding:8px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr style="border-bottom: 1px solid var(--win-border);">
                        <td style="padding:8px; font-weight:600;"><?php echo htmlspecialchars($s['name']); ?></td>
                        <td style="padding:8px; font-size:14px; color:var(--win-text-secondary);"><?php echo htmlspecialchars($s['description']); ?></td>
                        <td style="padding:8px; text-align:right;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Удалить службу? Это может повлиять на привязанных пользователей.')">Удалить</button>
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
