<?php

class Storage {
    private static $base_path = __DIR__ . '/../data/';

    public static function read($file) {
        $path = self::$base_path . $file . '.json';
        if (!file_exists($path)) {
            return [];
        }

        $fp = fopen($path, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $size = filesize($path);
        $data = $size > 0 ? fread($fp, $size) : '';
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($data, true) ?: [];
    }

    public static function write($file, $data) {
        $path = self::$base_path . $file . '.json';
        $fp = fopen($path, 'c+');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        rewind($fp);
        $success = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $success !== false;
    }

    public static function find($file, $criteria) {
        $data = self::read($file);
        return array_filter($data, function($item) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }

    public static function findOne($file, $criteria) {
        $results = self::find($file, $criteria);
        return !empty($results) ? reset($results) : null;
    }

    public static function update($file, $id, $updates, $id_key = 'id') {
        $data = self::read($file);
        $found = false;
        foreach ($data as &$item) {
            if ($item[$id_key] == $id) {
                $item = array_merge($item, $updates);
                $found = true;
                break;
            }
        }
        if ($found) {
            return self::write($file, $data);
        }
        return false;
    }

    public static function insert($file, $item) {
        $data = self::read($file);
        if (!isset($item['id'])) {
            $item['id'] = uniqid();
        }
        $data[] = $item;
        return self::write($file, $data) ? $item['id'] : false;
    }

    public static function delete($file, $id, $id_key = 'id') {
        $data = self::read($file);
        $new_data = array_filter($data, function($item) use ($id, $id_key) {
            return $item[$id_key] != $id;
        });
        if (count($data) !== count($new_data)) {
            return self::write($file, array_values($new_data));
        }
        return false;
    }
}
