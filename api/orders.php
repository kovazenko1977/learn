<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$orders = Storage::read('orders');

if ($method === 'GET') {
    if (Auth::isAdmin()) {
        echo json_encode($orders);
    } else {
        $user = Auth::user();
        $userOrders = array_values(array_filter($orders, fn($o) => $o['client_id'] === $user['id']));
        echo json_encode($userOrders);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user = Auth::user();

    $order = [
        'id' => uniqid(),
        'client_id' => $user['id'],
        'client_name' => $user['name'],
        'items' => $data['items'],
        'total' => $data['total'],
        'date' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];

    $orders[] = $order;
    Storage::write('orders', $orders);
    echo json_encode(['success' => true, 'order' => $order]);
}
