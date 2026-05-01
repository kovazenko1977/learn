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
        $settingsFile = $this->dataDir . 'config.json';
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
        $settingsFile = $this->dataDir . 'config.json';
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
            $dsn = "mysql:host={$this->dbConfig['host']};dbname={$this->dbConfig['dbname']};charset=utf8mb4";
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

        // Auto-generate tables
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(50) PRIMARY KEY,
                login VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                name VARCHAR(100),
                email VARCHAR(100),
                phone VARCHAR(20),
                department_id VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS requests (
                id VARCHAR(50) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50),
                priority VARCHAR(20),
                status VARCHAR(20),
                creator_id VARCHAR(50),
                executor_id VARCHAR(50),
                department_id VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deadline TIMESTAMP NULL,
                custom_fields JSON
            )",
            "CREATE TABLE IF NOT EXISTS comments (
                id VARCHAR(50) PRIMARY KEY,
                request_id VARCHAR(50),
                user_id VARCHAR(50),
                text TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                files JSON
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                id VARCHAR(50) PRIMARY KEY,
                value JSON
            )"
        ];

        foreach ($queries as $query) {
            $pdo->exec($query);
        }
        return true;
    }

    public function getAll($collection) {
        if ($this->mode === 'mysql') {
            $pdo = $this->getPdo();
            if ($pdo) {
                $stmt = $pdo->query("SELECT * FROM $collection");
                return $stmt->fetchAll();
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

    public function getById($collection, $id) {
        if ($this->mode === 'mysql') {
            $pdo = $this->getPdo();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM $collection WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch();
            }
        }
        $items = $this->getAll($collection);
        foreach ($items as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }

    public function save($collection, $data) {
        if ($this->mode === 'mysql') {
            return $this->saveMysql($collection, $data);
        }

        $items = $this->getAll($collection);
        $found = false;
        foreach ($items as &$item) {
            if ($item['id'] == $data['id']) {
                $item = array_merge($item, $data);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = $data;
        }

        return $this->saveAll($collection, $items);
    }

    public function saveAll($collection, $items) {
        if ($this->mode === 'mysql') {
            // This is complex for generic saveAll, usually we don't use it for MySQL in same way
            // But for simple migrations or settings it might be used
            return false;
        }

        $file = $this->dataDir . $collection . '.json';
        $fp = fopen($file, 'w');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        $result = fwrite($fp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result !== false;
    }

    private function saveMysql($collection, $data) {
        $pdo = $this->getPdo();
        if (!$pdo) return false;

        // Ensure JSON fields are encoded
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        $keys = array_keys($data);
        $cols = implode(',', $keys);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $updateParts = [];
        foreach ($keys as $key) {
            if ($key !== 'id') {
                $updateParts[] = "$key = VALUES($key)";
            }
        }
        $updateStr = implode(',', $updateParts);

        $sql = "INSERT INTO $collection ($cols) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateStr";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function delete($collection, $id) {
        if ($this->mode === 'mysql') {
            $pdo = $this->getPdo();
            if ($pdo) {
                $stmt = $pdo->prepare("DELETE FROM $collection WHERE id = ?");
                return $stmt->execute([$id]);
            }
        }

        $items = $this->getAll($collection);
        $newItems = array_filter($items, function($item) use ($id) {
            return $item['id'] != $id;
        });

        if (count($items) === count($newItems)) return false;
        return $this->saveAll($collection, array_values($newItems));
    }

    public function migrateToJson() {
        if ($this->mode !== 'mysql') return false;
        $tables = ['users', 'requests', 'comments', 'settings'];
        foreach ($tables as $table) {
            $data = $this->getAll($table);
            $this->mode = 'json'; // Temporarily switch to save to JSON
            $this->saveAll($table, $data);
            $this->mode = 'mysql';
        }
        return true;
    }

    public function migrateToMysql() {
        if ($this->mode !== 'mysql') return false;
        $tables = ['users', 'requests', 'comments', 'settings'];
        foreach ($tables as $table) {
            $this->mode = 'json';
            $data = $this->getAll($table);
            $this->mode = 'mysql';
            foreach ($data as $item) {
                $this->saveMysql($table, $item);
            }
        }
        return true;
    }
}
