<?php
require_once 'Core/JsonStore.php';
require_once 'Managers/AuthManager.php';
require_once 'Managers/ProductManager.php';
require_once 'Managers/OrderManager.php';
require_once 'Managers/ClientManager.php';

use Managers\AuthManager;
use Managers\ProductManager;
use Managers\OrderManager;
use Managers\ClientManager;

$auth = new AuthManager();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($auth->login($data['username'], $data['password'])) {
        echo json_encode(['success' => true, 'user' => $auth->getCurrentUser()]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверные учетные данные']);
    }
    exit;
}

if ($action === 'logout') {
    $auth->logout();
    echo json_encode(['success' => true]);
    exit;
}

// Защищенные роуты
if (!$auth->isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$productManager = new ProductManager();
$orderManager = new OrderManager();
$clientManager = new ClientManager();

if ($action === 'get_products') {
    echo json_encode($productManager->getProducts());
} elseif ($action === 'get_orders') {
    echo json_encode($orderManager->getOrders());
} elseif ($action === 'get_clients') {
    echo json_encode($clientManager->getClients());
} elseif ($action === 'get_user') {
    echo json_encode($auth->getCurrentUser());
}
