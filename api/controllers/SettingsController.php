<?php
/**
 * SettingsController
 */

class SettingsController {
    public function handle($method) {
        if ($method === 'GET') {
            $isPublic = isset($_GET['public']) && $_GET['public'] === '1';
            $this->getSettings($isPublic);
        } elseif ($method === 'PUT') {
            require_auth(true);
            $this->updateSettings();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    public function actionTestMysql() {
        require_auth(true);
        $data = json_in();

        $host = clean($data['host'] ?? 'localhost');
        $name = clean($data['name'] ?? '');
        $user = clean($data['user'] ?? '');
        $pass = $data['pass'] ?? '';

        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO_ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            json_out(['success' => true, 'message' => 'Соединение с MySQL успешно установлено!']);
        } catch (Exception $e) {
            json_out(['error' => 'Ошибка подключения: ' . $e->getMessage()], 400);
        }
    }

    public function actionSwitchDriver() {
        require_auth(true);
        $data = json_in();
        $targetDriver = strtolower(clean($data['driver'] ?? 'json'));

        if (!in_array($targetDriver, ['json', 'mysql'])) {
            json_out(['error' => 'Некорректный драйвер'], 400);
        }

        $configPath = __DIR__ . '/../config.php';
        $configContent = file_get_contents($configPath);

        $newConfig = preg_replace(
            "/define\('STORAGE_DRIVER',\s*'.*?'\);/",
            "define('STORAGE_DRIVER', '{$targetDriver}');",
            $configContent
        );

        if (isset($data['mysql_host'])) {
            $newConfig = preg_replace("/define\('DB_HOST',\s*'.*?'\);/", "define('DB_HOST', '" . clean($data['mysql_host']) . "');", $newConfig);
            $newConfig = preg_replace("/define\('DB_NAME',\s*'.*?'\);/", "define('DB_NAME', '" . clean($data['mysql_name']) . "');", $newConfig);
            $newConfig = preg_replace("/define\('DB_USER',\s*'.*?'\);/", "define('DB_USER', '" . clean($data['mysql_user']) . "');", $newConfig);
            $newConfig = preg_replace("/define\('DB_PASS',\s*'.*?'\);/", "define('DB_PASS', '" . clean($data['mysql_pass']) . "');", $newConfig);
        }

        file_put_contents($configPath, $newConfig);

        json_out(['success' => true, 'driver' => strtoupper($targetDriver)]);
    }

    private function getSettings($isPublic) {
        $db = get_storage();
        $settingsList = $db->get('settings');
        $settings = array_values($settingsList)[0] ?? [];

        if ($isPublic) {
            // Remove sensitive config data if any
            unset($settings['mysql_pass']);
        }

        json_out($settings);
    }

    private function updateSettings() {
        $data = json_in();
        $db = get_storage();
        $settingsList = $db->get('settings');

        $existingId = !empty($settingsList) ? array_keys($settingsList)[0] : 1;

        $updateData = [
            'store_name' => clean($data['store_name'] ?? 'Flower Studio Pro'),
            'store_phone' => clean($data['store_phone'] ?? '+375 29 111-22-33'),
            'store_address' => clean($data['store_address'] ?? 'г. Минск, пр. Независимости, 10'),
            'working_hours' => clean($data['working_hours'] ?? '08:00 - 22:00 ежедневно'),
            'currency' => clean($data['currency'] ?? 'BYN'),
            'delivery_price' => floatval($data['delivery_price'] ?? 10),
            'free_delivery_from' => floatval($data['free_delivery_from'] ?? 100),
            'min_order' => floatval($data['min_order'] ?? 30),
            'auto_reply' => clean($data['auto_reply'] ?? 'Спасибо за сообщение! Менеджер свяжется с вами.'),
            'instagram' => clean($data['instagram'] ?? 'https://instagram.com'),
            'telegram' => clean($data['telegram'] ?? 'https://t.me'),
            'viber' => clean($data['viber'] ?? 'viber://chat')
        ];

        if (!empty($settingsList)) {
            $db->update('settings', $existingId, $updateData);
        } else {
            $db->insert('settings', $updateData);
        }

        json_out(['success' => true]);
    }
}
