<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Mailer.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin', 'admin_content', 'client']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit':
        $orderData = json_decode(file_get_contents('php://input'), true);
        $orderData = Security::sanitize($orderData);
        $user = Auth::getCurrentUser();

        // Fetch current price list to create snapshots
        $assigned_pl_id = $user['assigned_pricelist_id'] ?? 'default';
        $lists = Storage::read('pricelist');
        $target_list = null;
        foreach ($lists as $l) {
            if ($l['id'] === $assigned_pl_id) {
                $target_list = $l;
                break;
            }
        }

        $enriched_items = [];
        $total_sum = 0;
        foreach ($orderData['items'] as $oi) {
            $product = null;
            if ($target_list) {
                foreach ($target_list['items'] as $p) {
                    if ($p['id'] === $oi['id']) {
                        $product = $p;
                        break;
                    }
                }
            }

            $item_price = (float)($product['price'] ?? 0);
            $enriched_items[] = [
                'id' => $oi['id'],
                'qty' => $oi['qty'],
                'name_snapshot' => $product['name'] ?? 'Удаленный товар',
                'price_snapshot' => $item_price,
                'subtotal' => $oi['qty'] * $item_price
            ];
            $total_sum += ($oi['qty'] * $item_price);
        }

        $order = [
            'id' => uniqid('ord_'),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'company_name' => $user['company_name'] ?? '',
            'items' => $enriched_items,
            'total_items' => count($enriched_items),
            'total_sum' => $total_sum,
            'comment' => $orderData['comment'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'new'
        ];

        Storage::insert('orders', $order);
        Security::log('order_submit', $user['id'], 'orders', ['id' => $order['id']]);

        // Notify Admins
        $admins = array_filter(Storage::read('users'), fn($u) => in_array($u['role'], ['superadmin', 'admin_content']));
        foreach ($admins as $admin) {
            if (!empty($admin['email'])) {
                Mailer::send($admin['email'], "Новый заказ от {$order['username']}",
                    "<h1>Новый заказ #{$order['id']}</h1>" .
                    "<p>Клиент: {$order['username']} ({$order['company_name']})</p>" .
                    "<p>Сумма: " . number_format($order['total_sum'], 2) . "</p>" .
                    "<p>Комментарий: " . nl2br($order['comment']) . "</p>" .
                    "<p>Пожалуйста, проверьте панель управления для деталей.</p>"
                );
            }
        }

        echo json_encode(['success' => true, 'id' => $order['id']]);
        break;

    case 'list':
        $orders = Storage::read('orders');
        $user = Auth::getCurrentUser();

        if ($user['role'] === 'client') {
            $orders = array_filter($orders, fn($o) => $o['user_id'] === $user['id']);
        }

        echo json_encode(['success' => true, 'orders' => array_values($orders)]);
        break;

    case 'update_status':
        Auth::requireRole(['superadmin', 'admin_content']);
        $data = json_decode(file_get_contents('php://input'), true);
        Storage::update('orders', $data['id'], ['status' => $data['status']]);
        Security::log('order_status_update', $_SESSION['user_id'], 'orders', ['id' => $data['id'], 'status' => $data['status']]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
