<?php
Auth::requireRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = isset($_GET['type']) ? $_GET['type'] : 'system';
    $settings = Storage::read('settings', $type);
    if (!$settings) {
        if ($type === 'modules') {
            $settings = [
                'finance' => true,
                'tasks' => true,
                'sources' => true,
                'tags' => true,
                'chat' => true
            ];
        } else {
            $settings = [
                'clinic_name' => 'Dental CRM',
                'timezone' => 'UTC'
            ];
        }
    }
    echo json_encode($settings);
} elseif ($method === 'POST') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'reset_data') {
        $storageDir = __DIR__ . '/../storage/';
        $it = new RecursiveDirectoryIterator($storageDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        Logger::log("System reset by admin", "warning", "system.log");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'seed_data') {
        require_once __DIR__ . '/../cron/seed.php';
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'create_backup') {
        require_once __DIR__ . '/../cron/backups.php';
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'cleanup') {
        // Clear versions and logs (simulating cleanup)
        $logs = ['system.log', 'auth.log', 'notifications.log', 'chat.log'];
        foreach($logs as $l) {
            file_put_contents(__DIR__ . '/../storage/logs/' . $l, "");
        }
        Logger::log("System cleanup performed", "info", "system.log");
        echo json_encode(['success' => true, 'message' => 'Логи очищены']);
        exit;
    }

    if ($action === 'health_check') {
        $entities = ['users', 'patients', 'doctors', 'services', 'appointments', 'tasks', 'finance'];
        $report = [];
        foreach($entities as $e) {
            $path = __DIR__ . "/../storage/data/$e";
            $count = is_dir($path) ? count(scandir($path)) - 2 : 0;
            $report[$e] = ['count' => max(0, $count), 'status' => 'OK'];
        }
        echo json_encode(['success' => true, 'report' => $report]);
        exit;
    }

    $type = isset($_GET['type']) ? $_GET['type'] : 'system';
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $input = Security::sanitize($input);
    Storage::write('settings', $type, $input);
    Logger::log("Settings updated: $type", "info", "system.log");
    echo json_encode(['success' => true]);
}
