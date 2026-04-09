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

        $order = [
            'id' => uniqid(),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'company_name' => $user['company_name'] ?? '',
            'items' => $orderData['items'],
            'total_items' => count($orderData['items']),
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
                    "<p>Количество позиций: {$order['total_items']}</p>" .
                    "<p>Дата: {$order['created_at']}</p>" .
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
