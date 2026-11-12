<?php
declare(strict_types=1);

require_once __DIR__ . '/../storage.php';

class MysqlDriver implements StorageInterface {
    private ?PDO $pdo = null;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
        $this->connect();
        $this->ensureSchema();
    }

    private function connect(): void {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $db = $this->config['database'] ?? 'crm_maintenance';
        $user = $this->config['username'] ?? 'root';
        $pass = $this->config['password'] ?? '';

        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $safeDb = preg_replace('/[^a-zA-Z0-9_]/', '', $db);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$safeDb}`");

        $this->pdo = $pdo;
    }

    public function ensureSchema(): void {
        if (!$this->pdo) {
            return;
        }

        $sqlUsers = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `role` ENUM('admin', 'manager', 'executor', 'requester') NOT NULL DEFAULT 'requester',
            `status` VARCHAR(20) NOT NULL DEFAULT 'active',
            `department` VARCHAR(150) DEFAULT NULL,
            `avatar` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlTickets = "CREATE TABLE IF NOT EXISTS `tickets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT NOT NULL,
            `category` VARCHAR(50) NOT NULL,
            `priority` VARCHAR(20) NOT NULL DEFAULT 'medium',
            `status` ENUM('new', 'assigned', 'in_progress', 'completed', 'rejected') NOT NULL DEFAULT 'new',
            `requester_id` INT NULL,
            `requester_name` VARCHAR(150) NULL,
            `executor_id` INT NULL,
            `executor_name` VARCHAR(150) NULL,
            `custom_fields` JSON NULL,
            `attachments` JSON NULL,
            `sla_hours` INT NOT NULL DEFAULT 24,
            `deadline` DATETIME NULL,
            `completed_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (`status`),
            INDEX idx_category (`category`),
            INDEX idx_executor (`executor_id`),
            INDEX idx_requester (`requester_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlComments = "CREATE TABLE IF NOT EXISTS `comments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `user_name` VARCHAR(150) NOT NULL,
            `user_role` VARCHAR(50) NOT NULL,
            `message` TEXT NOT NULL,
            `attachments` JSON NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (`ticket_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlFields = "CREATE TABLE IF NOT EXISTS `fields` (
            `id` VARCHAR(50) PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `label` VARCHAR(150) NOT NULL,
            `type` VARCHAR(30) NOT NULL,
            `required` TINYINT(1) NOT NULL DEFAULT 0,
            `placeholder` VARCHAR(255) DEFAULT '',
            `options` JSON NULL,
            `order` INT NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlNotifications = "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `ticket_id` INT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read (`user_id`, `is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlLogs = "CREATE TABLE IF NOT EXISTS `logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `user_name` VARCHAR(150) NULL,
            `action` VARCHAR(100) NOT NULL,
            `details` TEXT NULL,
            `ip` VARCHAR(50) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->pdo->exec($sqlUsers);
        $this->pdo->exec($sqlTickets);
        $this->pdo->exec($sqlComments);
        $this->pdo->exec($sqlFields);
        $this->pdo->exec($sqlNotifications);
        $this->pdo->exec($sqlLogs);
    }

    private function sanitizeTable(string $table): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    }

    public function getAll(string $collection): array {
        $table = $this->sanitizeTable($collection);
        $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll();
        return array_map([$this, 'unserializeRow'], $rows);
    }

    public function findById(string $collection, $id): ?array {
        $table = $this->sanitizeTable($collection);
        $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE `id` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->unserializeRow($row) : null;
    }

    public function findBy(string $collection, array $criteria): array {
        $table = $this->sanitizeTable($collection);
        $where = [];
        $params = [];
        $idx = 0;
        foreach ($criteria as $col => $val) {
            $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $paramName = "p_{$idx}";
            $where[] = "`{$safeCol}` = :{$paramName}";
            $params[$paramName] = $val;
            $idx++;
        }

        $sql = "SELECT * FROM `{$table}`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'unserializeRow'], $rows);
    }

    public function findOneBy(string $collection, array $criteria): ?array {
        $matches = $this->findBy($collection, $criteria);
        return !empty($matches) ? $matches[0] : null;
    }

    public function insert(string $collection, array $data): array {
        $table = $this->sanitizeTable($collection);
        $serialized = $this->serializeRow($data);

        $cols = array_keys($serialized);
        $escapedCols = array_map(fn($c) => "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $c) . "`", $cols);
        $placeholders = array_map(fn($c) => ":" . preg_replace('/[^a-zA-Z0-9_]/', '', $c), $cols);

        $sql = "INSERT INTO `{$table}` (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($serialized);

        if (!isset($data['id'])) {
            $data['id'] = (int)$this->pdo->lastInsertId();
        }
        return $data;
    }

    public function update(string $collection, $id, array $data): ?array {
        $table = $this->sanitizeTable($collection);
        $serialized = $this->serializeRow($data);
        unset($serialized['id']);

        if (empty($serialized)) {
            return $this->findById($collection, $id);
        }

        $sets = [];
        $params = ['_id' => $id];
        foreach ($serialized as $col => $val) {
            $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $sets[] = "`{$safeCol}` = :{$safeCol}";
            $params[$safeCol] = $val;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `id` = :_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->findById($collection, $id);
    }

    public function delete(string $collection, $id): bool {
        $table = $this->sanitizeTable($collection);
        $stmt = $this->pdo->prepare("DELETE FROM `{$table}` WHERE `id` = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function testConnection(): bool {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getDriverName(): string {
        return 'mysql';
    }

    private function serializeRow(array $data): array {
        $result = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $result[$k] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($v)) {
                $result[$k] = $v ? 1 : 0;
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    private function unserializeRow(array $row): array {
        $result = [];
        foreach ($row as $k => $v) {
            if (in_array($k, ['custom_fields', 'attachments', 'options']) && is_string($v)) {
                $decoded = json_decode($v, true);
                $result[$k] = $decoded !== null ? $decoded : $v;
            } elseif (in_array($k, ['required', 'active', 'is_read', 'work_permit'])) {
                $result[$k] = (bool)$v;
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public function migrateFromJson(string $dataDir): array {
        $collections = ['users', 'fields', 'tickets', 'comments', 'notifications', 'logs'];
        $stats = [];
        $jsonDriver = new JsonDriver($dataDir);

        foreach ($collections as $col) {
            $items = $jsonDriver->getAll($col);
            $count = 0;
            foreach ($items as $item) {
                $existing = $this->findById($col, $item['id']);
                if ($existing) {
                    $this->update($col, $item['id'], $item);
                } else {
                    $this->insert($col, $item);
                }
                $count++;
            }
            $stats[$col] = $count;
        }

        return $stats;
    }
}
