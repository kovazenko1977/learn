<?php
require_once __DIR__ . '/header.php';
requireRole('admin');

use Medical\Core\ProceduresManager;

$procManager = new ProceduresManager($store);

if (isset($_POST['save'])) {
    checkCsrf();
    $procManager->saveProcedure([
        'id' => $_POST['id'] ?: null,
        'name' => $_POST['name'],
        'price' => $_POST['price'],
        'duration' => $_POST['duration'],
        'break_time' => $_POST['break_time']
    ]);
    echo "<script>showToast('Справочник обновлен');</script>";
}

if (isset($_GET['delete'])) {
    $procManager->deleteProcedure($_GET['delete']);
    header('Location: dictionary.php');
    exit;
}

$procedures = $procManager->getProcedures();
?>

<div class="win-card mica-effect">
    <h2><i class="lucide-book"></i> Справочник процедур</h2>

    <form method="POST" class="win-card" style="margin-bottom: 30px;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="id" id="procId">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
            <div>
                <label>Наименование</label>
                <input type="text" name="name" id="procName" class="form-win" required placeholder="Электрофорез">
            </div>
            <div>
                <label>Цена (руб.)</label>
                <input type="number" step="0.01" name="price" id="procPrice" class="form-win" required placeholder="0.00">
            </div>
            <div>
                <label>Время (мин)</label>
                <input type="number" name="duration" id="procDuration" class="form-win" required value="30">
            </div>
            <div>
                <label>Перерыв (мин)</label>
                <input type="number" name="break_time" id="procBreak" class="form-win" required value="5">
            </div>
            <div>
                <button type="submit" name="save" class="btn-win">Сохранить</button>
            </div>
        </div>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #ccc;">
                <th style="padding: 10px;">ID</th>
                <th>Наименование</th>
                <th>Цена</th>
                <th>Длительность</th>
                <th>Перерыв</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($procedures as $p): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><?php echo $p['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                <td><?php echo $p['price']; ?> руб.</td>
                <td><?php echo $p['duration']; ?> мин.</td>
                <td><?php echo $p['break_time']; ?> мин.</td>
                <td>
                    <button onclick="editProc(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="btn-win-sec" style="padding: 4px 8px;">Ред.</button>
                    <a href="?delete=<?php echo $p['id']; ?>" class="btn-win-sec" style="color: var(--danger); padding: 4px 8px; text-decoration: none;" onclick="return confirm('Удалить?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editProc(p) {
    document.getElementById('procId').value = p.id;
    document.getElementById('procName').value = p.name;
    document.getElementById('procPrice').value = p.price;
    document.getElementById('procDuration').value = p.duration;
    document.getElementById('procBreak').value = p.break_time;
    window.scrollTo(0, 0);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
