<?php

class Storage {
    private $mode;
    private $dataDir = __DIR__ . '/../data/';
    private $pdo = null;

    public function __construct($settings = []) {
        $this->mode = $settings['storage_mode'] ?? 'json';
        if ($this->mode === 'mysql') {
            $this->initMySQL($settings['mysql'] ?? []);
        }
    }

    private function initMySQL($config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->ensureSchema();
        } catch (PDOException $e) {
            $this->mode = 'json'; // Fallback
        }
    }

    private function ensureSchema() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'responsible', 'head', 'executor') NOT NULL,
            full_name VARCHAR(255),
            phone VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('new', 'assigned', 'in_work', 'completed', 'rejected') DEFAULT 'new',
            creator_id INT,
            executor_id INT,
            deadline DATETIME,
            custom_fields JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (executor_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT,
            user_id INT,
            text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT,
            user_id INT,
            old_status VARCHAR(50),
            new_status VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
    }

    private function decodeResult($item) {
        if (!$item) return $item;
        foreach ($item as $key => $value) {
            if (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $item[$key] = $decoded;
                }
            }
        }
        return $item;
    }

    public function get($collection, $where = []) {
        if ($this->mode === 'mysql' && $this->pdo) {
            $sql = "SELECT * FROM $collection";
            $params = [];
            if (!empty($where)) {
                $clauses = [];
                foreach ($where as $key => $val) {
                    $clauses[] = "$key = ?";
                    $params[] = $val;
                }
                $sql .= " WHERE " . implode(' AND ', $clauses);
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            return array_map([$this, 'decodeResult'], $results);
        } else {
            $file = $this->dataDir . $collection . '.json';
            if (!file_exists($file)) return [];
            $data = json_decode(file_get_contents($file), true) ?: [];
            if (!empty($where)) {
                $data = array_filter($data, function($item) use ($where) {
                    foreach ($where as $k => $v) {
                        if (!isset($item[$k]) || $item[$k] != $v) return false;
                    }
                    return true;
                });
            }
            return array_values($data);
        }
    }

    public function find($collection, $id) {
        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("SELECT * FROM $collection WHERE id = ?");
            $stmt->execute([$id]);
            return $this->decodeResult($stmt->fetch());
        } else {
            $data = $this->get($collection);
            foreach ($data as $item) {
                if ($item['id'] == $id) return $item;
            }
            return null;
        }
    }

    public function save($collection, $data) {
        if ($this->mode === 'mysql' && $this->pdo) {
            $toSave = $data;
            foreach ($toSave as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $toSave[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
            }

            if (isset($toSave['id'])) {
                $id = $toSave['id'];
                unset($toSave['id']);
                $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($toSave)));
                $stmt = $this->pdo->prepare("UPDATE $collection SET $sets WHERE id = :id");
                $toSave['id'] = $id;
                $stmt->execute($toSave);
                return $id;
            } else {
                $keys = implode(', ', array_keys($toSave));
                $placeholders = ':' . implode(', :', array_keys($toSave));
                $stmt = $this->pdo->prepare("INSERT INTO $collection ($keys) VALUES ($placeholders)");
                $stmt->execute($toSave);
                return $this->pdo->lastInsertId();
            }
        } else {
            $items = $this->get($collection);
            if (isset($data['id'])) {
                foreach ($items as &$item) {
                    if ($item['id'] == $data['id']) {
                        $item = array_merge($item, $data);
                        break;
                    }
                }
            } else {
                $data['id'] = time() . rand(100, 999);
                $items[] = $data;
            }
            $this->atomicWrite($collection, $items);
            return $data['id'];
        }
    }

    public function delete($collection, $id) {
        if ($this->mode === 'mysql' && $this->pdo) {
            $stmt = $this->pdo->prepare("DELETE FROM $collection WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $items = $this->get($collection);
            $items = array_filter($items, fn($i) => $i['id'] != $id);
            $this->atomicWrite($collection, array_values($items));
        }
    }

    public function atomicWrite($collection, $data) {
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        $file = $this->dataDir . $collection . '.json';
        $temp = $file . '.tmp';
        file_put_contents($temp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($temp, $file);
    }
}
