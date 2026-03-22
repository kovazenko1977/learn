<?php
require_once 'Storage.php';

class TaskManager {
    private $storage;
    private $filename = 'tasks';

    public function __construct() {
        $this->storage = new Storage();
    }

    public function createList($title, $creator_id, $participant_ids) {
        $taskLists = $this->storage->read($this->filename);
        $id = $this->storage->generateId();

        $taskLists[$id] = [
            'id' => $id,
            'title' => $title,
            'creator_id' => $creator_id,
            'participants' => array_unique(array_merge([$creator_id], $participant_ids)),
            'tasks' => []
        ];

        if ($this->storage->write($this->filename, $taskLists)) {
            return $taskLists[$id];
        }
        return false;
    }

    public function addTask($list_id, $text, $user_id) {
        $taskLists = $this->storage->read($this->filename);
        if (!$this->canModify($taskLists, $list_id, $user_id)) return false;

        $task_id = $this->storage->generateId();
        $taskLists[$list_id]['tasks'][] = [
            'id' => $task_id,
            'text' => $text,
            'author_id' => $user_id,
            'completed' => false,
            'created_at' => time()
        ];

        return $this->storage->write($this->filename, $taskLists);
    }

    public function toggleTask($list_id, $task_id, $user_id) {
        $taskLists = $this->storage->read($this->filename);
        if (!$this->canModify($taskLists, $list_id, $user_id)) return false;

        foreach ($taskLists[$list_id]['tasks'] as &$task) {
            if ($task['id'] === $task_id) {
                $task['completed'] = !$task['completed'];
                break;
            }
        }

        return $this->storage->write($this->filename, $taskLists);
    }

    public function editTask($list_id, $task_id, $text, $user_id) {
        $taskLists = $this->storage->read($this->filename);
        if (!isset($taskLists[$list_id])) return false;

        foreach ($taskLists[$list_id]['tasks'] as &$task) {
            if ($task['id'] === $task_id) {
                if ($task['author_id'] !== $user_id) return false;
                $task['text'] = $text;
                break;
            }
        }

        return $this->storage->write($this->filename, $taskLists);
    }

    public function deleteTask($list_id, $task_id, $user_id) {
        $taskLists = $this->storage->read($this->filename);
        if (!isset($taskLists[$list_id])) return false;

        $taskLists[$list_id]['tasks'] = array_filter($taskLists[$list_id]['tasks'], function($task) use ($task_id, $user_id) {
            return !($task['id'] === $task_id && $task['author_id'] === $user_id);
        });

        return $this->storage->write($this->filename, $taskLists);
    }

    public function getMyLists($user_id) {
        $taskLists = $this->storage->read($this->filename);
        $myLists = [];
        foreach ($taskLists as $list) {
            if (in_array($user_id, $list['participants'])) {
                $myLists[] = $list;
            }
        }
        return $myLists;
    }

    private function canModify($taskLists, $list_id, $user_id) {
        return isset($taskLists[$list_id]) && in_array($user_id, $taskLists[$list_id]['participants']);
    }
}
