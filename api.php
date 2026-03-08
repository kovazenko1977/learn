<?php
require_once 'Includes/autoload.php';

use Managers\AuthManager;
use Managers\ProductManager;
use Managers\OrderManager;
use Managers\ClientManager;
use Managers\PriceListManager;
use Managers\MailingManager;
use Managers\DocumentManager;
use Managers\ProductionManager;
use Managers\AnalyticsManager;
use Managers\AlertManager;
use Managers\AnnouncementManager;
use Managers\UserManager;
use Includes\ExportHelper;

$auth = new AuthManager();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($auth->login($data['username'] ?? '', $data['password'] ?? '')) {
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

if (!$auth->isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

$productManager = new ProductManager();
$orderManager = new OrderManager();
$clientManager = new ClientManager();
$priceListManager = new PriceListManager();
$mailingManager = new MailingManager();
$documentManager = new DocumentManager();
$productionManager = new ProductionManager();
$analyticsManager = new AnalyticsManager();
$alertManager = new AlertManager();
$announcementManager = new AnnouncementManager();
$userManager = new UserManager();
$reportManager = new \Managers\ReportManager();

$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'get_products':
        if (!$auth->hasPermission('products_view')) { http_response_code(403); exit; }
        echo json_encode($productManager->getProducts());
        break;
    case 'add_product':
        if (!$auth->hasPermission('products_manage')) { http_response_code(403); exit; }
        if (empty($input['name']) || empty($input['price'])) {
            echo json_encode(['success' => false, 'message' => 'Отсутствуют обязательные поля']); exit;
        }
        echo json_encode($productManager->addProduct($input));
        break;
    case 'update_product':
        if (!$auth->hasPermission('products_manage')) { http_response_code(403); exit; }
        if (empty($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Отсутствует ID']); exit;
        }
        echo json_encode($productManager->updateProduct($input['id'], $input));
        break;
    case 'delete_product':
        if (!$auth->hasPermission('products_manage')) { http_response_code(403); exit; }
        echo json_encode($productManager->deleteProduct($input['id']));
        break;
    case 'get_orders':
        if (!$auth->hasPermission('orders_view_all') && !$auth->hasPermission('orders_view_own')) { http_response_code(403); exit; }
        $clientId = $auth->hasPermission('orders_view_all') ? null : $auth->getCurrentUser()['id'];
        echo json_encode($orderManager->getOrders($clientId));
        break;
    case 'add_order':
        if (!$auth->hasPermission('orders_create')) { http_response_code(403); exit; }
        $input['client_id'] = $auth->getCurrentUser()['id'];
        $input['client_name'] = $auth->getCurrentUser()['name'];
        echo json_encode($orderManager->createOrder($input));
        break;
    case 'update_order_status':
        if (!$auth->hasPermission('orders_manage')) { http_response_code(403); exit; }
        echo json_encode($orderManager->updateStatus($input['id'], $input['status']));
        break;
    case 'get_raw_materials':
        if (!$auth->hasPermission('raw_materials_view')) { http_response_code(403); exit; }
        echo json_encode($productionManager->getRawMaterials());
        break;
    case 'update_raw_material':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; } // Strict for Admin only
        echo json_encode($productionManager->updateRawMaterial($input['id'], $input));
        break;
    case 'delete_raw_material':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; }
        echo json_encode($productionManager->deleteRawMaterial($input['id']));
        break;
    case 'produce':
        if (!$auth->hasPermission('production_log')) { http_response_code(403); exit; }
        echo json_encode($productionManager->produce($input['product_id'], $input['quantity'], $input['batch']));
        break;
    case 'update_recipe':
        if (!$auth->hasPermission('recipes_view')) { http_response_code(403); exit; }
        echo json_encode($productManager->updateProduct($input['product_id'], ['bom' => $input['bom']]));
        break;
    case 'get_analytics':
        if ($auth->getCurrentUser()['role'] !== 'admin' && $auth->getCurrentUser()['role'] !== 'sales_manager') { http_response_code(403); exit; }
        echo json_encode($analyticsManager->getExecutiveSummary());
        break;
    case 'get_expanded_analytics':
        if ($auth->getCurrentUser()['role'] !== 'admin' && $auth->getCurrentUser()['role'] !== 'sales_manager') { http_response_code(403); exit; }
        echo json_encode($analyticsManager->getExpandedAnalytics());
        break;
    case 'get_enterprise_dashboard':
        if ($auth->getCurrentUser()['role'] !== 'admin' && $auth->getCurrentUser()['role'] !== 'sales_manager') { http_response_code(403); exit; }
        echo json_encode($reportManager->getEnterpriseDashboard());
        break;
    case 'get_production_logs':
        if (!$auth->hasPermission('production_log')) { http_response_code(403); exit; }
        echo json_encode($productionManager->getLogs());
        break;
    case 'get_alerts':
        echo json_encode($alertManager->getActiveAlerts());
        break;
    case 'get_announcements':
        echo json_encode($announcementManager->getActive($auth->getCurrentUser()['role']));
        break;
    case 'add_announcement':
        if ($auth->getCurrentUser()['role'] !== 'admin') { http_response_code(403); exit; }
        echo json_encode($announcementManager->create($input));
        break;
    case 'delete_announcement':
        if ($auth->getCurrentUser()['role'] !== 'admin') { http_response_code(403); exit; }
        $annStore = new \Core\JsonStore('announcements');
        echo json_encode($annStore->delete($input['id']));
        break;
    case 'get_users':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; }
        echo json_encode($userManager->getUsers());
        break;
    case 'get_user_details':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; }
        echo json_encode($userManager->getUser($_GET['id']));
        break;
    case 'add_user':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; }
        echo json_encode($userManager->createUser($input));
        break;
    case 'update_user_permissions':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; }
        echo json_encode($userManager->updateUserPermissions($input['id'], $input['permissions']));
        break;
    case 'delete_user':
        if (!$auth->hasPermission('*')) { http_response_code(403); exit; }
        echo json_encode($userManager->deleteUser($input['id']));
        break;
    case 'get_user':
        echo json_encode($auth->getCurrentUser());
        break;
    case 'get_audit':
        if ($auth->getCurrentUser()['role'] !== 'admin') { http_response_code(403); exit; }
        $auditStore = new \Core\JsonStore('audit_logs');
        echo json_encode($auditStore->findAll());
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Действие не найдено: ' . $action]);
}
