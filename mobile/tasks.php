<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Planning\PlanningManager;

$store = new JsonStore(__DIR__ . '/data');
$planningManager = new PlanningManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $id = (int)$_POST['id'];
    $plans = $store->findAll('plans');
    foreach ($plans as &$p) {
        if ($p['id'] == $id) {
            $p['status'] = (($p['status'] ?? '') === 'completed') ? 'pending' : 'completed';
            $store->save('plans', $p);
            break;
        }
    }
    header("Location: tasks.php");
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$allPlans = $planningManager->getAll();
$plans = array_filter($allPlans, function($p) use ($date) {
    return ($p['date'] ?? '') === $date;
});

// Sort: uncompleted first, then by priority (High, Medium, Low)
usort($plans, function($a, $b) {
    $aDone = ($a['status'] ?? '') === 'completed';
    $bDone = ($b['status'] ?? '') === 'completed';
    if ($aDone !== $bDone) {
        return $aDone ? 1 : -1;
    }
    $prios = ['High' => 3, 'Medium' => 2, 'Low' => 1];
    $pa = $prios[$a['priority'] ?? 'Low'] ?? 1;
    $pb = $prios[$b['priority'] ?? 'Low'] ?? 1;
    return $pb - $pa;
});

$pageTitle = 'Задачи';
include 'includes/header.php';
?>

<div class="m-card" style="padding: 12px; margin-bottom: 12px;">
    <form method="get" style="display: flex; gap: 8px; align-items: center;">
        <input type="date" name="date" value="<?php echo $date; ?>" class="form-control" style="font-size: 0.8rem; padding: 6px; margin-bottom:0; flex: 1;">
        <button type="submit" class="btn-m btn-m-primary" style="padding: 6px 16px; width: auto; font-size: 0.8rem;">Ок</button>
    </form>
</div>

<div id="m-tasks-list" style="padding-bottom: 20px;">
    <?php if (empty($plans)): ?>
        <div style="text-align: center; color: #64748b; padding: 40px 20px;">
            <div style="font-size: 3rem; margin-bottom: 10px;">☕</div>
            <p>На этот день задач не запланировано.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($plans as $p):
        $isCompleted = ($p['status'] ?? '') === 'completed';
        $prioColor = '#64748b';
        if (($p['priority'] ?? '') === 'High') $prioColor = '#ef4444';
        if (($p['priority'] ?? '') === 'Medium') $prioColor = '#f59e0b';
    ?>
        <div class="m-card" style="padding: 12px; border-left: 4px solid <?php echo $isCompleted ? '#10b981' : $prioColor; ?>; opacity: <?php echo $isCompleted ? '0.7' : '1'; ?>">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                    <button type="submit" style="background: none; border: 2px solid <?php echo $isCompleted ? '#10b981' : '#e2e8f0'; ?>; width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">
                        <?php if ($isCompleted): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php endif; ?>
                    </button>
                </form>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #1e293b; text-decoration: <?php echo $isCompleted ? 'line-through' : 'none'; ?>;">
                        <?php echo htmlspecialchars($p['title']); ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                        <?php echo htmlspecialchars($p['description'] ?? ''); ?>
                    </div>
                    <div style="display: flex; gap: 8px; margin-top: 8px;">
                        <span style="font-size: 0.65rem; color: <?php echo $prioColor; ?>; font-weight: 700; text-transform: uppercase;">
                            <?php echo $p['priority'] ?? 'Low'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
