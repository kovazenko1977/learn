<?php
// api/controllers/SettingsController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class SettingsController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function get() {
        $settingsList = $this->storage->all('settings');
        if (empty($settingsList)) {
            $defaultSettings = [
                'id' => 'setting_default',
                'phone' => '+375 (29) 111-22-33',
                'address' => 'г. Минск, ул. Цветочная, д. 10',
                'working_hours' => '08:00 - 22:00 ежедневно',
                'delivery_cost' => 10.00,
                'free_delivery_threshold' => 100.00,
                'min_order_amount' => 25.00,
                'roulette_discount' => 15,
                'auto_reply_text' => 'Спасибо за ваше сообщение! Наш флорист свяжется с вами в течение 5 минут. 💐',
                'driver' => 'json',
                'mysql_config' => [
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'dbname' => 'flower_studio',
                    'user' => 'root',
                    'password' => ''
                ],
                'social_links' => [
                    'instagram' => 'https://instagram.com',
                    'telegram' => 'https://t.me',
                    'viber' => 'viber://chat'
                ],
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->storage->insert('settings', $defaultSettings);
            json_out($defaultSettings);
        } else {
            json_out($settingsList[0]);
        }
    }

    public function update() {
        require_admin();
        $data = json_in();

        $settingsList = $this->storage->all('settings');
        $settingId = !empty($settingsList) ? $settingsList[0]['id'] : 'setting_default';

        $fields = [];
        if (isset($data['phone'])) $fields['phone'] = trim($data['phone']);
        if (isset($data['address'])) $fields['address'] = trim($data['address']);
        if (isset($data['working_hours'])) $fields['working_hours'] = trim($data['working_hours']);
        if (isset($data['delivery_cost'])) $fields['delivery_cost'] = (float)$data['delivery_cost'];
        if (isset($data['free_delivery_threshold'])) $fields['free_delivery_threshold'] = (float)$data['free_delivery_threshold'];
        if (isset($data['min_order_amount'])) $fields['min_order_amount'] = (float)$data['min_order_amount'];
        if (isset($data['roulette_discount'])) $fields['roulette_discount'] = (int)$data['roulette_discount'];
        if (isset($data['auto_reply_text'])) $fields['auto_reply_text'] = trim($data['auto_reply_text']);
        if (isset($data['social_links']) && is_array($data['social_links'])) $fields['social_links'] = $data['social_links'];

        if (empty($settingsList)) {
            $fields['id'] = $settingId;
            $updated = $this->storage->insert('settings', $fields);
        } else {
            $updated = $this->storage->update('settings', $settingId, $fields);
        }

        json_out($updated);
    }

    public function switchDriver() {
        require_admin();
        $data = json_in();
        $targetDriver = trim($data['driver'] ?? 'json');

        if (!in_array($targetDriver, ['json', 'mysql'])) {
            json_out(['error' => 'Недопустимый тип драйвера хранилища'], 400);
        }

        $settingsList = $this->storage->all('settings');
        $settingId = !empty($settingsList) ? $settingsList[0]['id'] : 'setting_default';

        $updateData = ['driver' => $targetDriver];
        if (isset($data['mysql_config']) && is_array($data['mysql_config'])) {
            $updateData['mysql_config'] = $data['mysql_config'];
        }

        // Test MySQL connection if driver is set to mysql
        if ($targetDriver === 'mysql' && isset($data['mysql_config'])) {
            $mc = $data['mysql_config'];
            try {
                $dsn = "mysql:host={$mc['host']};port={$mc['port']};dbname={$mc['dbname']};charset=utf8mb4";
                new PDO($dsn, $mc['user'], $mc['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            } catch (Exception $e) {
                json_out(['error' => 'Ошибка подключения к MySQL: ' . $e->getMessage()], 400);
            }
        }

        if (empty($settingsList)) {
            $updateData['id'] = $settingId;
            $updated = $this->storage->insert('settings', $updateData);
        } else {
            $updated = $this->storage->update('settings', $settingId, $updateData);
        }

        // Reinitialize storage driver
        $this->storage->initDriver();

        json_out([
            'success' => true,
            'message' => "Драйвер базы данных переключен на {$targetDriver}",
            'current_driver' => $this->storage->getDriverType(),
            'settings' => $updated
        ]);
    }

    public function testMysql() {
        require_admin();
        $data = json_in();
        $mc = $data['mysql_config'] ?? [];

        $host = $mc['host'] ?? '127.0.0.1';
        $port = $mc['port'] ?? 3306;
        $dbname = $mc['dbname'] ?? 'flower_studio';
        $user = $mc['user'] ?? 'root';
        $password = $mc['password'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            json_out(['success' => true, 'message' => 'Соединение с MySQL успешно установлено!']);
        } catch (Exception $e) {
            json_out(['success' => false, 'error' => 'Не удалось подключиться к MySQL: ' . $e->getMessage()], 400);
        }
    }
}
