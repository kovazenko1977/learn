<?php
namespace Sanatorium\Core\Tasks;
use Sanatorium\Core\Database\JsonStore;

class TaskManager {
    private $store;
    public function __construct(JsonStore $store) { $this->store = $store; }
    public function addTask($text, $assignee) {
        $tasks = $this->store->findAll('tasks');
        $tasks[] = ['id' => time(), 'text' => $text, 'assignee' => $assignee, 'status' => 'pending', 'created_at' => date('Y-m-d')];
        $this->store->save('tasks', $tasks);
    }
    public function getTasks() { return $this->store->findAll('tasks'); }
}
