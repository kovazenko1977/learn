<?php
header('Content-Type: application/json');
require_once '../includes/AuthManager.php';
require_once '../includes/ChatManager.php';
require_once '../includes/TaskManager.php';
require_once '../includes/ShoppingManager.php';
require_once '../includes/AchievementManager.php';
require_once '../includes/EventManager.php';
require_once '../includes/AdminManager.php';

$auth = new AuthManager();
$chat = new ChatManager();
$tasks = new TaskManager();
$shopping = new ShoppingManager();
$achievements = new AchievementManager();
$events = new EventManager();
$admin = new AdminManager();

$user = $auth->getCurrentUser();
$action = isset($_GET['action']) ? $_GET['action'] : '';

$response = ['success' => false, 'message' => 'Неизвестное действие'];

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $response = $auth->register($data['username'], $data['password']);
        break;

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $response = $auth->login($data['username'], $data['password']);
        break;

    case 'logout':
        $response = $auth->logout();
        break;

    case 'get_current_user':
        $response = ['success' => true, 'user' => $user];
        break;

    case 'list_users':
        if ($user) {
            $response = ['success' => true, 'users' => $auth->listUsers()];
        }
        break;

    case 'send_message':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->sendMessage($user['id'], $data['to_id'], $data['message'], isset($data['image']) ? $data['image'] : null);
            $response = ['success' => $success];
        }
        break;

    case 'get_chat_history':
        if ($user) {
            $history = $chat->getHistory($user['id'], $_GET['with_id']);
            $response = ['success' => true, 'history' => $history];
        }
        break;

    case 'get_task_lists':
        if ($user) {
            $response = ['success' => true, 'lists' => $tasks->getMyLists($user['id'])];
        }
        break;

    case 'create_task_list':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $list = $tasks->createList($data['title'], $user['id'], $data['participant_ids']);
            $response = ['success' => (bool)$list, 'list' => $list];
        }
        break;

    case 'add_task':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $tasks->addTask($data['list_id'], $data['text'], $user['id']);
            $response = ['success' => $success];
        }
        break;

    case 'edit_task':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $tasks->editTask($data['list_id'], $data['task_id'], $data['text'], $user['id']);
            $response = ['success' => $success];
        }
        break;

    case 'toggle_task':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $tasks->toggleTask($data['list_id'], $data['task_id'], $user['id']);
            $response = ['success' => $success];
        }
        break;

    case 'delete_task':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $tasks->deleteTask($data['list_id'], $data['task_id'], $user['id']);
            $response = ['success' => $success];
        }
        break;

    case 'get_shopping_list':
        if ($user) {
            $response = ['success' => true, 'items' => $shopping->getList($user['id'])];
        }
        break;

    case 'add_shopping_item':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $shopping->addItem($data['text'], $user['id']);
            $response = ['success' => $success];
        }
        break;

    case 'toggle_shopping_item':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $shopping->toggleItem($data['id']);
            $response = ['success' => $success];
        }
        break;

    case 'delete_shopping_item':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $is_admin = (isset($user['role']) && $user['role'] === 'admin');
            $success = $shopping->deleteItem($data['id'], $user['id'], $is_admin);
            $response = ['success' => $success];
        }
        break;

    case 'clear_shopping_list':
        if ($user) {
            $success = $shopping->clearCompleted();
            $response = ['success' => $success];
        }
        break;

    case 'update_status':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $auth->updateStatus($user['id'], $data['status']);
            $response = ['success' => $success];
        }
        break;

    case 'get_achievements':
        if ($user) {
            $response = [
                'success' => true,
                'my_achievements' => $achievements->getAchievements($user['id']),
                'metadata' => AchievementManager::getMetadata()
            ];
        }
        break;

    case 'get_events':
        if ($user) {
            $response = ['success' => true, 'events' => $events->getEvents()];
        }
        break;

    case 'add_event':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $events->addEvent($data['title'], $data['date'], $data['description'], $user['id']);
            $response = ['success' => $success];
        }
        break;

    case 'delete_event':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $events->deleteEvent($data['id']);
            $response = ['success' => $success];
        }
        break;

    // Admin Actions
    case 'admin_get_summary':
        if ($user && $admin->isAdmin($user['id'])) {
            $response = [
                'success' => true,
                'summary' => $admin->getAllDataSummary(),
                'recent_messages' => array_slice($admin->getAllMessages(), 0, 50)
            ];
        }
        break;

    case 'admin_delete_user':
        if ($user && $admin->isAdmin($user['id'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $admin->deleteUser($data['id']);
            $response = ['success' => $success];
        }
        break;

    case 'admin_delete_message':
        if ($user && $admin->isAdmin($user['id'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $admin->deleteMessage($data['id']);
            $response = ['success' => $success];
        }
        break;

    case 'admin_delete_task':
        if ($user && $admin->isAdmin($user['id'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $admin->deleteTask($data['list_id'], $data['task_id']);
            $response = ['success' => $success];
        }
        break;
}

echo json_encode($response);
