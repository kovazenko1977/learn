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

    case 'list_groups':
        if ($user) {
            $response = ['success' => true, 'groups' => $chat->listMyGroups($user['id'])];
        }
        break;

    case 'create_group':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $group = $chat->createGroup($data['title'], $user['id'], $data['participant_ids']);
            $response = ['success' => (bool)$group, 'group' => $group];
        }
        break;

    case 'send_message':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->sendMessage(
                $user['id'],
                $data['to_id'],
                $data['message'],
                isset($data['image']) ? $data['image'] : null,
                isset($data['reply_to']) ? $data['reply_to'] : null,
                isset($data['file']) ? $data['file'] : null,
                isset($data['location']) ? $data['location'] : null,
                isset($data['voice']) ? $data['voice'] : null
            );
            $response = ['success' => $success];
        }
        break;

    case 'toggle_star':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->toggleStar($user['id'], $data['id']);
            $response = ['success' => $success];
        }
        break;

    case 'toggle_reaction':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->toggleReaction($user['id'], $data['id'], $data['reaction']);
            $response = ['success' => $success];
        }
        break;

    case 'toggle_pin':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->togglePin($data['id']);
            $response = ['success' => $success];
        }
        break;

    case 'edit_message':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->editMessage($user['id'], $data['id'], $data['message']);
            $response = ['success' => $success];
        }
        break;

    case 'delete_message_everyone':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $chat->deleteMessageForEveryone($user['id'], $data['id']);
            $response = ['success' => $success];
        }
        break;

    case 'update_profile':
        if ($user) {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $auth->updateProfile($user['id'], $data);
            $response = ['success' => $success];
        }
        break;

    case 'get_chat_history':
        if ($user) {
            $target_id = isset($_GET['with_id']) ? $_GET['with_id'] : $_GET['group_id'];
            $history = $chat->getHistory($user['id'], $target_id);
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
