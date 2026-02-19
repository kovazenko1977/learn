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
            'user_id' => $user['id'] ?? 'system',
            'user_name' => $user['name'] ?? 'Система',
            'role' => $user['role'] ?? 'system',
            'action' => $action,
            'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        // Add ID only for JSON store to keep it consistent
        $settingsStore = new JsonStore('settings');
        $settings = $settingsStore->getAll();
        if (($settings['db_driver'] ?? 'json') !== 'mysql') {
            $entry['id'] = uniqid();
        }
        try {
            $this->store->add($entry);
        } catch (\Throwable $e) {
            // Silently fail logging to prevent 500 errors on critical paths like login
        }
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getRecent($limit = 100) {
        $all = $this->store->getAll();
        return array_slice(array_reverse($all), 0, $limit);
    }
}
