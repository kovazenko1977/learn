<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Procedures\ProceduresManager;

$store = new JsonStore(__DIR__ . '/../data');
$procManager = new ProceduresManager($store);

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$assignments = $procManager->getAssignmentsByDate($date);

// Sort by time
usort($assignments, function($a, $b) {
    return strcmp($a['time'], $b['time']);
});

$guests = $store->findAll('guests');
$guestMap = []; foreach($guests as $g) $guestMap[$g['id']] = $g;

$procedures = $store->findAll('procedures');
$procMap = []; foreach($procedures as $p) $procMap[$p['id']] = $p;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $procManager->updateAssignmentStatus((int)$_POST['id'], 'completed');
    header('Location: procedures_nurse.php?date=' . $date . '&completed=1');
    exit;
}

$pageTitle = 'Прием процедур (Медсестра)';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>👩‍⚕️ График процедур на сегодня</h2>
        <form method="get" style="margin:0; display:flex; gap:10px; align-items:center;">
            <label>Дата:</label>
            <input type="date" name="date" value="<?php echo $date; ?>" onchange="this.form.submit()" style="margin:0; width:auto;">
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Время</th>
                    <th>Пациент</th>
                    <th>Процедура</th>
                    <th>Длительность</th>
                    <th>Оплата</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($assignments)): ?>
                    <tr><td colspan="6" style="text-align:center;">Нет записей на этот день</td></tr>
                <?php else: ?>
                    <?php foreach($assignments as $a):
                        $g = $guestMap[$a['guest_id']] ?? null;
                        $p = $procMap[$a['procedure_id']] ?? null;
                        $isPaid = (float)($a['price'] ?? 0) == 0 || ($a['status'] ?? '') === 'paid' || ($a['status'] ?? '') === 'completed';
                        $status = $a['status'] ?? 'assigned';
                    ?>
                    <tr style="<?php echo $status === 'completed' ? 'opacity:0.6;' : ''; ?>">
                        <td style="font-weight:600; font-size:1.1rem;"><?php echo $a['time']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($g['name'] ?? 'Unknown'); ?></strong><br>
                            <small><?php echo htmlspecialchars($g['phone'] ?? ''); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($p['name'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($p['duration'] ?? 0); ?> мин.</td>
                        <td>
                            <span class="status-badge <?php echo $isPaid ? 'status-green' : 'status-red'; ?>">
                                <?php echo $isPaid ? 'Оплачено/Беспл.' : 'Не оплачено'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($status !== 'completed'): ?>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                    <button type="submit" class="btn <?php echo !$isPaid ? 'btn-secondary' : ''; ?>" <?php echo !$isPaid ? 'onclick="return confirm(\'Процедура не оплачена. Все равно продолжить?\')"' : ''; ?>>Принял</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--primary-color); font-weight:bold;">✓ Пройдено</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
