<?php

class Storage {
    private $dataDir;
    private $mode = 'json'; // 'json' or 'mysql'
    private $pdo = null;

    public function __construct($dataDir) {
        $this->dataDir = realpath($dataDir);
        if (!$this->dataDir) {
            if (!mkdir($dataDir, 0755, true)) {
                die("Storage Error: Cannot create data directory.");
            }
            $this->dataDir = realpath($dataDir);
        }
        $this->loadConfig();
    }

    private function loadConfig() {
        $configFile = $this->dataDir . '/config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config && isset($config['mode'])) {
                $this->mode = $config['mode'];
                if ($this->mode === 'mysql' && isset($config['mysql'])) {
                    try {
                        $m = $config['mysql'];
                        $dsn = "mysql:host={$m['host']};dbname={$m['dbname']};charset=utf8mb4";
                        $this->pdo = new PDO($dsn, $m['user'], $m['password'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);
                    } catch (PDOException $e) {
                        // Fallback to JSON if MySQL fails
                        $this->mode = 'json';
                    }
                }
            }
        }
    }

    public function getMode() { return $this->mode; }

    public function setConfig($config) {
        $configFile = $this->dataDir . '/config.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        $this->loadConfig();
    }

    public function readCollection($collection) {
        if ($this->mode === 'mysql') {
            try {
                $stmt = $this->pdo->query("SELECT * FROM `$collection` ORDER BY id DESC");
                $results = $stmt->fetchAll();
                // Decode JSON fields if necessary (some fields might be JSON in MySQL)
                return array_map([$this, 'decodeRow'], $results);
            } catch (PDOException $e) { return []; }
        }

        $file = $this->dataDir . '/' . $collection . '.json';
        if (!file_exists($file)) return [];
        $fp = fopen($file, 'r');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $size = filesize($file);
        $data = $size > 0 ? fread($fp, $size) : '';
        flock($fp, LOCK_UN);
        fclose($fp);
        $data = str_replace("\xEF\xBB\xBF", '', $data);
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function decodeRow($row) {
        // Automatically decode fields that look like JSON
        if (isset($row['permissions']) && is_string($row['permissions'])) $row['permissions'] = json_decode($row['permissions'], true);
        // Ensure booleans are returned as bools if they were stored as tinyint
        if (isset($row['is_active'])) $row['is_active'] = (bool)$row['is_active'];
        if (isset($row['auth_enabled'])) $row['auth_enabled'] = (bool)$row['auth_enabled'];
        return $row;
    }

    private function encodeValue($val) {
        return (is_array($val) || is_object($val)) ? json_encode($val) : $val;
    }

    public function transactional($collection, callable $callback) {
        if ($this->mode === 'mysql') {
            try {
                $this->pdo->beginTransaction();
                $items = $this->readCollection($collection);
                $result = $callback($items);
                if ($result !== false) {
                    // Sync the entire collection back to MySQL (inefficient but maintains API compatibility)
                    // For 'settings', we usually only have one row 'global'
                    foreach ($items as $item) {
                        $keys = array_keys($item);
                        $sets = [];
                        foreach ($keys as $k) { if ($k !== 'id') $sets[] = "`$k` = :$k"; }
                        $sql = "INSERT INTO `$collection` (`" . implode('`, `', $keys) . "`) VALUES (:" . implode(', :', $keys) . ")
                                ON DUPLICATE KEY UPDATE " . implode(', ', $sets);
                        $stmt = $this->pdo->prepare($sql);
                        foreach ($item as $k => $v) { $stmt->bindValue(":$k", $this->encodeValue($v)); }
                        $stmt->execute();
                    }
                }
                $this->pdo->commit();
                return $result;
            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                return false;
            }
        }

        $file = $this->dataDir . '/' . $collection . '.json';
        if (!file_exists($file)) touch($file);
        $fp = fopen($file, 'c+');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        $size = filesize($file);
        $content = $size > 0 ? fread($fp, $size) : '';
        $content = str_replace("\xEF\xBB\xBF", '', $content);
        $data = json_decode($content, true) ?: [];
        $result = $callback($data);
        if ($result !== false) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result;
    }

    public function findOne($collection, $query) {
        if ($this->mode === 'mysql') {
            $where = [];
            $params = [];
            foreach ($query as $k => $v) {
                $where[] = "`$k` = ?";
                $params[] = $v;
            }
            $sql = "SELECT * FROM `$collection` WHERE " . implode(' AND ', $where) . " LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row ? $this->decodeRow($row) : null;
        }
        $items = $this->readCollection($collection);
        foreach ($items as $item) {
            $match = true;
            foreach ($query as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) { $match = false; break; }
            }
            if ($match) return $item;
        }
        return null;
    }

    public function find($collection, $query) {
        if ($this->mode === 'mysql') {
            $where = [];
            $params = [];
            foreach ($query as $k => $v) {
                $where[] = "`$k` = ?";
                $params[] = $v;
            }
            $sql = "SELECT * FROM `$collection` WHERE " . implode(' AND ', $where);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return array_map([$this, 'decodeRow'], $stmt->fetchAll());
        }
        $items = $this->readCollection($collection);
        return array_values(array_filter($items, function($item) use ($query) {
            foreach ($query as $k => $v) if (!isset($item[$k]) || $item[$k] != $v) return false;
            return true;
        }));
    }

    public function insert($collection, $item) {
        if ($this->mode === 'mysql') {
            if (!isset($item['id'])) $item['id'] = time() . rand(100, 999);
            $keys = array_keys($item);
            $placeholders = array_fill(0, count($keys), '?');
            $sql = "INSERT INTO `$collection` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);
            $values = array_map([$this, 'encodeValue'], array_values($item));
            $stmt->execute($values);
            return $item;
        }
        return $this->transactional($collection, function(&$items) use ($item) {
            if (!isset($item['id'])) $item['id'] = time() . rand(100, 999);
            $items[] = $item;
            return $item;
        });
    }

    public function update($collection, $id, $updates) {
        if ($this->mode === 'mysql') {
            $sets = [];
            $params = [];
            foreach ($updates as $k => $v) {
                $sets[] = "`$k` = ?";
                $params[] = $this->encodeValue($v);
            }
            $params[] = $id;
            $sql = "UPDATE `$collection` SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        }
        return $this->transactional($collection, function(&$items) use ($id, $updates) {
            foreach ($items as &$item) {
                if ($item['id'] == $id) { $item = array_merge($item, $updates); return true; }
            }
            return false;
        });
    }

    public function delete($collection, $id) {
        if ($this->mode === 'mysql') {
            $stmt = $this->pdo->prepare("DELETE FROM `$collection` WHERE id = ?");
            return $stmt->execute([$id]);
        }
        return $this->transactional($collection, function(&$items) use ($id) {
            $count = count($items);
            $items = array_values(array_filter($items, function($i) use ($id) { return $i['id'] != $id; }));
            return count($items) < $count;
        });
    }

    public function initMySQL() {
        if ($this->mode !== 'mysql' || !$this->pdo) return false;
        $sqls = [
            "CREATE TABLE IF NOT EXISTS `users` (
                id VARCHAR(50) PRIMARY KEY,
                login VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(255),
                role VARCHAR(50),
                department_id VARCHAR(50),
                is_active TINYINT DEFAULT 1,
                created_at DATETIME,
                permissions TEXT
            )",
            "CREATE TABLE IF NOT EXISTS `departments` (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                manager_id VARCHAR(50),
                description TEXT
            )",
            "CREATE TABLE IF NOT EXISTS `work_types` (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                department_id VARCHAR(50),
                sla_hours INT DEFAULT 24,
                description TEXT
            )",
            "CREATE TABLE IF NOT EXISTS `requests` (
                id VARCHAR(50) PRIMARY KEY,
                number VARCHAR(50) UNIQUE,
                requester_id VARCHAR(50),
                work_type_id VARCHAR(50),
                department_id VARCHAR(50),
                assigned_to VARCHAR(50),
                priority VARCHAR(20),
                location VARCHAR(255),
                description TEXT,
                status VARCHAR(50),
                file_path VARCHAR(255),
                file_original_name VARCHAR(255),
                rating INT,
                created_at DATETIME,
                updated_at DATETIME,
                deadline_at DATETIME
            )",
            "CREATE TABLE IF NOT EXISTS `status_history` (
                id VARCHAR(50) PRIMARY KEY,
                request_id VARCHAR(50),
                status VARCHAR(50),
                changed_by VARCHAR(50),
                changed_at DATETIME,
                comment TEXT
            )",
            "CREATE TABLE IF NOT EXISTS `comments` (
                id VARCHAR(50) PRIMARY KEY,
                request_id VARCHAR(50),
                user_id VARCHAR(50),
                message TEXT,
                created_at DATETIME
            )",
            "CREATE TABLE IF NOT EXISTS `login_logs` (
                id VARCHAR(50) PRIMARY KEY,
                user_id VARCHAR(50),
                full_name VARCHAR(255),
                login VARCHAR(100),
                timestamp DATETIME,
                ip VARCHAR(50),
                user_agent TEXT
            )",
            "CREATE TABLE IF NOT EXISTS `settings` (
                id VARCHAR(50) PRIMARY KEY,
                auth_enabled TINYINT,
                announcement TEXT,
                notify_sound TINYINT DEFAULT 0,
                notify_browser TINYINT DEFAULT 0,
                notify_new_text TEXT,
                org_name VARCHAR(255)
            )"
        ];
        foreach ($sqls as $sql) $this->pdo->exec($sql);
        return true;
    }
}
