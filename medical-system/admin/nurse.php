<?php
require_once __DIR__ . '/header.php';
requireRole(['nurse', 'admin']);

use Medical\Core\ProceduresManager;

$procManager = new ProceduresManager($store);
$date = $_GET['date'] ?? date('Y-m-d');

if (isset($_POST['complete'])) {
    checkCsrf();
    $id = $_POST['assignment_id'];
    $procManager->updateAssignment($id, ['status' => 'completed']);
    echo "<script>showToast('Процедура отмечена как выполненная');</script>";
}

$assignments = $procManager->getAssignments(['date' => $date]);
$procedures = [];
foreach ($procManager->getProcedures() as $p) {
    $procedures[$p['id']] = $p;
}

// Sort by time
usort($assignments, function($a, $b) {
    return strcmp($a['time'], $b['time']);
});
?>

<div class="win-card mica-effect">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="lucide-user-check"></i> Прием пациентов (Медсестра)</h2>
        <form method="GET">
            <input type="date" name="date" class="form-win" value="<?php echo $date; ?>" onchange="this.form.submit()">
        </form>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #ccc;">
                <th style="padding: 10px;">Время</th>
                <th>Пациент</th>
                <th>Процедура</th>
                <th>Оплата</th>
                <th>Статус</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a):
                $p = $procedures[$a['procedure_id']] ?? ['name' => '?', 'price' => 0];
                $isPaid = $a['is_paid'] || (float)$p['price'] <= 0;
            ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 15px;"><strong><?php echo $a['time']; ?></strong></td>
                <td>
                    <?php echo htmlspecialchars($a['patient_name']); ?><br>
                    <small style="color: var(--text-sec);"><?php echo htmlspecialchars($a['phone']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td>
                    <?php if ($isPaid): ?>
                        <span class="status-green" style="padding: 2px 8px; border-radius: 10px; font-size: 12px;">Оплачено</span>
                    <?php else: ?>
                        <span class="status-red" style="padding: 2px 8px; border-radius: 10px; font-size: 12px;">Требуется оплата</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['status'] === 'completed'): ?>
                        <span style="color: var(--success);"><i class="lucide-check-circle"></i> Выполнено</span>
                    <?php else: ?>
                        <span style="color: var(--win-accent);">Ожидание</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['status'] !== 'completed'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="complete" class="btn-win" <?php echo !$isPaid ? 'disabled title="Сначала оплатите в кассе"' : ''; ?>>
                                <i class="lucide-user-check"></i> Принять
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($assignments)): ?>
                <tr><td colspan="6" style="padding: 40px; text-align: center; color: var(--text-sec);">Нет записей на этот день</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
