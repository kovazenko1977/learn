<?php

class Storage {
    private static $instance = null;
    private $dataDir;
    private $settings = null;

    private function __construct() {
        $this->dataDir = dirname(__DIR__) . '/data/';
        $this->loadSettings();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Storage();
        }
        return self::$instance;
    }

    private function loadSettings() {
        $settingsPath = $this->dataDir . 'settings.json';
        if (file_exists($settingsPath)) {
            $this->settings = json_decode(file_get_contents($settingsPath), true);
        }
    }

    public function getSettings() {
        return $this->settings;
    }

    public function read($file) {
        $path = $this->dataDir . $file . '.json';
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?: [];
    }

    public function write($file, $data) {
        $path = $this->dataDir . $file . '.json';
        $tempPath = $path . '.tmp';

        if (file_put_contents($tempPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            return false;
        }

        if (!rename($tempPath, $path)) {
            unlink($tempPath);
            return false;
        }

        return true;
    }

    public function atomicUpdate($file, $callback) {
        $data = $this->read($file);
        $updatedData = $callback($data);
        return $this->write($file, $updatedData);
    }
}
