<?php
// api/controllers/BackupController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class BackupController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function list() {
        require_admin();
        $files = glob(BACKUP_DIR . '/*.json');
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        json_out($backups);
    }

    public function create() {
        require_admin();

        $entities = ['users', 'products', 'orders', 'promos', 'promocodes', 'news', 'chats', 'stats', 'settings', 'notifications'];
        $exportData = [];

        foreach ($entities as $entity) {
            $exportData[$entity] = $this->storage->all($entity);
        }

        $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
        $filePath = BACKUP_DIR . '/' . $backupFileName;

        file_put_contents($filePath, json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Auto rotation: keep maximum 20 backups
        $files = glob(BACKUP_DIR . '/*.json');
        if (count($files) > 20) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b); // oldest first
            });
            while (count($files) > 20) {
                $oldest = array_shift($files);
                @unlink($oldest);
            }
        }

        json_out([
            'success' => true,
            'message' => 'Резервная копия успешно создана',
            'filename' => $backupFileName
        ], 201);
    }

    public function restore() {
        require_admin();
        $data = json_in();
        $filename = basename($data['filename'] ?? '');

        $filePath = BACKUP_DIR . '/' . $filename;
        if (empty($filename) || !file_exists($filePath)) {
            json_out(['error' => 'Файл бэкапа не найден'], 404);
        }

        $content = file_get_contents($filePath);
        $importedData = json_decode($content, true);

        if (!is_array($importedData)) {
            json_out(['error' => 'Некорректный формат файла резервной копии'], 400);
        }

        // Restore json storage files
        foreach ($importedData as $entity => $items) {
            if (is_array($items)) {
                $driver = new JsonDriver();
                $driver->saveAll($entity, $items);
            }
        }

        // Reinit storage driver
        $this->storage->initDriver();

        json_out([
            'success' => true,
            'message' => 'Данные успешно восстановлены из резервной копии'
        ]);
    }

    public function download($filename = null) {
        require_admin();
        $cleanName = basename($filename ?? $_GET['file'] ?? '');
        $filePath = BACKUP_DIR . '/' . $cleanName;

        if (empty($cleanName) || !file_exists($filePath)) {
            json_out(['error' => 'Файл резервной копии не найден'], 404);
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $cleanName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function delete($filename = null) {
        require_admin();
        $cleanName = basename($filename ?? $_GET['file'] ?? '');
        $filePath = BACKUP_DIR . '/' . $cleanName;

        if (empty($cleanName) || !file_exists($filePath)) {
            json_out(['error' => 'Файл бэкапа не найден'], 404);
        }

        @unlink($filePath);
        json_out(['success' => true, 'message' => 'Резервная копия удалена']);
    }
}
