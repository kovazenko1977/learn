<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Procedures\ProceduresManager;

$store = new JsonStore(__DIR__ . '/../data');
$procManager = new ProceduresManager($store);

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

$allAssignments = $procManager->getAllAssignments();
$filteredAssignments = array_filter($allAssignments, function($a) use ($dateFrom, $dateTo) {
    return $a['date'] >= $dateFrom && $a['date'] <= $dateTo;
});

$totalCount = count($filteredAssignments);
$completedCount = 0;
$totalRevenue = 0;
$paidRevenue = 0;

$procStats = [];
$procedures = $store->findAll('procedures');
$procMap = []; foreach($procedures as $p) {
    $procMap[$p['id']] = $p['name'];
    $procStats[$p['id']] = ['name' => $p['name'], 'count' => 0, 'revenue' => 0];
}

foreach ($filteredAssignments as $a) {
    if (($a['status'] ?? '') === 'completed') $completedCount++;

    $price = (float)($a['price'] ?? 0);
    $totalRevenue += $price;
    if (($a['status'] ?? '') === 'paid' || ($a['status'] ?? '') === 'completed') {
        if ($price > 0) $paidRevenue += $price;
    }

    $pid = $a['procedure_id'];
    if (isset($procStats[$pid])) {
        $procStats[$pid]['count']++;
        $procStats[$pid]['revenue'] += $price;
    }
}

$pageTitle = 'Аналитика процедур';
include 'includes/header.php';
?>

<div class="mica-card" style="margin-bottom: 24px;">
    <form method="GET" class="grid-4" style="align-items: end; gap: 15px;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Период с</label>
            <input type="date" name="from" value="<?php echo $dateFrom; ?>" class="form-control">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>по</label>
            <input type="date" name="to" value="<?php echo $dateTo; ?>" class="form-control">
        </div>
        <div style="margin-bottom: 0;">
            <button type="submit" class="btn btn-primary w-100">Показать</button>
        </div>
        <div style="margin-bottom: 0;">
            <a href="procedures_analytics.php" class="btn btn-secondary w-100">Сбросить</a>
        </div>
    </form>
</div>

<div class="grid-4">
    <div class="mica-card stat-card">
        <h3>Всего назначений</h3>
        <h2><?php echo $totalCount; ?></h2>
    </div>
    <div class="mica-card stat-card">
        <h3>Выполнено</h3>
        <h2><?php echo $completedCount; ?></h2>
    </div>
    <div class="mica-card stat-card">
        <h3>Общая сумма</h3>
        <h2><?php echo number_format($totalRevenue, 0, ',', ' '); ?> ₽</h2>
    </div>
    <div class="mica-card stat-card" style="border: 1px solid #d1e7dd;">
        <h3>Получено оплаты</h3>
        <h2><?php echo number_format($paidRevenue, 0, ',', ' '); ?> ₽</h2>
    </div>
</div>

<div class="mica-card" style="margin-top: 24px;">
    <h3>📊 Статистика по процедурам</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Название процедуры</th>
                    <th>Кол-во назначений</th>
                    <th>Общая стоимость</th>
                </tr>
            </thead>
            <tbody>
                <?php
                uasort($procStats, function($a, $b) { return $b['count'] - $a['count']; });
                foreach($procStats as $stat):
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                    <td><?php echo $stat['count']; ?></td>
                    <td><?php echo number_format($stat['revenue'], 0, ',', ' '); ?> ₽</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
