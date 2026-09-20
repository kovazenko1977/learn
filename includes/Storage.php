<?php

require_once __DIR__ . '/TokenProvider.php';

class Storage {
    private static ?Storage $instance = null;
    private string $dataDir;
    private string $settingsFile;
    private array $settings = [];
    private ?PDO $pdo = null;

    private function __construct() {
        $this->dataDir = __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }

        // Secure data directory
        $htaccess = $this->dataDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        $this->settingsFile = $this->dataDir . '/settings.json';
        $this->loadSettings();
    }

    public static function getInstance(): Storage {
        if (self::$instance === null) {
            self::$instance = new Storage();
        }
        return self::$instance;
    }

    public function getSettings(): array {
        return $this->settings;
    }

    private function loadSettings(): void {
        $defaultSettings = [
            'storage_mode' => 'json', // json or mysql
            'mysql_host' => '127.0.0.1',
            'mysql_port' => '3306',
            'mysql_db' => 'crm_db',
            'mysql_user' => 'root',
            'mysql_pass' => '',
            'sla_low' => 72,      // hours
            'sla_medium' => 48,   // hours
            'sla_high' => 24,     // hours
            'sla_urgent' => 8,    // hours
            'categories' => ['Оборудование', 'ПО / ИТ', 'Сантехника', 'Электрика', 'Мебель', 'Общее'],
            'priorities' => ['Низкий', 'Средний', 'Высокий', 'Критический'],
            'notifications' => [
                'email' => true,
                'push' => true,
                'telegram' => false,
                'triggers' => ['created', 'assigned', 'status_changed', 'comment_added', 'sla_alert']
            ],
            'language' => 'ru'
        ];

        if (file_exists($this->settingsFile)) {
            $data = json_decode(file_get_contents($this->settingsFile), true);
            if (is_array($data)) {
                $this->settings = array_replace_recursive($defaultSettings, $data);
            } else {
                $this->settings = $defaultSettings;
            }
        } else {
            $this->settings = $defaultSettings;
            $this->saveSettings($this->settings);
        }
    }

    public function saveSettings(array $newSettings): bool {
        $this->settings = array_merge($this->settings, $newSettings);
        file_put_contents($this->settingsFile, json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (($this->settings['storage_mode'] ?? 'json') === 'mysql') {
            $this->initMySQL();
        }
        return true;
    }

    // --- JSON Storage Methods ---

    private function getJsonFilePath(string $table): string {
        return $this->dataDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . '.json';
    }

    private function readJsonData(string $table): array {
        $filePath = $this->getJsonFilePath($table);
        if (!file_exists($filePath)) {
            return [];
        }

        $fp = fopen($filePath, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function writeJsonData(string $table, array $data): bool {
        $filePath = $this->getJsonFilePath($table);
        $fp = fopen($filePath, 'c+');
        if (!$fp) return false;

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }
        fclose($fp);
        return false;
    }

    // --- Database (MySQL) Connection & Schema ---

    public function getPDO(): ?PDO {
        if ($this->settings['storage_mode'] !== 'mysql') {
            return null;
        }

        if ($this->pdo === null) {
            $host = $this->settings['mysql_host'] ?? '127.0.0.1';
            $port = $this->settings['mysql_port'] ?? '3306';
            $db   = $this->settings['mysql_db'] ?? 'crm_db';
            $user = $this->settings['mysql_user'] ?? 'root';
            $pass = $this->settings['mysql_pass'] ?? '';

            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (\Exception $e) {
                // Fallback to json if MySQL connection fails
                error_log("MySQL connection failed: " . $e->getMessage());
                return null;
            }
        }

        return $this->pdo;
    }

    public function initMySQL(): bool {
        $pdo = $this->getPDO();
        if (!$pdo) return false;

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id VARCHAR(64) PRIMARY KEY,
                    username VARCHAR(128) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255),
                    role VARCHAR(64) NOT NULL,
                    department VARCHAR(128),
                    status VARCHAR(32) DEFAULT 'active',
                    reset_token VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS requests (
                    id VARCHAR(64) PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    category VARCHAR(128) NOT NULL,
                    priority VARCHAR(64) NOT NULL,
                    status VARCHAR(64) NOT NULL DEFAULT 'new',
                    author_id VARCHAR(64) NOT NULL,
                    executor_id VARCHAR(64) NULL,
                    department VARCHAR(128) NULL,
                    custom_fields JSON NULL,
                    attachments JSON NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    due_date DATETIME NULL,
                    completed_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS comments (
                    id VARCHAR(64) PRIMARY KEY,
                    request_id VARCHAR(64) NOT NULL,
                    author_id VARCHAR(64) NOT NULL,
                    author_name VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    attachments JSON NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS form_fields (
                    id VARCHAR(64) PRIMARY KEY,
                    label VARCHAR(255) NOT NULL,
                    type VARCHAR(64) NOT NULL,
                    required TINYINT(1) DEFAULT 0,
                    options JSON NULL,
                    sort_order INT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS audit_logs (
                    id VARCHAR(64) PRIMARY KEY,
                    user_id VARCHAR(64) NOT NULL,
                    user_name VARCHAR(255) NOT NULL,
                    action VARCHAR(128) NOT NULL,
                    details TEXT NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            return true;
        } catch (\Exception $e) {
            error_log("MySQL schema setup error: " . $e->getMessage());
            return false;
        }
    }

    public function migrateJsonToMySQL(): bool {
        if (!$this->initMySQL()) {
            return false;
        }

        $tables = ['users', 'requests', 'comments', 'form_fields', 'audit_logs'];
        $pdo = $this->getPDO();

        foreach ($tables as $table) {
            $items = $this->readJsonData($table);
            if (empty($items)) continue;

            foreach ($items as $item) {
                if ($table === 'users') {
                    $stmt = $pdo->prepare("REPLACE INTO users (id, username, password_hash, full_name, email, role, department, status, reset_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $item['id'], $item['username'], $item['password_hash'], $item['full_name'], $item['email'] ?? '',
                        $item['role'], $item['department'] ?? '', $item['status'] ?? 'active', $item['reset_token'] ?? null, $item['created_at']
                    ]);
                } else if ($table === 'requests') {
                    $stmt = $pdo->prepare("REPLACE INTO requests (id, title, description, category, priority, status, author_id, executor_id, department, custom_fields, attachments, created_at, updated_at, due_date, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $item['id'], $item['title'], $item['description'] ?? '', $item['category'], $item['priority'],
                        $item['status'], $item['author_id'], $item['executor_id'] ?? null, $item['department'] ?? null,
                        json_encode($item['custom_fields'] ?? []), json_encode($item['attachments'] ?? []),
                        $item['created_at'], $item['updated_at'], $item['due_date'] ?? null, $item['completed_at'] ?? null
                    ]);
                } else if ($table === 'comments') {
                    $stmt = $pdo->prepare("REPLACE INTO comments (id, request_id, author_id, author_name, content, attachments, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $item['id'], $item['request_id'], $item['author_id'], $item['author_name'],
                        $item['content'], json_encode($item['attachments'] ?? []), $item['created_at']
                    ]);
                } else if ($table === 'form_fields') {
                    $stmt = $pdo->prepare("REPLACE INTO form_fields (id, label, type, required, options, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $item['id'], $item['label'], $item['type'], $item['required'] ? 1 : 0,
                        json_encode($item['options'] ?? []), $item['sort_order'] ?? 0
                    ]);
                } else if ($table === 'audit_logs') {
                    $stmt = $pdo->prepare("REPLACE INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $item['id'], $item['user_id'], $item['user_name'], $item['action'], $item['details'] ?? '', $item['created_at']
                    ]);
                }
            }
        }
        return true;
    }

    // --- Unified Generic CRUD Methods ---

    public function getAll(string $table): array {
        if ($this->settings['storage_mode'] === 'mysql' && ($pdo = $this->getPDO())) {
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll();
            return array_map(function($row) {
                foreach (['custom_fields', 'attachments', 'options'] as $jsonCol) {
                    if (isset($row[$jsonCol])) {
                        $row[$jsonCol] = json_decode($row[$jsonCol], true) ?? [];
                    }
                }
                return $row;
            }, $rows);
        }

        return $this->readJsonData($table);
    }

    public function getById(string $table, string $id): ?array {
        $items = $this->getAll($table);
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function save(string $table, array $record): bool {
        if (!isset($record['id'])) {
            $record['id'] = uniqid($table . '_', true);
        }

        if ($this->settings['storage_mode'] === 'mysql' && ($pdo = $this->getPDO())) {
            // For simplicity and consistency, update JSON table store or execute MySQL insert/update
            $existing = $this->getById($table, $record['id']);
            if ($existing) {
                $record = array_merge($existing, $record);
            }

            if ($table === 'users') {
                $stmt = $pdo->prepare("REPLACE INTO users (id, username, password_hash, full_name, email, role, department, status, reset_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                return $stmt->execute([
                    $record['id'], $record['username'], $record['password_hash'], $record['full_name'], $record['email'] ?? '',
                    $record['role'], $record['department'] ?? '', $record['status'] ?? 'active', $record['reset_token'] ?? null, $record['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            } else if ($table === 'requests') {
                $stmt = $pdo->prepare("REPLACE INTO requests (id, title, description, category, priority, status, author_id, executor_id, department, custom_fields, attachments, created_at, updated_at, due_date, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                return $stmt->execute([
                    $record['id'], $record['title'], $record['description'] ?? '', $record['category'], $record['priority'],
                    $record['status'], $record['author_id'], $record['executor_id'] ?? null, $record['department'] ?? null,
                    json_encode($record['custom_fields'] ?? []), json_encode($record['attachments'] ?? []),
                    $record['created_at'] ?? date('Y-m-d H:i:s'), $record['updated_at'] ?? date('Y-m-d H:i:s'), $record['due_date'] ?? null, $record['completed_at'] ?? null
                ]);
            } else if ($table === 'comments') {
                $stmt = $pdo->prepare("REPLACE INTO comments (id, request_id, author_id, author_name, content, attachments, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                return $stmt->execute([
                    $record['id'], $record['request_id'], $record['author_id'], $record['author_name'],
                    $record['content'], json_encode($record['attachments'] ?? []), $record['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            } else if ($table === 'form_fields') {
                $stmt = $pdo->prepare("REPLACE INTO form_fields (id, label, type, required, options, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                return $stmt->execute([
                    $record['id'], $record['label'], $record['type'], !empty($record['required']) ? 1 : 0,
                    json_encode($record['options'] ?? []), $record['sort_order'] ?? 0
                ]);
            } else if ($table === 'audit_logs') {
                $stmt = $pdo->prepare("REPLACE INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                return $stmt->execute([
                    $record['id'], $record['user_id'], $record['user_name'], $record['action'], $record['details'] ?? '', $record['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
        }

        // JSON Fallback
        $items = $this->readJsonData($table);
        $found = false;
        foreach ($items as $index => $item) {
            if ($item['id'] === $record['id']) {
                $items[$index] = array_merge($item, $record);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = $record;
        }

        return $this->writeJsonData($table, $items);
    }

    public function delete(string $table, string $id): bool {
        if ($this->settings['storage_mode'] === 'mysql' && ($pdo = $this->getPDO())) {
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
            return $stmt->execute([$id]);
        }

        $items = $this->readJsonData($table);
        $filtered = array_filter($items, fn($item) => $item['id'] !== $id);
        return $this->writeJsonData($table, array_values($filtered));
    }
}
