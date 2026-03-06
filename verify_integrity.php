<?php
require_once 'Includes/autoload.php';

use Managers\ProductManager;
use Managers\ProductionManager;
use Managers\OrderManager;

$productManager = new ProductManager();
$productionManager = new ProductionManager();
$orderManager = new OrderManager();

echo "--- ALCO.BY System Integrity Audit ---\n";

// 1. Check Products & BOM
$products = $productManager->getProducts();
$rms = $productionManager->getRawMaterials();
$rmIds = array_column($rms, 'id');

foreach ($products as $p) {
    echo "[Product] Checking {$p['name']} (SKU: {$p['sku']})...\n";
    if ($p['quantity'] < 0) echo "  ERROR: Negative stock level ({$p['quantity']})\n";

    if (isset($p['bom'])) {
        foreach ($p['bom'] as $item) {
            if (!in_array($item['rm_id'], $rmIds)) {
                echo "  ERROR: BOM references non-existent raw material ID: {$item['rm_id']}\n";
            }
        }
    } else {
        echo "  WARNING: Product has no BOM defined.\n";
    }
}

// 2. Check Raw Materials
foreach ($rms as $rm) {
    if ($rm['quantity'] < 0) {
        echo "[RM] ERROR: Material {$rm['name']} has negative quantity ({$rm['quantity']})\n";
    }
}

// 3. Check Orders
$orders = $orderManager->getOrders();
$pIds = array_column($products, 'id');
foreach ($orders as $o) {
    foreach ($o['items'] as $item) {
        if (!in_array($item['id'], $pIds)) {
             echo "[Order] ERROR: Order #{$o['id']} references non-existent product ID: {$item['id']}\n";
        }
    }
}

echo "--- Audit Complete ---\n";
