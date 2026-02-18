<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;
use Medical\Core\Auth;

class LogManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('activity_log');
    }

    public function log($action, $details = '') {
        $user = Auth::getUser();
        $entry = [
            'id' => uniqid(),
            'timestamp' => date('d-m-Y H:i:s'),
            'user' => $user['name'] ?? 'Система',
            'role' => $user['role'] ?? 'system',
            'action' => $action,
            'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details
        ];
        $this->store->add($entry);
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getRecent($limit = 100) {
        $all = $this->store->getAll();
        return array_slice(array_reverse($all), 0, $limit);
    }
}
