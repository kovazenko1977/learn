<?php
header('Content-Type: application/json');
require_once '../includes/AuthManager.php';
require_once '../includes/ChatManager.php';
require_once '../includes/TaskManager.php';

$auth = new AuthManager();
$chat = new ChatManager();
$tasks = new TaskManager();

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
}

echo json_encode($response);
