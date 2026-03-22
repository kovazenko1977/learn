<?php
class Storage {
    private $directory;
    private $uploads_directory;

    public function __construct() {
        $root = dirname(__DIR__);
        $this->directory = $root . '/data/';
        $this->uploads_directory = $root . '/uploads/';

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
        if (!is_dir($this->uploads_directory)) {
            mkdir($this->uploads_directory, 0755, true);
        }

        // Secure data directory
        $htaccess = $this->directory . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all");
        }
    }

    public function read($filename) {
        $path = $this->directory . $filename . '.json';
        if (!file_exists($path)) {
            return [];
        }

        $fp = fopen($path, 'r');
        if (!$fp) return [];

        flock($fp, LOCK_SH);
        $content = file_get_contents($path);
        flock($fp, LOCK_UN);
        fclose($fp);

        return json_decode($content, true) ?: [];
    }

    public function write($filename, $data) {
        $path = $this->directory . $filename . '.json';
        $fp = fopen($path, 'c+');
        if (!$fp) return false;

        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    public function generateId() {
        return bin2hex(random_bytes(8));
    }

    public function getUploadDir() {
        return $this->uploads_directory;
    }
}
