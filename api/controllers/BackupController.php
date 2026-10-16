<?php
/**
 * BackupController
 */

class BackupController {
    public function handle($method) {
        require_auth(true);

        if ($method === 'GET') {
            $action = $_GET['action'] ?? '';
            if ($action === 'download' || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], 'download'))) {
                $this->downloadBackup();
            } else {
                $this->listBackups();
            }
        } elseif ($method === 'POST') {
            $action = $_GET['action'] ?? '';
            if ($action === 'restore' || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], 'restore'))) {
                $this->restoreBackup();
            } else {
                $this->createBackup();
            }
        } elseif ($method === 'DELETE') {
            $this->deleteBackup();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    public function actionList() {
        require_auth(true);
        $this->listBackups();
    }

    public function actionCreate() {
        require_auth(true);
        $this->createBackup();
    }

    public function actionRestore() {
        require_auth(true);
        $this->restoreBackup();
    }

    public function actionDownload() {
        require_auth(true);
        $this->downloadBackup();
    }

    private function getBackupDir() {
        $dir = __DIR__ . '/../../data/backups/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    private function listBackups() {
        $dir = $this->getBackupDir();
        $files = glob($dir . 'backup_*.json');
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => round(filesize($file) / 1024, 2) . ' KB',
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        json_out(['backups' => $backups]);
    }

    private function createBackup() {
        $dir = $this->getBackupDir();
        $dataDir = __DIR__ . '/../../data/';

        $backupData = [];
        $files = glob($dataDir . '*.json');

        foreach ($files as $f) {
            $key = basename($f, '.json');
            $backupData[$key] = json_decode(file_get_contents($f), true) ?? [];
        }

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($dir . $filename, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Auto rotation: keep max 20 backups
        $allBackups = glob($dir . 'backup_*.json');
        if (count($allBackups) > 20) {
            usort($allBackups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            while (count($allBackups) > 20) {
                $oldest = array_shift($allBackups);
                unlink($oldest);
            }
        }

        json_out(['success' => true, 'filename' => $filename]);
    }

    private function restoreBackup() {
        $data = json_in();
        $filename = clean($data['filename'] ?? '');

        if (!$filename) {
            json_out(['error' => 'Файл бэкапа не указан'], 400);
        }

        $filepath = $this->getBackupDir() . basename($filename);
        if (!file_exists($filepath)) {
            json_out(['error' => 'Файл бэкапа не найден'], 404);
        }

        $content = json_decode(file_get_contents($filepath), true);
        if (!is_array($content)) {
            json_out(['error' => 'Ошибка формата файла бэкапа'], 400);
        }

        $dataDir = __DIR__ . '/../../data/';
        foreach ($content as $table => $rows) {
            file_put_contents($dataDir . $table . '.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        json_out(['success' => true, 'message' => 'Данные успешно восстановлены']);
    }

    private function downloadBackup() {
        $filename = clean($_GET['filename'] ?? $_GET['file'] ?? '');
        if (!$filename) {
            json_out(['error' => 'Укажите filename'], 400);
        }

        $filepath = $this->getBackupDir() . basename($filename);
        if (!file_exists($filepath)) {
            json_out(['error' => 'Файл бэкапа не найден'], 404);
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    private function deleteBackup() {
        $filename = clean($_GET['filename'] ?? $_GET['file'] ?? '');
        if (!$filename) {
            $data = json_in();
            $filename = clean($data['filename'] ?? $data['file'] ?? '');
        }

        if (!$filename) {
            json_out(['error' => 'Имя файла не указано'], 400);
        }

        $filepath = $this->getBackupDir() . basename($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        json_out(['success' => true]);
    }
}
