<?php
require_once __DIR__ . '/header.php';
requireRole(['cashier', 'admin']);

use Medical\Core\ProceduresManager;

$procManager = new ProceduresManager($store);
$search = $_GET['search'] ?? '';

if (isset($_POST['pay'])) {
    checkCsrf();
    $id = $_POST['assignment_id'];
    $procManager->updateAssignment($id, ['is_paid' => true]);
    echo "<script>showToast('Оплата зафиксирована');</script>";
}

$assignments = [];
if ($search) {
    $assignments = $procManager->getAssignments(['patient' => $search]);
}

$procedures = [];
foreach ($procManager->getProcedures() as $p) {
    $procedures[$p['id']] = $p;
}

// Sort by date and time
usort($assignments, function($a, $b) {
    $cmp = strcmp($b['date'], $a['date']);
    if ($cmp === 0) return strcmp($b['time'], $a['time']);
    return $cmp;
});
?>

<div class="win-card mica-effect">
    <h2><i class="lucide-wallet"></i> Касса (Оплата процедур)</h2>

    <form method="GET" style="margin-bottom: 30px; display: flex; gap: 10px;">
        <input type="text" name="search" class="form-win" placeholder="Поиск по ФИО или телефону..." value="<?php echo htmlspecialchars($search); ?>" style="flex-grow: 1;">
        <button type="submit" class="btn-win">Найти пациента</button>
    </form>

    <?php if ($search): ?>
    <div class="win-card">
        <h3>Результаты для: <?php echo htmlspecialchars($search); ?></h3>
        <div style="display: grid; gap: 15px;">
            <?php foreach ($assignments as $a):
                $p = $procedures[$a['procedure_id']] ?? ['name' => '?', 'price' => 0];
                $price = (float)($p['price'] ?? 0);

                $colorClass = 'status-red';
                if ($price <= 0) $colorClass = 'status-gray';
                if ($a['is_paid']) $colorClass = 'status-green';
            ?>
            <div class="win-card mica-effect <?php echo $colorClass; ?>" style="padding: 15px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0,0,0,0.1);">
                <div>
                    <strong style="font-size: 1.1em;"><?php echo htmlspecialchars($p['name']); ?></strong><br>
                    <small><?php echo $a['date']; ?> в <?php echo $a['time']; ?></small><br>
                    <strong>Стоимость: <?php echo $price; ?> руб.</strong>
                </div>
                <div>
                    <?php if ($price > 0 && !$a['is_paid']): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="pay" class="btn-win" style="background-color: var(--success);">
                                <i class="lucide-check"></i> Оплатить
                            </button>
                        </form>
                    <?php elseif ($price <= 0): ?>
                        <span style="font-weight: 500;">Бесплатно</span>
                    <?php else: ?>
                        <span style="font-weight: 600; color: var(--success);"><i class="lucide-check-circle"></i> Оплачено</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($assignments)): ?>
                <p style="text-align: center; color: var(--text-sec);">Назначения не найдены</p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-sec); padding: 40px;">Введите данные пациента для поиска назначений</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
