<?php
/**
 * Storage Layer: Universal Dual-Mode Storage Engine (JSON Files with flock / MySQL with PDO)
 */

class Storage {
    private static ?Storage $instance = null;
    private string $mode = 'json';
    private array $settings = [];
    private ?PDO $pdo = null;
    private string $dataDir;

    private function __construct() {
        $this->dataDir = __DIR__ . '/../data';
        $settingsFile = $this->dataDir . '/settings.json';
        if (file_exists($settingsFile)) {
            $this->settings = json_decode(file_get_contents($settingsFile), true) ?: [];
        }
        $this->mode = strtolower($this->settings['storage_mode'] ?? 'json');

        if ($this->mode === 'mysql') {
            $this->initMySQLPDO();
        }
    }

    public static function getInstance(): Storage {
        if (self::$instance === null) {
            self::$instance = new Storage();
        }
        return self::$instance;
    }

    public function getMode(): string {
        return $this->mode;
    }

    private function initMySQLPDO(): void {
        $cfg = $this->settings['mysql'] ?? [];
        $host = $cfg['host'] ?? 'localhost';
        $port = $cfg['port'] ?? 3306;
        $dbname = $cfg['dbname'] ?? 'crm_maintenance';
        $username = $cfg['username'] ?? 'root';
        $password = $cfg['password'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            // Fallback to JSON if MySQL connection fails
            $this->mode = 'json';
            error_log("MySQL Connection failed: " . $e->getMessage() . ". Falling back to JSON mode.");
        }
    }

    /**
     * Get records from collection/table
     */
    public function get(string $collection, array $filter = []): array {
        if ($this->mode === 'mysql' && $this->pdo) {
            return $this->getFromMySQL($collection, $filter);
        }
        return $this->getFromJSON($collection, $filter);
    }

    /**
     * Get single record by ID
     */
    public function getById(string $collection, int|string $id): ?array {
        $items = $this->get($collection, ['id' => $id]);
        return count($items) > 0 ? $items[0] : null;
    }

    /**
     * Insert new record into collection/table
     */
    public function insert(string $collection, array $data): array {
        if ($this->mode === 'mysql' && $this->pdo) {
            return $this->insertToMySQL($collection, $data);
        }
        return $this->insertToJSON($collection, $data);
    }

    /**
     * Update existing record in collection/table
     */
    public function update(string $collection, int|string $id, array $data): bool {
        if ($this->mode === 'mysql' && $this->pdo) {
            return $this->updateInMySQL($collection, $id, $data);
        }
        return $this->updateInJSON($collection, $id, $data);
    }

    /**
     * Delete record by ID
     */
    public function delete(string $collection, int|string $id): bool {
        if ($this->mode === 'mysql' && $this->pdo) {
            return $this->deleteFromMySQL($collection, $id);
        }
        return $this->deleteFromJSON($collection, $id);
    }

    // ==========================================
    // JSON STORAGE IMPLEMENTATION WITH FLOCK
    // ==========================================

