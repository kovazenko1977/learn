<?php

class Storage {
    private $mode;
    private $dbConfig;
    private $pdo;
    private $dataDir = __DIR__ . '/../data/';

    private $allowedFields = [
        'users' => ['id', 'username', 'password', 'full_name', 'role', 'department', 'email', 'telegram_id', 'created_at'],
        'tasks' => ['id', 'title', 'description', 'category', 'priority', 'status', 'creator_id', 'executor_id', 'department', 'deadline', 'completed_at', 'attachments', 'custom_fields', 'created_at', 'updated_at'],
        'comments' => ['id', 'task_id', 'user_id', 'content', 'attachments', 'created_at'],
        'recovery' => ['id', 'user_id', 'expires']
    ];

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
                department VARCHAR(50),
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
            )"
        ];
        foreach ($queries as $q) {
            $this->pdo->exec($q);
        }

        // Seamless migration: seed from JSON files if database table is empty
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            if ($stmt->fetchColumn() == 0) {
                $path = $this->dataDir . 'users.json';
                if (file_exists($path)) {
                    $items = json_decode(file_get_contents($path), true) ?: [];
                    foreach ($items as $item) {
                        $this->saveMySQLDirectly('users', $item);
                    }
                }
            }

            $stmt = $this->pdo->query("SELECT COUNT(*) FROM tasks");
            if ($stmt->fetchColumn() == 0) {
                $path = $this->dataDir . 'tasks.json';
                if (file_exists($path)) {
                    $items = json_decode(file_get_contents($path), true) ?: [];
                    foreach ($items as $item) {
                        $this->saveMySQLDirectly('tasks', $item);
                    }
                }
            }

            $stmt = $this->pdo->query("SELECT COUNT(*) FROM comments");
            if ($stmt->fetchColumn() == 0) {
                $path = $this->dataDir . 'comments.json';
                if (file_exists($path)) {
                    $items = json_decode(file_get_contents($path), true) ?: [];
                    foreach ($items as $item) {
                        $this->saveMySQLDirectly('comments', $item);
                    }
                }
            }
        } catch (PDOException $e) {
            // Ignore migration error if any database query fails during migration
        }
    }

    private function validateCollection($collection) {
        $allowed = ['users', 'tasks', 'comments', 'recovery'];
        if (!in_array($collection, $allowed)) {
            throw new InvalidArgumentException("Invalid collection: " . $collection);
        }
    }

    private function saveMySQLDirectly($collection, $data) {
        if (!isset($data['id'])) $data['id'] = bin2hex(random_bytes(16));

        // strict column whitelist validation to neutralize SQL injection entirely
        $allowed = $this->allowedFields[$collection] ?? [];
        $filteredData = [];
        foreach ($data as $key => $val) {
            if (in_array($key, $allowed)) {
                $filteredData[$key] = $val;
            }
        }
        $data = $filteredData;

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
    }

    public function getAll($collection) {
        $this->validateCollection($collection);

        if ($this->mode === 'mysql' && $this->pdo) {
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
        $this->validateCollection($collection);

        if ($this->mode === 'mysql' && $this->pdo) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM $collection WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if ($row) {
                    if (isset($row['custom_fields'])) $row['custom_fields'] = json_decode($row['custom_fields'], true);
                    if (isset($row['attachments'])) $row['attachments'] = json_decode($row['attachments'], true);
                }
                return $row ?: null;
            } catch (PDOException $e) {
                return null;
            }
        } else {
            $items = $this->getAll($collection);
            foreach ($items as $item) {
                if ($item['id'] === $id) return $item;
            }
            return null;
        }
    }

    public function save($collection, $data) {
        $this->validateCollection($collection);

        if (!isset($data['id'])) $data['id'] = bin2hex(random_bytes(16));

        if ($this->mode === 'mysql' && $this->pdo) {
            $this->saveMySQLDirectly($collection, $data);
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
        $this->validateCollection($collection);

        if ($this->mode === 'mysql' && $this->pdo) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM $collection WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                // Ignore delete error
            }
        } else {
            $items = $this->getAll($collection);
            $items = array_filter($items, fn($item) => $item['id'] !== $id);
            file_put_contents($this->dataDir . $collection . '.json', json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
