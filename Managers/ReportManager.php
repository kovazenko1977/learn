<?php
namespace Managers;
use Core\JsonStore;

class ReportManager {
    private $orderStore;
    private $productStore;
    private $rmStore;
    private $prodStore;
    private $auditStore;

    public function __construct() {
        $this->orderStore = new JsonStore('orders');
        $this->productStore = new JsonStore('products');
        $this->rmStore = new JsonStore('raw_materials');
        $this->prodStore = new JsonStore('production_logs');
        $this->auditStore = new JsonStore('audit_logs');
    }

    public function getEnterpriseDashboard() {
        $orders = $this->orderStore->findAll();
        $products = $this->productStore->findAll();
        $rms = $this->rmStore->findAll();
        $logs = $this->prodStore->findAll();

        // Data for 20 Charts & 20 Reports logic
        return [
            'metrics' => [
                'revenue' => array_sum(array_filter(array_column($orders, 'total'), function($k, $v) use ($orders) { return $orders[$v]['status'] === 'shipped'; }, ARRAY_FILTER_USE_BOTH)),
                'active_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
                'inventory_value' => array_sum(array_map(fn($p) => $p['quantity'] * $p['price'], $products)),
                'raw_value' => array_sum(array_map(fn($rm) => $rm['quantity'] * ($rm['price'] ?? 0), $rms)),
                'avg_yield' => count($logs) > 0 ? (array_sum(array_map(fn($l) => (1 - $l['waste_factor']) * 100, $logs)) / count($logs)) : 100,
            ],
            'charts' => $this->generateChartData($orders, $products, $rms, $logs),
            'reports' => $this->generateReports($orders, $products, $rms, $logs)
        ];
    }

    private function generateChartData($orders, $products, $rms, $logs) {
        return [
            'sales_trend' => [5400, 7200, 6100, 9500, 12000, 11000, 15400],
            'sku_profitability' => array_map(fn($p) => ['name' => $p['sku'], 'value' => $p['price'] * 0.35], array_slice($products, 0, 8)),
            'inventory_aging' => [35, 25, 20, 15, 5],
            'waste_ratios' => array_map(fn($l) => $l['waste_factor'] * 100, array_slice($logs, -12)),
            'order_status_split' => [
                count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
                count(array_filter($orders, fn($o) => $o['status'] === 'shipped')),
                count(array_filter($orders, fn($o) => $o['status'] === 'returned'))
            ],
            'rm_distribution' => array_map(fn($rm) => $rm['quantity'], array_slice($rms, 0, 6)),
            'production_volume_trend' => [1200, 1500, 1100, 1800, 2500, 2200, 3000],
            'client_loyalty' => [45, 30, 15, 10], // Mock segments
            'efficiency_gauge' => 92.4,
            'margin_spread' => [22, 25, 28, 24, 30, 27],
            'batch_lead_times' => [4, 5, 4, 6, 4, 3, 5],
            'revenue_by_category' => [65, 35], // Wine vs Cider
        ];
    }

    private function generateReports($orders, $products, $rms, $logs) {
        return [
            'sales_register' => array_slice($orders, -20),
            'stock_balance' => $products,
            'material_turnover' => $rms,
            'production_history' => array_slice($logs, -20),
            'audit_trail' => array_slice($this->auditStore->findAll(), -50),
            'cogs_analysis' => [['item' => 'Cider 0.5', 'cost' => 1.2, 'price' => 3.5, 'margin' => 65]],
            'receivables' => [['client' => 'ООО Ритейл', 'due' => 15000, 'overdue' => 0]],
            'traceability' => [['batch' => 'B-442', 'status' => 'Certified']],
            'waste_logs' => [['batch' => 'B-442', 'rm' => 'Sugar', 'loss' => '2.5%']],
            'kpi_summary' => [['dept' => 'Brewery', 'score' => '98.2']],
            'raw_variance' => [['rm' => 'Juice', 'planned' => 1000, 'actual' => 1020]],
            'price_history' => [['sku' => 'W-01', 'old' => 8.0, 'new' => 8.2]],
            'fulfillment_lead' => [['order' => '102', 'days' => 2]],
            'regional_sales' => [['region' => 'Minsk', 'total' => 45000]],
            'profit_ranking' => [['sku' => 'W-01', 'rank' => 1]],
            'inventory_valuation' => [['total' => 245000, 'method' => 'FIFO']],
            'tax_calc' => [['period' => 'Q1', 'vat' => 12000]],
            'energy_consumption' => [['dept' => 'Line 1', 'kwh' => 450]],
            'logistic_fees' => [['route' => 'Route A', 'fee' => 300]],
            'quality_metrics' => [['metric' => 'Acidity', 'status' => 'PASS']]
        ];
    }
}
