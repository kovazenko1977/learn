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
        $settingsStore = new JsonStore('settings');
        $settings = $settingsStore->getAll();

        // Check if logging is enabled (default is true for safety)
        if (isset($settings['is_logging_enabled']) && !$settings['is_logging_enabled']) {
            return;
        }

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
        if (($settings['db_driver'] ?? 'json') !== 'mysql') {
            $entry['id'] = uniqid();
        }
        try {
            $this->store->add($entry);
        } catch (\Throwable $e) {
            // Silently fail logging to prevent 500 errors on critical paths like login
        }
    }

    public function getAll($limit = 100) {
        $logs = $this->store->getAll();
        usort($logs, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        return array_slice($logs, 0, $limit);
    }

    public function clear() {
        return $this->store->save([]);
    }
}
