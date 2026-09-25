<?php
/**
 * Storage Abstraction Layer for МедСервис
 * Supports JSON file storage with MySQL PDO interface capability.
 */

interface StorageInterface {
    public function getCollection(string $name): array;
    public function find(string $name, callable $predicate): array;
    public function findOne(string $name, callable $predicate): ?array;
    public function insert(string $name, array $data): array;
    public function update(string $name, $id, array $data): bool;
    public function delete(string $name, $id): bool;
    public function paginate(string $name, int $page = 1, int $limit = 20, ?callable $filter = null, ?callable $sort = null): array;
}

class JSONStorage implements StorageInterface {
    private string $dataDir;

    public function __construct(?string $dataDir = null) {
        $this->dataDir = $dataDir ?? __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    private function getFilePath(string $name): string {
        return $this->dataDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $name) . '.json';
    }

    public function getCollection(string $name): array {
        $filePath = $this->getFilePath($name);
        if (!file_exists($filePath)) {
            return [];
        }

        $fp = fopen($filePath, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if (empty($content)) return [];
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function saveCollection(string $name, array $items): bool {
        $filePath = $this->getFilePath($name);
        $fp = fopen($filePath, 'c+');
        if (!$fp) return false;

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }

        fclose($fp);
        return false;
    }

    public function find(string $name, callable $predicate): array {
        $items = $this->getCollection($name);
        return array_values(array_filter($items, $predicate));
    }

    public function findOne(string $name, callable $predicate): ?array {
        $items = $this->getCollection($name);
        foreach ($items as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }
        return null;
    }

    public function insert(string $name, array $data): array {
        $items = $this->getCollection($name);

        if (!isset($data['id'])) {
            $maxId = 0;
            foreach ($items as $item) {
                if (isset($item['id']) && is_numeric($item['id']) && $item['id'] > $maxId) {
                    $maxId = (int)$item['id'];
                }
            }
            $data['id'] = $maxId + 1;
        }

        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $items[] = $data;
        $this->saveCollection($name, $items);
        return $data;
    }

    public function update(string $name, $id, array $data): bool {
        $items = $this->getCollection($name);
        $updated = false;

        foreach ($items as $i => $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                $items[$i] = array_merge($item, $data, ['updated_at' => date('Y-m-d H:i:s')]);
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->saveCollection($name, $items);
        }

        return false;
    }

    public function delete(string $name, $id): bool {
        $items = $this->getCollection($name);
        $filtered = array_filter($items, fn($item) => !isset($item['id']) || $item['id'] != $id);

        if (count($filtered) !== count($items)) {
            return $this->saveCollection($name, array_values($filtered));
        }

        return false;
    }

    public function paginate(string $name, int $page = 1, int $limit = 20, ?callable $filter = null, ?callable $sort = null): array {
        $items = $this->getCollection($name);

        if ($filter !== null) {
            $items = array_values(array_filter($items, $filter));
        }

        if ($sort !== null) {
            usort($items, $sort);
        }

        $total = count($items);
        $totalPages = ceil($total / $limit) ?: 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $pagedItems = array_slice($items, $offset, $limit);

        return [
            'data' => $pagedItems,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages
        ];
    }
}

class MySQLStorage implements StorageInterface {
    private ?PDO $pdo = null;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
    }

    public function getCollection(string $name): array {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->query("SELECT * FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $name) . "`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(string $name, callable $predicate): array {
        return array_values(array_filter($this->getCollection($name), $predicate));
    }

    public function findOne(string $name, callable $predicate): ?array {
        foreach ($this->getCollection($name) as $item) {
            if ($predicate($item)) return $item;
        }
        return null;
    }

    public function insert(string $name, array $data): array {
        if (!$this->pdo) return $data;
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        $data['id'] = $this->pdo->lastInsertId();
        return $data;
    }

    public function update(string $name, $id, array $data): bool {
        if (!$this->pdo) return false;
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $setStr = implode(',', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE `$table` SET $setStr WHERE id = ?");
        $values = array_values($data);
        $values[] = $id;
        return $stmt->execute($values);
    }

    public function delete(string $name, $id): bool {
        if (!$this->pdo) return false;
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $stmt = $this->pdo->prepare("DELETE FROM `$table` WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function paginate(string $name, int $page = 1, int $limit = 20, ?callable $filter = null, ?callable $sort = null): array {
        $items = $this->getCollection($name);
        if ($filter) $items = array_values(array_filter($items, $filter));
        if ($sort) usort($items, $sort);
        $total = count($items);
        $totalPages = ceil($total / $limit) ?: 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;
        return [
            'data' => array_slice($items, $offset, $limit),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages
        ];
    }
}

class StorageProvider {
    private static ?StorageInterface $instance = null;

    public static function getInstance(): StorageInterface {
        if (self::$instance === null) {
            self::$instance = new JSONStorage();
        }
        return self::$instance;
    }
}
