<?php
namespace Medical\Core;

class MySqlStore {
    private $pdo;
    private $tableName;

    public function __construct($tableName) {
        $this->tableName = $tableName;
        $this->pdo = DB::getInstance()->getPdo();
    }

    public function getAll() {
        if ($this->tableName === 'settings') {
            $stmt = $this->pdo->query("SELECT * FROM settings");
            $result = [];
            while ($row = $stmt->fetch()) {
                $result[$row['name']] = json_decode($row['value'], true);
            }
            return $result;
        }

        $stmt = $this->pdo->query("SELECT * FROM " . $this->tableName);
        $data = $stmt->fetchAll();
        foreach ($data as &$row) {
            foreach ($row as $key => $val) {
                if ($this->isJson($val)) {
                    $row[$key] = json_decode($val, true);
                }
            }
        }
        return $data;
    }

    public function save($data) {
        if ($this->tableName === 'settings') {
            foreach ($data as $name => $val) {
                $stmt = $this->pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
                $jsonVal = json_encode($val, JSON_UNESCAPED_UNICODE);
                $stmt->execute([$name, $jsonVal, $jsonVal]);
            }
            return true;
        }
        // Save for other tables usually means reset or specific logic?
        // JsonStore handles save for the whole array. MySQL doesn't usually do that.
        return false;
    }

    public function findById($id, $key = 'id') {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE {$key} = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            foreach ($row as $k => $v) {
                if ($this->isJson($v)) {
                    $row[$k] = json_decode($v, true);
                }
            }
        }
        return $row ?: null;
    }

    public function updateById($id, $newData, $key = 'id') {
        $sets = [];
        $params = [];
        foreach ($newData as $k => $v) {
            if ($k === $key) continue;
            $sets[] = "{$k} = ?";
            $params[] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
        }
        $params[] = $id;
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $sets) . " WHERE {$key} = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteById($id, $key = 'id') {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE {$key} = ?");
        return $stmt->execute([$id]);
    }

    public function add($item) {
        $keys = array_keys($item);
        $placeholders = array_fill(0, count($keys), '?');
        $params = [];
        foreach ($item as $v) {
            $params[] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
        }
        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    private function isJson($string) {
        if (!is_string($string)) return false;
        if (empty($string)) return false;
        if ($string[0] !== '{' && $string[0] !== '[') return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
