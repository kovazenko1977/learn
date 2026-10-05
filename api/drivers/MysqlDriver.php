<?php
// api/drivers/MysqlDriver.php

class MysqlDriver {
    private ?PDO $pdo = null;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function connect(): PDO {
        if ($this->pdo === null) {
            $host = $this->config['host'] ?? '127.0.0.1';
            $port = $this->config['port'] ?? 3306;
            $db   = $this->config['name'] ?? 'flower_studio';
            $user = $this->config['user'] ?? 'root';
            $pass = $this->config['pass'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->ensureTables();
        }
        return $this->pdo;
    }

    public function testConnection(): bool {
        try {
            $host = $this->config['host'] ?? '127.0.0.1';
            $port = $this->config['port'] ?? 3306;
            $db   = $this->config['name'] ?? '';
            $user = $this->config['user'] ?? 'root';
            $pass = $this->config['pass'] ?? '';

            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            if (!empty($db)) {
                $dsn .= ";dbname={$db}";
            }
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ensureTables(): void {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'user',
                is_blocked TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                old_price DECIMAL(10,2) DEFAULT NULL,
                category VARCHAR(100) DEFAULT 'Букеты',
                stock INT DEFAULT 10,
                emoji VARCHAR(50) DEFAULT '💐',
                image VARCHAR(255) DEFAULT '',
                is_sale TINYINT(1) DEFAULT 0,
                active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS orders (
                id VARCHAR(100) PRIMARY KEY,
                user_id INT NOT NULL,
                user_name VARCHAR(255),
                user_phone VARCHAR(50),
                address VARCHAR(255),
                date VARCHAR(50),
                time VARCHAR(50),
                comment TEXT,
                promocode VARCHAR(50) DEFAULT '',
                items JSON,
                subtotal DECIMAL(10,2) DEFAULT 0.00,
                delivery_fee DECIMAL(10,2) DEFAULT 0.00,
                discount DECIMAL(10,2) DEFAULT 0.00,
                total DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'Новый',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS promos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                text TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS promocodes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                discount DECIMAL(10,2) NOT NULL,
                used INT DEFAULT 0,
                `limit` INT DEFAULT 100,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS news (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                text TEXT,
                date VARCHAR(50),
                image VARCHAR(255) DEFAULT '📰',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS chats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                sender VARCHAR(20) NOT NULL,
                text TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event VARCHAR(100) NOT NULL,
                screen VARCHAR(100) DEFAULT '',
                ip VARCHAR(100) DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                store_name VARCHAR(255),
                store_phone VARCHAR(50),
                store_address VARCHAR(255),
                working_hours VARCHAR(100),
                currency VARCHAR(20),
                delivery_price DECIMAL(10,2),
                free_delivery_from DECIMAL(10,2),
                min_order DECIMAL(10,2),
                auto_reply TEXT,
                surprise_discount INT DEFAULT 10,
                instagram VARCHAR(255),
                telegram VARCHAR(255),
                viber VARCHAR(255)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        $pdo = $this->connect();
        foreach ($queries as $sql) {
            $pdo->exec($sql);
        }
    }

    public function getAll(string $collection): array {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) . "`");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return array_map([$this, 'castRow'], $rows);
    }

    public function getById(string $collection, $id): ?array {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) . "` WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->castRow($row) : null;
    }

    public function findWhere(string $collection, array $criteria): array {
        $pdo = $this->connect();
        $where = [];
        $params = [];
        foreach ($criteria as $k => $v) {
            $cleanK = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $where[] = "`{$cleanK}` = ?";
            $params[] = $v;
        }
        $sql = "SELECT * FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) . "`";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'castRow'], $rows);
    }

    public function insert(string $collection, array $data): array {
        $pdo = $this->connect();
        $cols = [];
        $placeholders = [];
        $params = [];

        foreach ($data as $k => $v) {
            $cleanK = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $cols[] = "`{$cleanK}`";
            $placeholders[] = "?";
            $params[] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
        }

        $sql = "INSERT INTO `" . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) .
               "` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!isset($data['id'])) {
            $data['id'] = (int)$pdo->lastInsertId();
        }
        return $data;
    }

    public function update(string $collection, $id, array $data): ?array {
        $pdo = $this->connect();
        $sets = [];
        $params = [];

        foreach ($data as $k => $v) {
            if ($k === 'id') continue;
            $cleanK = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $sets[] = "`{$cleanK}` = ?";
            $params[] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
        }

        if (empty($sets)) {
            return $this->getById($collection, $id);
        }

        $params[] = $id;
        $sql = "UPDATE `" . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) .
               "` SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $this->getById($collection, $id);
    }

    public function delete(string $collection, $id): bool {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("DELETE FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $collection) . "` WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function castRow(array $row): array {
        foreach ($row as $k => $v) {
            if (is_string($v) && ($v === 'true' || $v === 'false')) {
                $row[$k] = ($v === 'true');
            } elseif ($k === 'items') {
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$k] = $decoded;
                }
            } elseif (in_array($k, ['id', 'user_id', 'stock', 'used', 'limit', 'surprise_discount'])) {
                if (is_numeric($v)) $row[$k] = (int)$v;
            } elseif (in_array($k, ['price', 'old_price', 'subtotal', 'delivery_fee', 'discount', 'total'])) {
                if ($v !== null) $row[$k] = (float)$v;
            } elseif (in_array($k, ['is_sale', 'active', 'is_blocked', 'is_read'])) {
                if ($v !== null) $row[$k] = (bool)$v;
            }
        }
        return $row;
    }
}
