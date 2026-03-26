<?php
class Storage {
    private $filepath;

    public function __construct($filename) {
        $this->filepath = dirname(__DIR__) . '/data/' . $filename;
        if (!file_exists(dirname($this->filepath))) {
            mkdir(dirname($this->filepath), 0777, true);
        }
        if (!file_exists($this->filepath)) {
            file_put_contents($this->filepath, json_encode([]));
        }
    }

    public function read() {
        $fp = fopen($this->filepath, 'rb');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $data = file_get_contents($this->filepath);
        flock($fp, LOCK_UN);
        fclose($fp);
        return json_decode($data, true) ?: [];
    }

    public function write($data) {
        $fp = fopen($this->filepath, 'cb');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
}
