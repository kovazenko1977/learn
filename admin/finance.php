<?php
require_once "auth.php";
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Finance\FinancialManager;

$store = new JsonStore(__DIR__ . '/../data');
$fin = new FinancialManager($store);

if (isset($_POST['amount'])) {
    $fin->addExpense([
        'amount' => (float)$_POST['amount'],
        'category' => $_POST['category'],
        'comment' => $_POST['comment']
    ]);
}

$expenses = $fin->getExpenses();
$pageTitle = 'Финансы';
include 'includes/header.php';
?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    <div class="mica-card">
        <h3>Добавить расход</h3>
        <form method="POST">
            <div class="form-group"><label>Сумма</label><input type="number" name="amount" required style="width:100%"></div>
            <div class="form-group"><label>Категория</label><input type="text" name="category" required style="width:100%"></div>
            <div class="form-group"><label>Комментарий</label><textarea name="comment" style="width:100%"></textarea></div>
            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>
    </div>
    <div class="mica-card">
        <h3>История расходов</h3>
        <table>
            <thead><tr><th>Дата</th><th>Категория</th><th>Сумма</th><th>Инфо</th></tr></thead>
            <tbody>
                <?php foreach(array_reverse($expenses) as $e): ?>
                    <tr><td><?php echo date('d.m', strtotime($e['date'])); ?></td><td><?php echo htmlspecialchars($e['category']); ?></td><td><?php echo number_format($e['amount'], 0, '.', ' '); ?></td><td><?php echo htmlspecialchars($e['comment']??''); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
