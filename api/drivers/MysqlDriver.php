<?php
// api/drivers/MysqlDriver.php

class MysqlDriver {
    private $pdo;

    public function __construct($host, $dbname, $user, $password, $port = 3306) {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $this->ensureTables();
    }

    private function sanitizeTable($entity) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $entity);
    }

    public function ensureTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS `users` (
                `id` VARCHAR(64) PRIMARY KEY,
                `name` VARCHAR(191) NOT NULL,
                `phone` VARCHAR(64) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(32) DEFAULT 'user',
                `blocked` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME,
                `updated_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `products` (
                `id` VARCHAR(64) PRIMARY KEY,
                `title` VARCHAR(191) NOT NULL,
                `category` VARCHAR(64) NOT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `old_price` DECIMAL(10,2) DEFAULT NULL,
                `description` TEXT,
                `image` TEXT,
                `is_sale` TINYINT(1) DEFAULT 0,
                `stock` INT DEFAULT 10,
                `deleted` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME,
                `updated_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `orders` (
                `id` VARCHAR(64) PRIMARY KEY,
                `user_id` VARCHAR(64),
                `user_name` VARCHAR(191),
                `user_phone` VARCHAR(64),
                `items` LONGTEXT,
                `total_price` DECIMAL(10,2),
                `discount` DECIMAL(10,2) DEFAULT 0,
                `address` TEXT,
                `lat` DECIMAL(10,8) DEFAULT NULL,
                `lng` DECIMAL(11,8) DEFAULT NULL,
                `status` VARCHAR(32) DEFAULT 'Новый',
                `comment` TEXT,
                `promocode` VARCHAR(64) DEFAULT NULL,
                `created_at` DATETIME,
                `updated_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `promos` (
                `id` VARCHAR(64) PRIMARY KEY,
                `title` VARCHAR(191) NOT NULL,
                `description` TEXT,
                `image` TEXT,
                `badge` VARCHAR(64) DEFAULT NULL,
                `created_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `promocodes` (
                `id` VARCHAR(64) PRIMARY KEY,
                `code` VARCHAR(64) NOT NULL UNIQUE,
                `discount_percent` INT NOT NULL,
                `uses_left` INT DEFAULT 100,
                `active` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `news` (
                `id` VARCHAR(64) PRIMARY KEY,
                `title` VARCHAR(191) NOT NULL,
                `content` TEXT NOT NULL,
                `image` TEXT,
                `created_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `chats` (
                `id` VARCHAR(64) PRIMARY KEY,
                `user_id` VARCHAR(64) NOT NULL,
                `user_name` VARCHAR(191),
                `messages` LONGTEXT,
                `unread_admin` TINYINT(1) DEFAULT 0,
                `unread_user` TINYINT(1) DEFAULT 0,
                `updated_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `stats` (
                `id` VARCHAR(64) PRIMARY KEY,
                `event` VARCHAR(64) NOT NULL,
                `screen` VARCHAR(64) DEFAULT NULL,
                `product_id` VARCHAR(64) DEFAULT NULL,
                `data` LONGTEXT,
                `created_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `settings` (
                `id` VARCHAR(64) PRIMARY KEY,
                `phone` VARCHAR(64),
                `address` TEXT,
                `working_hours` VARCHAR(191),
                `delivery_cost` DECIMAL(10,2) DEFAULT 0,
                `free_delivery_threshold` DECIMAL(10,2) DEFAULT 0,
                `min_order_amount` DECIMAL(10,2) DEFAULT 0,
                `roulette_discount` INT DEFAULT 10,
                `auto_reply_text` TEXT,
                `driver` VARCHAR(32) DEFAULT 'json',
                `mysql_config` LONGTEXT,
                `social_links` LONGTEXT,
                `created_at` DATETIME,
                `updated_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `notifications` (
                `id` VARCHAR(64) PRIMARY KEY,
                `user_id` VARCHAR(64) DEFAULT 'all',
                `title` VARCHAR(191) NOT NULL,
                `message` TEXT NOT NULL,
                `is_read` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $q) {
            $this->pdo->exec($q);
        }
    }

    public function all($entity) {
        $table = $this->sanitizeTable($entity);
        $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
        $results = $stmt->fetchAll();
        return array_map([$this, 'decodeJsonFields'], $results);
    }

    public function get($entity, array $filter = []) {
        $table = $this->sanitizeTable($entity);
        if (empty($filter)) {
            return $this->all($entity);
        }

        $where = [];
        $params = [];
        foreach ($filter as $k => $v) {
            $cleanK = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $where[] = "`{$cleanK}` = :{$cleanK}";
            $params[$cleanK] = $v;
        }

        $sql = "SELECT * FROM `{$table}` WHERE " . implode(" AND ", $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        return array_map([$this, 'decodeJsonFields'], $results);
    }

    public function getById($entity, $id) {
        $table = $this->sanitizeTable($entity);
        $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE `id` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->decodeJsonFields($row) : null;
    }

    public function insert($entity, array $data) {
        $table = $this->sanitizeTable($entity);
        if (!isset($data['id'])) {
            $data['id'] = 'id_' . uniqid() . '_' . rand(100, 999);
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $encodedData = $this->encodeJsonFields($data);
        $keys = array_map(fn($k) => preg_replace('/[^a-zA-Z0-9_]/', '', $k), array_keys($encodedData));
        $fields = implode("`, `", $keys);
        $placeholders = implode(", :", $keys);

        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES (:${placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($encodedData);

        return $data;
    }

    public function update($entity, $id, array $data) {
        $table = $this->sanitizeTable($entity);
        $data['updated_at'] = date('Y-m-d H:i:s');
        $encodedData = $this->encodeJsonFields($data);

        $set = [];
        $params = ['id' => $id];
        foreach ($encodedData as $k => $v) {
            if ($k === 'id') continue;
            $cleanK = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $set[] = "`{$cleanK}` = :{$cleanK}";
            $params[$cleanK] = $v;
        }

        $sql = "UPDATE `{$table}` SET " . implode(", ", $set) . " WHERE `id` = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->getById($entity, $id);
    }

    public function delete($entity, $id) {
        $table = $this->sanitizeTable($entity);
        $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function encodeJsonFields(array $data) {
        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $data[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }
        return $data;
    }

    private function decodeJsonFields(array $row) {
        foreach ($row as $k => $v) {
            if (is_string($v) && ($v !== '') && ($v[0] === '[' || $v[0] === '{')) {
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$k] = $decoded;
                }
            }
        }
        return $row;
    }
}
