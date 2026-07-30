<?php

class Storage {
    private $mode;
    private $dbConfig;
    private $pdo;
    private $dataDir = __DIR__ . '/../data/';

    public function __construct() {
        $settings = $this->getSettings();
        $this->mode = $settings['storage_mode'] ?? 'json';
        $this->dbConfig = $settings['mysql'] ?? [];

        if ($this->mode === 'mysql' && !empty($this->dbConfig)) {
            $this->connectMySQL();
        }
    }

    private function getSettings() {
        $path = $this->dataDir . 'settings.json';
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return [];
    }

    private function connectMySQL() {
        try {
            $dsn = "mysql:host={$this->dbConfig['host']};dbname={$this->dbConfig['dbname']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->ensureTables();
        } catch (PDOException $e) {
            $this->mode = 'json'; // Fallback to JSON if MySQL fails
        }
    }

    private function ensureTables() {
        // Simple schema auto-generation for MySQL
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY,
                username VARCHAR(50) UNIQUE,
                password VARCHAR(255),
                full_name VARCHAR(100),
                role VARCHAR(20),
                department VARCHAR(50),
                email VARCHAR(100),
                telegram_id VARCHAR(50),
                last_seen INT DEFAULT 0,
                banned TINYINT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS tasks (
                id VARCHAR(36) PRIMARY KEY,
                title VARCHAR(255),
                description TEXT,
                category VARCHAR(50),
                priority VARCHAR(20),
                status VARCHAR(20),
                creator_id VARCHAR(36),
                executor_id VARCHAR(36),
                deadline DATETIME,
                completed_at DATETIME,
                attachments JSON,
                custom_fields JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS comments (
                id VARCHAR(36) PRIMARY KEY,
                task_id VARCHAR(36),
                user_id VARCHAR(36),
                content TEXT,
                attachments JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS recovery (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36),
                expires DATETIME
            )",
            "CREATE TABLE IF NOT EXISTS chat (
                id VARCHAR(36) PRIMARY KEY,
                sender_id VARCHAR(36),
                recipient_id VARCHAR(36) NULL,
                message TEXT,
                attachment_url VARCHAR(255) NULL,
                attachment_name VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        foreach ($queries as $q) {
            $this->pdo->exec($q);
        }
    }

    public function getAll($collection) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->query("SELECT * FROM $collection");
                $results = $stmt->fetchAll();
                return array_map(function($row) {
                    if (isset($row['custom_fields'])) $row['custom_fields'] = json_decode($row['custom_fields'], true);
                    if (isset($row['attachments'])) $row['attachments'] = json_decode($row['attachments'], true);
                    return $row;
                }, $results);
            } catch (PDOException $e) {
                return [];
            }
        } else {
            $path = $this->dataDir . $collection . '.json';
            return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        }
    }

    public function getById($collection, $id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT * FROM $collection WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                if (isset($row['custom_fields'])) $row['custom_fields'] = json_decode($row['custom_fields'], true);
                if (isset($row['attachments'])) $row['attachments'] = json_decode($row['attachments'], true);
            }
            return $row;
        } else {
            $items = $this->getAll($collection);
            foreach ($items as $item) {
                if ($item['id'] === $id) return $item;
            }
            return null;
        }
    }

    public function save($collection, $data) {
        if (!isset($data['id'])) $data['id'] = bin2hex(random_bytes(16));

        if ($this->mode === 'mysql') {
            $fields = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $columns = implode(',', $fields);
            $update = implode(',', array_map(fn($f) => "$f = VALUES($f)", $fields));

            $values = array_values(array_map(function($val) {
                return is_array($val) ? json_encode($val) : $val;
            }, $data));

            $sql = "INSERT INTO $collection ($columns) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $update";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
        } else {
            $items = $this->getAll($collection);
            $found = false;
            foreach ($items as &$item) {
                if ($item['id'] === $data['id']) {
                    $item = array_merge($item, $data);
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] = $data;
            file_put_contents($this->dataDir . $collection . '.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return $data['id'];
    }

    public function delete($collection, $id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("DELETE FROM $collection WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $items = $this->getAll($collection);
            $items = array_filter($items, fn($item) => $item['id'] !== $id);
            file_put_contents($this->dataDir . $collection . '.json', json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
