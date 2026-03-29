<?php
class Storage {
    private $filePath;

    public function __construct($filename) {
        $this->filePath = dirname(__DIR__) . '/data/' . $filename . '.json';
        if (!file_exists(dirname($this->filePath))) {
            mkdir(dirname($this->filePath), 0777, true);
        }
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function read() {
        $fp = fopen($this->filePath, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = file_get_contents($this->filePath);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($content, true) ?: [];
    }

    public function write($data) {
        $fp = fopen($this->filePath, 'w');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }
}
