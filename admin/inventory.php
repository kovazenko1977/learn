<?php
require_once "auth.php";
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Inventory\InventoryManager;

$store = new JsonStore(__DIR__ . '/../data');
$inv = new InventoryManager($store);

if (isset($_POST['action']) && $_POST['action'] === 'update_qty') {
    $inv->updateStock($_POST['item_id'], (int)$_POST['quantity']);
}

$stock = $inv->getStock();
if (empty($stock)) {
    $stock = [
        ['id' => 1, 'name' => 'Полотенца', 'quantity' => 100, 'unit' => 'шт'],
        ['id' => 2, 'name' => 'Постельное белье', 'quantity' => 50, 'unit' => 'компл'],
        ['id' => 3, 'name' => 'Мыло/Шампунь', 'quantity' => 200, 'unit' => 'шт'],
        ['id' => 4, 'name' => 'Тапочки', 'quantity' => 80, 'unit' => 'пар']
    ];
    $store->save('inventory', $stock);
}

$pageTitle = 'Склад';
include 'includes/header.php';
?>
<div class="mica-card">
    <h2>📦 Учет расходных материалов</h2>
    <table>
        <thead><tr><th>Наименование</th><th>Остаток</th><th>Ед. изм.</th><th>Действие</th></tr></thead>
        <tbody>
            <?php foreach($stock as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><strong><?php echo $item['quantity']; ?></strong></td>
                    <td><?php echo $item['unit']; ?></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 5px;">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" style="width: 70px;">
                            <button type="submit" class="btn btn-primary" style="padding: 2px 10px;">Обновить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