    private function getFilePath(string $collection): string {
        return $this->dataDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) . '.json';
    }

    private function getFromJSON(string $collection, array $filter = []): array {
        $filePath = $this->getFilePath($collection);
        if (!file_exists($filePath)) {
            return [];
        }

        $fp = fopen($filePath, 'r');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $items = json_decode($content, true) ?: [];

        // If items is associative dictionary (like settings.json), return as is if no array_is_list
        if (!empty($items) && array_keys($items) !== range(0, count($items) - 1)) {
            return $items;
        }

        if (empty($filter)) {
            return array_values($items);
        }

        return array_values(array_filter($items, function ($item) use ($filter) {
            if (!is_array($item)) return false;
            foreach ($filter as $key => $val) {
                if (!array_key_exists($key, $item)) return false;
                if ($item[$key] != $val) return false;
            }
            return true;
        }));
    }

    private function insertToJSON(string $collection, array $data): array {
        $filePath = $this->getFilePath($collection);
        $fp = fopen($filePath, 'c+');
        if (!$fp) throw new Exception("Unable to open file for writing: $filePath");

        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $items = json_decode($content, true) ?: [];

        // Determine next auto ID
        $maxId = 0;
        foreach ($items as $item) {
            if (is_array($item) && isset($item['id']) && is_numeric($item['id']) && $item['id'] > $maxId) {
                $maxId = (int)$item['id'];
            }
        }
        if (!isset($data['id'])) {
            $data['id'] = $maxId + 1;
        }

        $items[] = $data;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $data;
    }

    private function updateInJSON(string $collection, int|string $id, array $data): bool {
        $filePath = $this->getFilePath($collection);
        if (!file_exists($filePath)) return false;

        $fp = fopen($filePath, 'c+');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $items = json_decode($content, true) ?: [];

        $updated = false;
        foreach ($items as &$item) {
            if (is_array($item) && isset($item['id']) && $item['id'] == $id) {
                $item = array_merge($item, $data);
                $item['id'] = $id; // preserve original id
                $updated = true;
                break;
            }
        }

        if ($updated) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $updated;
    }

    private function deleteFromJSON(string $collection, int|string $id): bool {
        $filePath = $this->getFilePath($collection);
        if (!file_exists($filePath)) return false;

        $fp = fopen($filePath, 'c+');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $items = json_decode($content, true) ?: [];

        $initialCount = count($items);
        $items = array_values(array_filter($items, fn($item) => is_array($item) && isset($item['id']) && $item['id'] != $id));

        $deleted = count($items) < $initialCount;

        if ($deleted) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $deleted;
    }

    // ==========================================
    // MYSQL STORAGE IMPLEMENTATION
    // ==========================================

    private function getFromMySQL(string $table, array $filter = []): array {
        $sql = "SELECT * FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "`";
        $params = [];
        if (!empty($filter)) {
            $where = [];
            foreach ($filter as $col => $val) {
                $where[] = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . "` = ?";
                $params[] = $val;
            }
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Process JSON fields automatically if present
        foreach ($rows as &$row) {
            foreach ($row as $k => $v) {
                if (is_string($v) && ($v !== '') && ($v[0] === '{' || $v[0] === '[')) {
                    $decoded = json_decode($v, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $row[$k] = $decoded;
                    }
                }
            }
        }
        return $rows;
    }

    private function insertToMySQL(string $table, array $data): array {
        $cols = [];
        $placeholders = [];
        $params = [];

        foreach ($data as $col => $val) {
            $cols[] = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . "`";
            $placeholders[] = "?";
            $params[] = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
        }

        $sql = "INSERT INTO `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "` (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if (!isset($data['id'])) {
            $data['id'] = (int)$this->pdo->lastInsertId();
        }

        return $data;
    }

    private function updateInMySQL(string $table, int|string $id, array $data): bool {
        $set = [];
        $params = [];

        foreach ($data as $col => $val) {
            if ($col === 'id') continue;
            $set[] = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . "` = ?";
            $params[] = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
        }

        if (empty($set)) return true;

        $params[] = $id;
        $sql = "UPDATE `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "` SET " . implode(", ", $set) . " WHERE `id` = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    private function deleteFromMySQL(string $table, int|string $id): bool {
        $sql = "DELETE FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "` WHERE `id` = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    // ==========================================
    // AUTOMATIC MYSQL MIGRATION & SCHEMA CREATION
    // ==========================================

    public function migrateToMySQL(array $dbConfig): array {
        try {
            $host = $dbConfig['host'] ?? 'localhost';
            $port = $dbConfig['port'] ?? 3306;
            $dbname = $dbConfig['dbname'] ?? 'crm_maintenance';
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';

            // 1. Connect without db to create db if needed
            $dsnNoDb = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdoInit = new PDO($dsnNoDb, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . preg_replace('/[^a-zA-Z0-9_]/', '', $dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 2. Connect to actual database
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // 3. Create schema
            $schemas = [
                "CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(100) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `full_name` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(255),
                    `phone` VARCHAR(50),
                    `role` VARCHAR(50) NOT NULL,
                    `department` VARCHAR(100),
                    `position` VARCHAR(100),
                    `active` TINYINT(1) DEFAULT 1,
                    `avatar` VARCHAR(255),
                    `notif_email` TINYINT(1) DEFAULT 1,
                    `notif_push` TINYINT(1) DEFAULT 1,
                    `notif_telegram` TINYINT(1) DEFAULT 0,
                    `telegram_chat_id` VARCHAR(100)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `categories` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `description` TEXT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `priorities` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(50) NOT NULL,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `color` VARCHAR(20) DEFAULT '#3B82F6',
                    `sla_hours` INT DEFAULT 24
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `form_fields` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `label` VARCHAR(255) NOT NULL,
                    `field_name` VARCHAR(100) NOT NULL,
                    `field_type` VARCHAR(50) NOT NULL,
                    `required` TINYINT(1) DEFAULT 0,
                    `options` JSON,
                    `placeholder` VARCHAR(255),
                    `order` INT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `tickets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `number` VARCHAR(50) NOT NULL UNIQUE,
                    `title` VARCHAR(255) NOT NULL,
                    `description` TEXT,
                    `category_id` INT,
                    `priority_id` INT,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'new',
                    `created_by` INT,
                    `assigned_to` INT,
                    `department` VARCHAR(100),
                    `custom_fields` JSON,
                    `attachments` JSON,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    `due_date` DATETIME,
                    `completed_at` DATETIME,
                    `archived` TINYINT(1) DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `ticket_comments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `ticket_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `user_name` VARCHAR(255) NOT NULL,
                    `role` VARCHAR(50) NOT NULL,
                    `comment` TEXT NOT NULL,
                    `attachments` JSON,
                    `is_completion_report` TINYINT(1) DEFAULT 0,
                    `created_at` DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(100) UNIQUE,
                    `setting_value` JSON
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            ];

            foreach ($schemas as $sql) {
                $this->pdo->exec($sql);
            }

            // 4. Migrate data from JSON files into MySQL tables
            $collections = ['users', 'categories', 'priorities', 'form_fields', 'tickets', 'ticket_comments'];
            foreach ($collections as $col) {
                $filePath = $this->getFilePath($col);
                if (file_exists($filePath)) {
                    $items = json_decode(file_get_contents($filePath), true) ?: [];
                    foreach ($items as $item) {
                        // Check if item already exists
                        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `" . $col . "` WHERE `id` = ?");
                        $checkStmt->execute([$item['id']]);
                        if ($checkStmt->fetchColumn() == 0) {
                            $this->insertToMySQL($col, $item);
                        }
                    }
                }
            }

            // 5. Update settings to MySQL mode
            $this->settings['storage_mode'] = 'mysql';
            $this->settings['mysql'] = $dbConfig;
            $this->mode = 'mysql';
            file_put_contents($this->getFilePath('settings'), json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return ['success' => true, 'message' => 'Успешное переключение на MySQL и миграция данных.'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
