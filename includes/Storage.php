<?php
/**
 * Storage Engine for Mobile Organizer & Voice Notes App
 * Supports SQLite PDO database with seamless JSON file fallback in data/
 */

class Storage {
    private string $dataDir;
    private string $uploadsDir;
    private bool $useSqlite = false;
    private ?PDO $db = null;

    public function __construct() {
        $this->dataDir = __DIR__ . '/../data';
        $this->uploadsDir = __DIR__ . '/../uploads';

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }

        // Try initializing SQLite PDO
        if (extension_loaded('pdo_sqlite')) {
            try {
                $dbPath = $this->dataDir . '/organizer.sqlite';
                $this->db = new PDO('sqlite:' . $dbPath);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->useSqlite = true;
                $this->initSqliteTables();
            } catch (Exception $e) {
                $this->useSqlite = false;
            }
        }
    }

    private function initSqliteTables(): void {
        if (!$this->db) return;

        $queries = [
            "CREATE TABLE IF NOT EXISTS items (
                id TEXT PRIMARY KEY,
                collection TEXT NOT NULL,
                data TEXT NOT NULL,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            )",
            "CREATE INDEX IF NOT EXISTS idx_collection ON items(collection)"
        ];

        foreach ($queries as $q) {
            $this->db->exec($q);
        }
    }

    public function getCollection(string $collection): array {
        if ($this->useSqlite && $this->db) {
            $stmt = $this->db->prepare("SELECT data FROM items WHERE collection = :col ORDER BY created_at DESC");
            $stmt->execute(['col' => $collection]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $items = [];
            foreach ($rows as $row) {
                $decoded = json_decode($row, true);
                if ($decoded) $items[] = $decoded;
            }
            return $items;
        }

        // JSON file storage fallback
        $filePath = $this->dataDir . '/' . preg_replace('/[^a-z0-9_]/', '', $collection) . '.json';
        if (!file_exists($filePath)) {
            return [];
        }
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public function saveCollection(string $collection, array $items): bool {
        if ($this->useSqlite && $this->db) {
            $this->db->beginTransaction();
            try {
                $stmtDel = $this->db->prepare("DELETE FROM items WHERE collection = :col");
                $stmtDel->execute(['col' => $collection]);

                $stmtIns = $this->db->prepare("INSERT INTO items (id, collection, data, created_at, updated_at) VALUES (:id, :col, :data, :created, :updated)");
                $now = time();
                foreach ($items as $item) {
                    $id = $item['id'] ?? uniqid('item_');
                    $stmtIns->execute([
                        'id' => $id,
                        'col' => $collection,
                        'data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                        'created' => $item['created_at'] ?? $now,
                        'updated' => $item['updated_at'] ?? $now
                    ]);
                }
                $this->db->commit();
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                return false;
            }
        }

        // JSON storage
        $filePath = $this->dataDir . '/' . preg_replace('/[^a-z0-9_]/', '', $collection) . '.json';
        return file_put_contents($filePath, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    public function saveItem(string $collection, array $item): array {
        if (empty($item['id'])) {
            $item['id'] = 'id_' . bin2hex(random_bytes(8));
        }
        $now = time();
        if (empty($item['created_at'])) {
            $item['created_at'] = $now;
        }
        $item['updated_at'] = $now;

        $items = $this->getCollection($collection);
        $foundIndex = -1;
        foreach ($items as $index => $existing) {
            if (isset($existing['id']) && $existing['id'] === $item['id']) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex >= 0) {
            $items[$foundIndex] = array_merge($items[$foundIndex], $item);
        } else {
            array_unshift($items, $item);
        }

        $this->saveCollection($collection, $items);
        return $item;
    }

    public function deleteItem(string $collection, string $id): bool {
        $items = $this->getCollection($collection);
        $filtered = array_values(array_filter($items, fn($i) => isset($i['id']) && $i['id'] !== $id));
        return $this->saveCollection($collection, $filtered);
    }

    public function getItem(string $collection, string $id): ?array {
        $items = $this->getCollection($collection);
        foreach ($items as $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }
}
