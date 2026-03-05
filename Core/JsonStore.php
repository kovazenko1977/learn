<?php
namespace Core;

class JsonStore {
    private $filePath;
    private $collection;

    public function __construct($collection) {
        $this->collection = $collection;
        $this->filePath = __DIR__ . '/../Data/' . $collection . '.json';
        if (!file_exists($this->filePath)) {
            if (!is_dir(dirname($this->filePath))) {
                mkdir(dirname($this->filePath), 0777, true);
            }
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    private function ensureDirectory() {
        if (!is_dir(dirname($this->filePath))) {
            mkdir(dirname($this->filePath), 0777, true);
        }
    }

    private function logAction($action, $itemId, $details = []) {
        if ($this->collection === 'audit_logs') return;

        $auditPath = __DIR__ . '/../Data/audit_logs.json';
        $user = $_SESSION['user'] ?? ['username' => 'system'];

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $user['username'],
            'collection' => $this->collection,
            'action' => $action,
            'item_id' => $itemId,
            'details' => $details
        ];

        $fp = fopen($auditPath, 'c+');
        if (flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $logs = json_decode($content, true) ?: [];
            $logs[] = $logEntry;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    public function findAll() {
        if (!file_exists($this->filePath)) return [];
        $fp = fopen($this->filePath, 'r');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return json_decode($content, true) ?: [];
    }

    public function save($data) {
        $this->ensureDirectory();
        $fp = fopen($this->filePath, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    public function findOne($id) {
        $data = $this->findAll();
        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] == $id) return $item;
        }
        return null;
    }

    public function create($item) {
        $data = $this->findAll();
        if (!isset($item['id'])) {
            $item['id'] = bin2hex(random_bytes(8)) . '-' . time();
        }
        $data[] = $item;
        $this->save($data);
        $this->logAction('create', $item['id'], $item);
        return $item;
    }

    public function update($id, $updates) {
        $data = $this->findAll();
        foreach ($data as &$item) {
            if (isset($item['id']) && $item['id'] == $id) {
                $item = array_merge($item, $updates);
                $this->save($data);
                $this->logAction('update', $id, $updates);
                return $item;
            }
        }
        return null;
    }

    public function delete($id) {
        $data = $this->findAll();
        $initialCount = count($data);
        $data = array_filter($data, function($item) use ($id) {
            return isset($item['id']) && $item['id'] != $id;
        });
        if (count($data) !== $initialCount) {
            $this->save(array_values($data));
            $this->logAction('delete', $id);
            return true;
        }
        return false;
    }
}
