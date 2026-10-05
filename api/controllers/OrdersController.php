<?php
/**
 * OrdersController
 */

class OrdersController {
    public function handle($method) {
        $currentUser = require_auth();

        if ($method === 'GET') {
            $isAll = isset($_GET['all']) && $_GET['all'] === '1';
            if ($isAll) {
                if (($currentUser['role'] ?? '') !== 'admin') {
                    json_out(['error' => 'Доступ запрещён'], 403);
                }
                $this->getAllOrders();
            } else {
                $this->getUserOrders($currentUser['id']);
            }
        } elseif ($method === 'POST') {
            $this->createOrder($currentUser);
        } elseif ($method === 'PUT') {
            if (($currentUser['role'] ?? '') !== 'admin') {
                json_out(['error' => 'Доступ запрещён'], 403);
            }
            $this->updateStatus();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function getUserOrders($userId) {
        $db = get_storage();
        $orders = $db->get('orders');
        $userOrders = array_values(array_filter($orders, function($o) use ($userId) {
            return isset($o['user_id']) && $o['user_id'] == $userId;
        }));

        foreach ($userOrders as &$o) {
            $o['order_number'] = $o['id'] ?? $o['order_number'] ?? 'ORD-000000';
            $o['delivery_date'] = $o['date'] ?? $o['delivery_date'] ?? date('Y-m-d');
            $o['delivery_time'] = $o['time'] ?? $o['delivery_time'] ?? '12:00';
        }

        json_out(['orders' => $userOrders]);
    }

    private function getAllOrders() {
        $db = get_storage();
        $orders = $db->get('orders');

        foreach ($orders as &$o) {
            $o['order_number'] = $o['id'] ?? $o['order_number'] ?? 'ORD-000000';
            $o['delivery_date'] = $o['date'] ?? $o['delivery_date'] ?? date('Y-m-d');
            $o['delivery_time'] = $o['time'] ?? $o['delivery_time'] ?? '12:00';
        }

        json_out(['orders' => $orders]);
    }

    private function createOrder($user) {
        $data = json_in();

        if (empty($data['items']) || !is_array($data['items'])) {
            json_out(['error' => 'Корзина пуста'], 400);
        }

        $db = get_storage();
        $settingsList = $db->get('settings');
        $settings = array_values($settingsList)[0] ?? [];
        $minOrderSum = floatval($settings['min_order'] ?? $settings['min_order_amount'] ?? 0);

        $items = $data['items'];
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += floatval($item['price'] ?? 0) * intval($item['qty'] ?? 1);
        }

        if ($subtotal < $minOrderSum) {
            json_out(['error' => "Минимальная сумма заказа {$minOrderSum} BYN"], 400);
        }

        $deliveryFee = floatval($settings['delivery_price'] ?? $settings['delivery_fee'] ?? 10);
        $freeDeliveryFrom = floatval($settings['free_delivery_from'] ?? 100);

        if ($subtotal >= $freeDeliveryFrom) {
            $deliveryFee = 0;
        }

        $discount = floatval($data['discount'] ?? 0);
        $total = max(0, $subtotal - $discount + $deliveryFee);

        $orderId = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 6));

        $dateVal = clean($data['date'] ?? $data['delivery_date'] ?? date('Y-m-d'));
        $timeVal = clean($data['time'] ?? $data['delivery_time'] ?? '12:00');

        $newOrder = [
            'id' => $orderId,
            'order_number' => $orderId,
            'user_id' => $user['id'],
            'user_name' => clean($data['user_name'] ?? $user['phone']),
            'user_phone' => clean($data['user_phone'] ?? $user['phone']),
            'address' => clean($data['address'] ?? 'Минск'),
            'date' => $dateVal,
            'delivery_date' => $dateVal,
            'time' => $timeVal,
            'delivery_time' => $timeVal,
            'comment' => clean($data['comment'] ?? ''),
            'promocode' => clean($data['promocode'] ?? ''),
            'items' => $items,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'total' => $total,
            'status' => 'Новый',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->insert('orders', $newOrder);

        // Notify admins about new order
        $users = $db->get('users');
        foreach ($users as $u) {
            if (($u['role'] ?? '') === 'admin') {
                $db->insert('notifications', [
                    'user_id' => $u['id'],
                    'title' => 'Новый заказ!',
                    'message' => "Заказ #{$orderId} на сумму {$total} BYN",
                    'is_read' => false,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        json_out([
            'success' => true,
            'order' => $newOrder
        ]);
    }

    private function updateStatus() {
        $data = json_in();
        $orderId = isset($_GET['id']) ? $_GET['id'] : ($data['id'] ?? null);
        $status = clean($data['status'] ?? '');

        if (!$orderId || !$status) {
            json_out(['error' => 'Укажите ID заказа и новый статус'], 400);
        }

        $db = get_storage();
        $orders = $db->get('orders');
        $foundOrder = null;

        foreach ($orders as $o) {
            if ($o['id'] == $orderId) {
                $foundOrder = $o;
                break;
            }
        }

        if (!$foundOrder) {
            json_out(['error' => 'Заказ не найден'], 404);
        }

        $db->update('orders', $orderId, ['status' => $status]);

        // Send notification to customer
        if (isset($foundOrder['user_id'])) {
            $db->insert('notifications', [
                'user_id' => $foundOrder['user_id'],
                'title' => 'Статус заказа изменен',
                'message' => "Ваш заказ #{$orderId} переведен в статус \"{$status}\"",
                'is_read' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        json_out(['success' => true]);
    }
}
