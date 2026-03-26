<?php
namespace Managers;
use Core\JsonStore;

class AlertManager {
    private $productStore;
    private $rmStore;

    public function __construct() {
        $this->productStore = new JsonStore('products');
        $this->rmStore = new JsonStore('raw_materials');
    }

    public function getActiveAlerts() {
        $alerts = [];

        // Product Alerts
        $products = $this->productStore->findAll();
        foreach ($products as $p) {
            if ($p['quantity'] < 50) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'stock',
                    'message' => "Критический остаток товара: {$p['name']} ({$p['quantity']} ед.)"
                ];
            }

            $expDate = strtotime($p['expiration_date'] ?? '');
            if ($expDate && $expDate < strtotime('+3 months')) {
                $alerts[] = [
                    'type' => 'info',
                    'category' => 'expiration',
                    'message' => "Истекает срок годности: {$p['name']} (до {$p['expiration_date']})"
                ];
            }
        }

        // RM Alerts
        $rms = $this->rmStore->findAll();
        foreach ($rms as $rm) {
            if ($rm['quantity'] < ($rm['min_quantity'] ?? 0)) {
                $alerts[] = [
                    'type' => 'danger',
                    'category' => 'raw_material',
                    'message' => "Дефицит сырья: {$rm['name']} (в наличии {$rm['quantity']} {$rm['unit']})"
                ];
            }
        }

        return $alerts;
    }
}
