<?php

class Storage {
    private $dataDir;
    private $dbConfig = null;
    private $mode = 'json'; // 'json' or 'mysql'
    private $pdo = null;

    public function __construct($dataDir = __DIR__ . '/../data/') {
        $this->dataDir = rtrim($dataDir, '/') . '/';
        $this->loadSettings();
    }

    private function loadSettings() {
        $settingsFile = $this->dataDir . 'settings.json';
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true);
            if (isset($settings['storage_mode'])) {
                $this->mode = $settings['storage_mode'];
            }
            if (isset($settings['db_config'])) {
                $this->dbConfig = $settings['db_config'];
            }
        }
    }

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode, $dbConfig = null) {
        $this->mode = $mode;
        if ($dbConfig) {
            $this->dbConfig = $dbConfig;
        }
        $settingsFile = $this->dataDir . 'settings.json';
        $settings = [];
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true);
        }
        $settings['storage_mode'] = $this->mode;
        if ($this->dbConfig) {
            $settings['db_config'] = $this->dbConfig;
        }
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

        if ($this->mode === 'mysql') {
            $this->initMysql();
        }
    }

    private function getPdo() {
        if ($this->pdo !== null) return $this->pdo;
        if (!$this->dbConfig) return null;

        try {
            $dsn = "mysql:host={$this->dbConfig['host']};dbname={$this->dbConfig['name']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $this->pdo;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function initMysql() {
        $pdo = $this->getPdo();
        if (!$pdo) return false;

        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(50) PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                name VARCHAR(100),
                department VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS tasks (
                id VARCHAR(50) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50),
                priority VARCHAR(20),
                status VARCHAR(20),
                creator_id VARCHAR(50),
                creator_name VARCHAR(100),
                executor_id VARCHAR(50),
                executor_name VARCHAR(100),
                department VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deadline TIMESTAMP NULL,
                custom_fields JSON,
                comments JSON,
                attachments JSON,
                history JSON
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                id VARCHAR(50) PRIMARY KEY,
                data JSON
            )"
        ];

        foreach ($queries as $query) {
            $pdo->exec($query);
        }
        return true;
    }

    public static function read($collection) {
        $instance = new self();
        return $instance->getCollection($collection);
    }

    public static function write($collection, $data) {
        $instance = new self();
        return $instance->saveCollection($collection, $data);
    }

    public static function generateId() {
        return bin2hex(random_bytes(8));
    }

    public function getCollection($collection) {
        if ($this->mode === 'mysql') {
            $pdo = $this->getPdo();
            if ($pdo) {
                if ($collection === 'settings') {
                    $stmt = $pdo->query("SELECT data FROM settings WHERE id = 'main'");
                    $row = $stmt->fetch();
                    return $row ? json_decode($row['data'], true) : [];
                }

                $allowed = ['users', 'tasks'];
                if (!in_array($collection, $allowed)) return [];

                $stmt = $pdo->query("SELECT * FROM $collection");
                $results = $stmt->fetchAll();
                foreach ($results as &$row) {
                    foreach (['custom_fields', 'comments', 'attachments', 'history'] as $field) {
                        if (isset($row[$field])) $row[$field] = json_decode($row[$field], true);
                    }
                }
                return $results;
            }
        }

        $file = $this->dataDir . $collection . '.json';
        if (!file_exists($file)) return [];

        $fp = fopen($file, 'r');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function saveCollection($collection, $data) {
        if ($this->mode === 'mysql') {
            $pdo = $this->getPdo();
            if (!$pdo) return false;

            if ($collection === 'settings') {
                $stmt = $pdo->prepare("INSERT INTO settings (id, data) VALUES ('main', ?) ON DUPLICATE KEY UPDATE data = VALUES(data)");
                return $stmt->execute([json_encode($data, JSON_UNESCAPED_UNICODE)]);
            }

            $allowed = ['users', 'tasks'];
            if (!in_array($collection, $allowed)) return false;

            foreach ($data as $item) {
                $this->saveItemMysql($collection, $item);
            }
            return true;
        }

        $file = $this->dataDir . $collection . '.json';
        $temp = tempnam(dirname($file), 'tmp_');
        if (file_put_contents($temp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false) {
            chmod($temp, 0666);
            if (rename($temp, $file)) {
                return true;
            }
        }
        @unlink($temp);
        return false;
    }

    private function saveItemMysql($table, $data) {
        $pdo = $this->getPdo();
        if (!$pdo) return false;

        $allowedTables = ['users', 'tasks'];
        if (!in_array($table, $allowedTables)) return false;

        $tableColumns = [
            'users' => ['id', 'username', 'password', 'role', 'name', 'department', 'created_at'],
            'tasks' => ['id', 'title', 'description', 'category', 'priority', 'status', 'creator_id', 'creator_name', 'executor_id', 'executor_name', 'department', 'created_at', 'deadline', 'custom_fields', 'comments', 'attachments', 'history']
        ];

        $columns = [];
        $placeholders = [];
        $values = [];
        $updates = [];

        foreach ($tableColumns[$table] as $col) {
            if (isset($data[$col])) {
                $columns[] = $col;
                $placeholders[] = "?";
                $val = $data[$col];
                if (is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                $values[] = $val;
                if ($col !== 'id') {
                    $updates[] = "$col = VALUES($col)";
                }
            }
        }

        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ") ON DUPLICATE KEY UPDATE " . implode(',', $updates);
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }
}
