<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;
use Medical\Core\Auth;

class LogManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('activity_log');
    }

    public function log($action, $details = []) {
        $user = Auth::getUser();
        $entry = [
            'id' => uniqid(),
            'timestamp' => date('d-m-Y H:i:s'),
            'user_id' => $user['id'] ?? 'system',
            'user_name' => $user['name'] ?? 'Система',
            'role' => $user['role'] ?? 'unknown',
            'action' => $action,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        $this->store->add($entry);
    }

    public function getAll($limit = 100) {
        $logs = $this->store->getAll();
        return array_slice(array_reverse($logs), 0, $limit);
    }
}
