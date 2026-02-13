<?php require_once "auth.php";

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $store->save('packages', ['id' => !empty($_POST['id']) ? (int)$_POST['id'] : null, 'name' => $_POST['name'], 'base_price' => (float)$_POST['base_price']]);
    } elseif ($_POST['action'] === 'delete') {
        $store->delete('packages', (int)$_POST['id']);
    }
    header('Location: packages.php');
    exit;
}
$items = $store->findAll('packages');
?>
<!DOCTYPE html>
<html lang="ru"><head><meta charset="UTF-8"><link rel="stylesheet" href="../public/assets/css/admin.css"></head><body>
<div class="mica-card"><h2>Пакеты</h2>
<table><?php foreach($items as $i): ?><tr><td><?php echo $i['name']; ?></td><td><?php echo $i['base_price']; ?></td></tr><?php endforeach; ?></table>
<form method="post"><input type="hidden" name="action" value="save"><input type="text" name="name" required><input type="number" name="base_price" required><button type="submit">Добавить</button></form>
</div></body></html>
