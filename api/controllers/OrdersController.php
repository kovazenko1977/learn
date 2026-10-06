<?php
// api/controllers/OrdersController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class OrdersController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        $user = require_auth();
        $orders = $this->storage->all('orders');

        // Non-admin users only see their own orders
        if (($user['role'] ?? 'user') !== 'admin') {
            $orders = array_values(array_filter($orders, function($o) use ($user) {
                return isset($o['user_id']) && (string)$o['user_id'] === (string)$user['id'];
            }));
        }

        // Sort latest orders first
        usort($orders, function($a, $b) {
            return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
        });

        json_out($orders);
    }

    public function show($id = null) {
        $user = require_auth();
        if (!$id) {
            json_out(['error' => 'ID заказа не указан'], 400);
        }
        $order = $this->storage->getById('orders', $id);
        if (!$order) {
            json_out(['error' => 'Заказ не найден'], 404);
        }

        if (($user['role'] ?? 'user') !== 'admin' && (string)$order['user_id'] !== (string)$user['id']) {
            json_out(['error' => 'Доступ запрещен'], 403);
        }

        json_out($order);
    }

    public function create() {
        $user = require_auth();
        $data = json_in();

        if (empty($data['items']) || !is_array($data['items'])) {
            json_out(['error' => 'Корзина пуста'], 400);
        }

        if (empty($data['address'])) {
            json_out(['error' => 'Укажите адрес доставки'], 400);
        }

        $items = $data['items'];
        $totalPrice = (float)($data['total_price'] ?? 0);
        $discount = (float)($data['discount'] ?? 0);
        $promocode = trim($data['promocode'] ?? '');

        // Generate clean numeric order ID e.g. #FS-10024
        $orderNum = rand(10000, 99999);
        $newOrder = [
            'id' => 'FS-' . $orderNum,
            'user_id' => $user['id'],
            'user_name' => $data['user_name'] ?? $user['name'],
            'user_phone' => $data['user_phone'] ?? $user['phone'],
            'items' => $items,
            'total_price' => $totalPrice,
            'discount' => $discount,
            'address' => trim($data['address']),
            'lat' => isset($data['lat']) ? (float)$data['lat'] : null,
            'lng' => isset($data['lng']) ? (float)$data['lng'] : null,
            'status' => 'Новый', // Новый, Готовится, Доставлен, Отменён
            'comment' => trim($data['comment'] ?? ''),
            'promocode' => $promocode ?: null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $created = $this->storage->insert('orders', $newOrder);

        // Deduct promo code usage if applicable
        if (!empty($promocode)) {
            $promos = $this->storage->get('promocodes', ['code' => $promocode]);
            if (!empty($promos)) {
                $p = $promos[0];
                $newUses = max(0, ((int)($p['uses_left'] ?? 1)) - 1);
                $this->storage->update('promocodes', $p['id'], ['uses_left' => $newUses]);
            }
        }

        // Create user notification
        $this->storage->insert('notifications', [
            'id' => 'notif_' . uniqid(),
            'user_id' => $user['id'],
            'title' => 'Заказ оформлен! 🎉',
            'message' => "Ваш заказ #FS-{$orderNum} успешно создан. Мы свяжемся с вами в ближайшее время.",
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Record stats
        $this->storage->insert('stats', [
            'id' => 'stat_' . uniqid(),
            'event' => 'order_created',
            'data' => ['order_id' => $created['id'], 'amount' => $totalPrice],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        json_out($created, 201);
    }

    public function updateStatus($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID заказа не указан'], 400);
        }

        $order = $this->storage->getById('orders', $id);
        if (!$order) {
            json_out(['error' => 'Заказ не найден'], 404);
        }

        $data = json_in();
        $newStatus = trim($data['status'] ?? '');
        $allowedStatuses = ['Новый', 'Готовится', 'Доставлен', 'Отменён'];

        if (!in_array($newStatus, $allowedStatuses)) {
            json_out(['error' => 'Недопустимый статус заказа'], 400);
        }

        $updated = $this->storage->update('orders', $id, ['status' => $newStatus]);

        // Send status update notification to customer
        if (!empty($order['user_id'])) {
            $this->storage->insert('notifications', [
                'id' => 'notif_' . uniqid(),
                'user_id' => $order['user_id'],
                'title' => "Статус заказа #{$order['id']} изменен",
                'message' => "Новый статус вашего заказа: {$newStatus}",
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        json_out($updated);
    }
}
