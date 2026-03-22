<?php
require_once 'Storage.php';

class AdminManager {
    private $storage;

    public function __construct() {
        $this->storage = new Storage();
    }

    public function isAdmin($user_id) {
        $users = $this->storage->read('users');
        foreach ($users as $user) {
            if ($user['id'] === $user_id && isset($user['role']) && $user['role'] === 'admin') {
                return true;
            }
        }
        return false;
    }

    public function deleteUser($user_id) {
        $users = $this->storage->read('users');
        $username_to_delete = null;
        foreach ($users as $username => $user) {
            if ($user['id'] === $user_id) {
                $username_to_delete = $username;
                break;
            }
        }
        if ($username_to_delete) {
            unset($users[$username_to_delete]);
            return $this->storage->write('users', $users);
        }
        return false;
    }

    public function deleteMessage($message_id) {
        $chats = $this->storage->read('chats');
        foreach ($chats as &$messages) {
            foreach ($messages as $index => $msg) {
                if ($msg['id'] === $message_id) {
                    unset($messages[$index]);
                    $messages = array_values($messages);
                    return $this->storage->write('chats', $chats);
                }
            }
        }
        return false;
    }

    public function deleteTask($list_id, $task_id) {
        $tasks = $this->storage->read('tasks');
        if (isset($tasks[$list_id])) {
            foreach ($tasks[$list_id]['tasks'] as $index => $task) {
                if ($task['id'] === $task_id) {
                    unset($tasks[$list_id]['tasks'][$index]);
                    $tasks[$list_id]['tasks'] = array_values($tasks[$list_id]['tasks']);
                    return $this->storage->write('tasks', $tasks);
                }
            }
        }
        return false;
    }

    public function deleteTaskList($list_id) {
        $tasks = $this->storage->read('tasks');
        if (isset($tasks[$list_id])) {
            unset($tasks[$list_id]);
            return $this->storage->write('tasks', $tasks);
        }
        return false;
    }

    public function getAllDataSummary() {
        return [
            'users' => count($this->storage->read('users')),
            'tasks' => count($this->storage->read('tasks')),
            'events' => count($this->storage->read('events')),
            'shopping' => count($this->storage->read('shopping'))
        ];
    }

    public function getAllMessages() {
        $chats = $this->storage->read('chats');
        $all = [];
        foreach ($chats as $key => $messages) {
            foreach ($messages as $msg) {
                $msg['chat_key'] = $key;
                $all[] = $msg;
            }
        }
        usort($all, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        return $all;
    }
}
