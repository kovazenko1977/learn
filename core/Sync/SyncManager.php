<?php
namespace Sanatorium\Core\Sync;

use Sanatorium\Core\Database\JsonStore;

class SyncManager {
    private $store;
    private $settings;

    public function __construct(JsonStore $store) {
        $this->store = $store;
        $settingsPath = __DIR__ . '/../../data/settings.json';
        $this->settings = json_decode(@file_get_contents($settingsPath), true) ?: [];
    }

    public function getSyncConfig() {
        return $this->settings['sync'] ?? [
            'remote_url' => '',
            'api_key' => '',
            'last_sync' => null,
            'is_local' => true
        ];
    }

    public function saveSyncConfig($config) {
        $this->settings['sync'] = $config;
        $settingsPath = __DIR__ . '/../../data/settings.json';
        return file_put_contents($settingsPath, json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function pushData() {
        $config = $this->getSyncConfig();
        if (empty($config['remote_url']) || empty($config['api_key'])) {
            return ['success' => false, 'message' => 'Настройки синхронизации не заданы'];
        }

        $collections = ['bookings', 'guests', 'rooms', 'room_classes', 'procedures', 'extra_services', 'packages', 'plans', 'expenses', 'inventory', 'tasks'];
        $payload = [];
        foreach ($collections as $col) {
            $payload[$col] = $this->store->findAll($col);
        }

        $url = rtrim($config['remote_url'], '/') . '/api/sync.php?action=push';
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n" . "X-API-Key: " . $config['api_key'] . "\r\n",
                'method'  => 'POST',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            return ['success' => false, 'message' => 'Не удалось связаться с сервером'];
        }

        $response = json_decode($result, true);
        if ($response && $response['success']) {
            $config['last_sync'] = date('Y-m-d H:i:s');
            $this->saveSyncConfig($config);
        }

        return $response ?: ['success' => false, 'message' => 'Некорректный ответ сервера: ' . $result];
    }

    public function pullData() {
        $config = $this->getSyncConfig();
        if (empty($config['remote_url']) || empty($config['api_key'])) {
            return ['success' => false, 'message' => 'Настройки синхронизации не заданы'];
        }

        $url = rtrim($config['remote_url'], '/') . '/api/sync.php?action=pull';
        $options = [
            'http' => [
                'header'  => "X-API-Key: " . $config['api_key'] . "\r\n",
                'method'  => 'GET',
                'ignore_errors' => true
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            return ['success' => false, 'message' => 'Не удалось связаться с сервером'];
        }

        $data = json_decode($result, true);
        if (!$data || !isset($data['success']) || !$data['success']) {
            return ['success' => false, 'message' => $data['message'] ?? 'Ошибка получения данных'];
        }

        $collections = $data['payload'] ?? [];
        foreach ($collections as $col => $items) {
            if (is_array($items)) {
                // Simplified merge: remote overwrites local for now.
                // In production, we'd compare updated_at timestamps.
                file_put_contents(__DIR__ . '/../../data/' . $col . '.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $config['last_sync'] = date('Y-m-d H:i:s');
        $this->saveSyncConfig($config);

        return ['success' => true, 'message' => 'Данные успешно загружены и обновлены'];
    }
}
