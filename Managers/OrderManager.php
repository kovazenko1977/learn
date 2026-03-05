<?php
namespace Managers;
use Core\JsonStore;

class OrderManager {
    private $store;
    private $productStore;

    public function __construct() {
        $this->store = new JsonStore('orders');
        $this->productStore = new JsonStore('products');
    }

    public function getOrders($clientId = null) {
        $orders = $this->store->findAll();
        if ($clientId) {
            return array_filter($orders, function($o) use ($clientId) {
                return isset($o['client_id']) && $o['client_id'] === $clientId;
            });
        }
        return $orders;
    }

    public function createOrder($data) {
        $data['status'] = 'pending';
        $data['items'] = $data['items'] ?? [];
        $data['total'] = 0;
        foreach ($data['items'] as $item) {
            $data['total'] += $item['price'] * $item['qty'];
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->store->create($data);
    }

    public function updateStatus($id, $status) {
        $order = $this->store->findOne($id);
        if (!$order) return ['success' => false, 'message' => 'Order not found'];

        if ($status === 'shipped' && $order['status'] !== 'shipped') {
            $products = $this->productStore->findAll();
            $pMap = [];
            foreach ($products as $p) $pMap[$p['id']] = $p;

            foreach ($order['items'] as $item) {
                if (!isset($pMap[$item['id']]) || $pMap[$item['id']]['quantity'] < $item['qty']) {
                    return ['success' => false, 'message' => "Insufficient stock: " . ($pMap[$item['id']]['name'] ?? $item['id'])];
                }
            }

            foreach ($order['items'] as $item) {
                $pMap[$item['id']]['quantity'] -= $item['qty'];
            }
            $this->productStore->save(array_values($pMap));
        }

        if ($status === 'returned' && $order['status'] === 'shipped') {
            // Return goods to stock
            $products = $this->productStore->findAll();
            foreach ($order['items'] as $item) {
                foreach ($products as &$p) {
                    if ($p['id'] === $item['id']) {
                        $p['quantity'] += $item['qty'];
                    }
                }
            }
            $this->productStore->save($products);
        }

        return ['success' => true, 'order' => $this->store->update($id, ['status' => $status])];
    }
}
