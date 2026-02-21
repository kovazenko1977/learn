<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;

checkRole('admin');

$locStore = new JsonStore('data/locations.json');
$locations = $locStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = $_POST['name'];
        $floors = $_POST['floors']; // CSV string

        if ($id) {
            foreach ($locations as &$l) {
                if ($l['id'] === $id) {
                    $l['name'] = $name;
                    $l['floors'] = array_map('trim', explode(',', $floors));
                    break;
                }
            }
        } else {
            $newId = count($locations) > 0 ? max(array_column($locations, 'id')) + 1 : 1;
            $locations[] = [
                'id' => $newId,
                'name' => $name,
                'floors' => array_map('trim', explode(',', $floors))
            ];
        }
        $locStore->save($locations);
        $message = 'Объект сохранен';
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $locations = array_filter($locations, fn($l) => $l['id'] !== $id);
        $locStore->save(array_values($locations));
        $message = 'Объект удален';
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1>Справочник объектов</h1>
            <p style="color:var(--win-text-secondary);">Управление корпусами и этажами больницы</p>
        </div>
        <button onclick="window.print()" class="btn-secondary">
            <i data-lucide="printer"></i> Печать справочника
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; margin-bottom: 24px;">
        <h2 style="margin-top:0; font-size:18px; margin-bottom:20px;">Добавить / Редактировать объект</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="loc-id">

            <div class="form-group">
                <label>Название корпуса / здания</label>
                <input type="text" name="name" id="loc-name" required placeholder="Главный корпус, Корпус А...">
            </div>

            <div class="form-group">
                <label>Этажи (через запятую)</label>
                <input type="text" name="floors" id="loc-floors" required placeholder="1, 2, 3, Технический">
            </div>

            <div style="grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 8px;">
                <button type="submit" class="btn-primary" style="flex: 1;">
                    <i data-lucide="save"></i> Сохранить объект
                </button>
                <button type="button" class="btn-secondary" onclick="resetForm()">Сброс</button>
            </div>
        </form>
    </section>

    <div class="list-container">
        <?php foreach ($locations as $l): ?>
            <div class="card mica list-item" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; padding: 20px;">
                <div>
                    <div style="font-weight: 700; font-size: 16px;"><?php echo htmlspecialchars($l['name']); ?></div>
                    <div style="font-size: 13px; color: var(--win-text-secondary); margin-top: 4px;">
                        Этажи: <?php echo implode(', ', $l['floors']); ?>
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <button class="btn-icon" style="color:var(--win-accent); background:none; border:none; cursor:pointer;" onclick="editLoc(<?php echo htmlspecialchars(json_encode($l)); ?>)">
                        <i data-lucide="edit"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Удалить?')">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                        <button type="submit" class="btn-icon" style="color:var(--priority-critical); background:none; border:none; cursor:pointer;">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function editLoc(loc) {
    document.getElementById('loc-id').value = loc.id;
    document.getElementById('loc-name').value = loc.name;
    document.getElementById('loc-floors').value = loc.floors.join(', ');
    window.scrollTo({top: 0, behavior: 'smooth'});
}
function resetForm() {
    document.getElementById('loc-id').value = '';
    document.getElementById('loc-name').value = '';
    document.getElementById('loc-floors').value = '';
}
</script>

<?php include 'includes/footer.php'; ?>
