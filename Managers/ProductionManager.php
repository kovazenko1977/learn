<?php
namespace Managers;
use Core\JsonStore;

class ProductionManager {
    private $productStore;
    private $rmStore;
    private $logStore;

    public function __construct() {
        $this->productStore = new JsonStore('products');
        $this->rmStore = new JsonStore('raw_materials');
        $this->logStore = new JsonStore('production_logs');
    }

    public function produce($productId, $batchSize, $batchNumber, $wasteFactor = 0.05) {
        $product = $this->productStore->findOne($productId);
        if (!$product || !isset($product['bom'])) return ['success' => false, 'message' => 'Product not found or no BOM'];

        // 1. Check ingredients including waste
        $rawMaterials = $this->rmStore->findAll();
        $rmMap = [];
        foreach ($rawMaterials as $rm) { $rmMap[$rm['id']] = $rm; }

        foreach ($product['bom'] as $item) {
            $required = ($item['qty'] * $batchSize) * (1 + $wasteFactor);
            if (!isset($rmMap[$item['rm_id']]) || $rmMap[$item['rm_id']]['quantity'] < $required) {
                return ['success' => false, 'message' => "Insufficient raw materials: " . ($rmMap[$item['rm_id']]['name'] ?? $item['rm_id'])];
            }
        }

        // 2. Deduct materials
        $materialsUsed = [];
        foreach ($product['bom'] as $item) {
            $required = ($item['qty'] * $batchSize) * (1 + $wasteFactor);
            $rmMap[$item['rm_id']]['quantity'] -= $required;
            $materialsUsed[] = ['rm_id' => $item['rm_id'], 'qty' => $required];
        }
        $this->rmStore->save(array_values($rmMap));

        // 3. Increment product stock
        $product['quantity'] += $batchSize;
        $product['batch'] = $batchNumber;
        $this->productStore->update($product['id'], [
            'quantity' => $product['quantity'],
            'batch' => $product['batch']
        ]);

        // 4. Log production
        $this->logStore->create([
            'product_id' => $productId,
            'product_name' => $product['name'],
            'quantity' => $batchSize,
            'batch' => $batchNumber,
            'waste_factor' => $wasteFactor,
            'materials_used' => $materialsUsed,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return ['success' => true, 'new_quantity' => $product['quantity']];
    }

    public function getRawMaterials() {
        return $this->rmStore->findAll();
    }

    public function getLogs() {
        return $this->logStore->findAll();
    }
}
