<?php
class Storage {
    private $path;

    public function __construct($filename) {
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        $this->path = $dataDir . '/' . $filename;
        if (!file_exists($this->path)) {
            file_put_contents($this->path, json_encode([]));
        }
    }

    public function read() {
        $data = file_get_contents($this->path);
        return json_decode($data, true) ?: [];
    }

    public function write($data) {
        $fp = fopen($this->path, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }
        fclose($fp);
        return false;
    }
}
