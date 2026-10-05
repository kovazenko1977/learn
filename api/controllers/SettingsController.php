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

        $host = clean($data['host'] ?? $data['mysql_config']['host'] ?? 'localhost');
        $name = clean($data['name'] ?? $data['mysql_config']['name'] ?? '');
        $user = clean($data['user'] ?? $data['mysql_config']['user'] ?? '');
        $pass = $data['pass'] ?? $data['mysql_config']['pass'] ?? '';

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
        $targetDriver = strtolower(clean($data['driver'] ?? $data['db_driver'] ?? 'json'));

        if (!in_array($targetDriver, ['json', 'mysql'])) {
            json_out(['error' => 'Некорректный драйвер'], 400);
        }

        $mysqlConfig = $data['mysql_config'] ?? [];
        $host = clean($data['mysql_host'] ?? $mysqlConfig['host'] ?? '127.0.0.1');
        $name = clean($data['mysql_name'] ?? $mysqlConfig['name'] ?? 'flower_studio');
        $user = clean($data['mysql_user'] ?? $mysqlConfig['user'] ?? 'root');
        $pass = $data['mysql_pass'] ?? $mysqlConfig['pass'] ?? '';

        $configPath = __DIR__ . '/../config.php';
        $configContent = file_get_contents($configPath);

        $newConfig = preg_replace(
            "/define\('STORAGE_DRIVER',\s*'.*?'\);/",
            "define('STORAGE_DRIVER', '{$targetDriver}');",
            $configContent
        );

        $newConfig = preg_replace("/define\('DB_HOST',\s*'.*?'\);/", "define('DB_HOST', '{$host}');", $newConfig);
        $newConfig = preg_replace("/define\('DB_NAME',\s*'.*?'\);/", "define('DB_NAME', '{$name}');", $newConfig);
        $newConfig = preg_replace("/define\('DB_USER',\s*'.*?'\);/", "define('DB_USER', '{$user}');", $newConfig);
        $newConfig = preg_replace("/define\('DB_PASS',\s*'.*?'\);/", "define('DB_PASS', '{$pass}');", $newConfig);

        file_put_contents($configPath, $newConfig);

        // Also update settings.json
        $db = get_storage();
        $settingsList = $db->get('settings');
        $existingId = !empty($settingsList) ? array_keys($settingsList)[0] : 1;
        $currentSettings = array_values($settingsList)[0] ?? [];

        $currentSettings['db_driver'] = $targetDriver;
        $currentSettings['mysql_host'] = $host;
        $currentSettings['mysql_name'] = $name;
        $currentSettings['mysql_user'] = $user;
        $currentSettings['mysql_pass'] = $pass;

        if (!empty($settingsList)) {
            $db->update('settings', $existingId, $currentSettings);
        } else {
            $db->insert('settings', $currentSettings);
        }

        json_out(['success' => true, 'message' => 'Драйвер успешно переключён на ' . strtoupper($targetDriver), 'driver' => strtoupper($targetDriver)]);
    }

    private function getSettings($isPublic) {
        $db = get_storage();
        $settingsList = $db->get('settings');
        $s = array_values($settingsList)[0] ?? [];

        if ($isPublic) {
            unset($s['mysql_pass']);
        }

        $normalized = array_merge($s, [
            'store_name' => $s['store_name'] ?? 'Flower Studio Pro',
            'store_phone' => $s['store_phone'] ?? '+375 29 111-22-33',
            'store_address' => $s['store_address'] ?? 'г. Минск, пр. Независимости, 10',
            'work_hours' => $s['work_hours'] ?? $s['working_hours'] ?? '08:00 - 22:00 ежедневно',
            'working_hours' => $s['working_hours'] ?? $s['work_hours'] ?? '08:00 - 22:00 ежедневно',
            'currency' => $s['currency'] ?? 'BYN',
            'delivery_fee' => floatval($s['delivery_fee'] ?? $s['delivery_price'] ?? 10),
            'delivery_price' => floatval($s['delivery_price'] ?? $s['delivery_fee'] ?? 10),
            'free_delivery_from' => floatval($s['free_delivery_from'] ?? 100),
            'min_order_amount' => floatval($s['min_order_amount'] ?? $s['min_order'] ?? 30),
            'min_order' => floatval($s['min_order'] ?? $s['min_order_amount'] ?? 30),
            'auto_reply_text' => $s['auto_reply_text'] ?? $s['auto_reply'] ?? 'Спасибо за обращение! Менеджер ответит вам в ближайшее время.',
            'auto_reply' => $s['auto_reply'] ?? $s['auto_reply_text'] ?? 'Спасибо за обращение! Менеджер ответит вам в ближайшее время.',
            'surprise_discount' => intval($s['surprise_discount'] ?? 10),
            'instagram' => $s['instagram'] ?? 'https://instagram.com',
            'telegram' => $s['telegram'] ?? 'https://t.me',
            'viber' => $s['viber'] ?? 'viber://chat'
        ]);

        json_out(['settings' => $normalized]);
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
            'work_hours' => clean($data['work_hours'] ?? $data['working_hours'] ?? '08:00 - 22:00 ежедневно'),
            'working_hours' => clean($data['working_hours'] ?? $data['work_hours'] ?? '08:00 - 22:00 ежедневно'),
            'currency' => clean($data['currency'] ?? 'BYN'),
            'delivery_fee' => floatval($data['delivery_fee'] ?? $data['delivery_price'] ?? 10),
            'delivery_price' => floatval($data['delivery_price'] ?? $data['delivery_fee'] ?? 10),
            'free_delivery_from' => floatval($data['free_delivery_from'] ?? 100),
            'min_order_amount' => floatval($data['min_order_amount'] ?? $data['min_order'] ?? 30),
            'min_order' => floatval($data['min_order'] ?? $data['min_order_amount'] ?? 30),
            'auto_reply_text' => clean($data['auto_reply_text'] ?? $data['auto_reply'] ?? 'Спасибо за сообщение! Менеджер свяжется с вами.'),
            'auto_reply' => clean($data['auto_reply'] ?? $data['auto_reply_text'] ?? 'Спасибо за сообщение! Менеджер свяжется с вами.'),
            'surprise_discount' => intval($data['surprise_discount'] ?? 10),
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
