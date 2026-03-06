<?php
namespace Managers;
use Core\JsonStore;

class AnalyticsManager {
    private $orderStore;
    private $productStore;
    private $rmStore;
    private $prodStore;

    public function __construct() {
        $this->orderStore = new JsonStore('orders');
        $this->productStore = new JsonStore('products');
        $this->rmStore = new JsonStore('raw_materials');
        $this->prodStore = new JsonStore('production_logs');
    }

    public function getExpandedAnalytics() {
        $orders = $this->orderStore->findAll();
        $products = $this->productStore->findAll();
        $rms = $this->rmStore->findAll();
        $logs = $this->prodStore->findAll();

        $skuPerformance = [];
        foreach ($products as $p) {
            $sold = 0;
            foreach ($orders as $o) {
                if ($o['status'] === 'shipped') {
                    foreach ($o['items'] as $item) {
                        if ($item['id'] === $p['id']) $sold += $item['qty'];
                    }
                }
            }
            $skuPerformance[] = [
                'name' => $p['name'],
                'sku' => $p['sku'],
                'price' => $p['price'],
                'sold' => $sold,
                'stock' => $p['quantity']
            ];
        }

        $yieldData = [];
        foreach (array_slice($logs, -5) as $log) {
             $yieldData[] = [
                 'batch' => $log['batch'],
                 'product' => $log['product_name'],
                 'efficiency' => (1 - $log['waste_factor']) * 100
             ];
        }

        return [
            'raw_materials' => $rms,
            'sku_performance' => $skuPerformance,
            'recent_logs' => array_slice(array_reverse($logs), 0, 10),
            'yield_data' => $yieldData
        ];
    }

    public function getExecutiveSummary() {
        $orders = $this->orderStore->findAll();
        $products = $this->productStore->findAll();
        $rms = $this->rmStore->findAll();
        $logs = $this->prodStore->findAll();

        $totalRevenue = 0;
        foreach ($orders as $o) {
            if ($o['status'] === 'completed' || $o['status'] === 'shipped') {
                $totalRevenue += ($o['total'] ?? 0);
            }
        }

        $stockValue = 0;
        foreach ($products as $p) {
            $stockValue += $p['quantity'] * $p['price'];
        }

        $rmValue = 0;
        foreach ($rms as $rm) {
            $rmValue += $rm['quantity'] * $rm['price'];
        }

        return [
            'revenue' => $totalRevenue,
            'stock_value' => $stockValue,
            'raw_material_value' => $rmValue,
            'order_count' => count($orders),
            'production_volume' => array_sum(array_column($logs, 'quantity')),
            'low_stock_alerts' => count(array_filter($products, function($p) { return $p['quantity'] < 100; })),
            'low_rm_alerts' => count(array_filter($rms, function($rm) { return $rm['quantity'] < ($rm['min_quantity'] ?? 0); }))
        ];
    }
}
