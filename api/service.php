<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAdmin();
$currentUser = Auth::getUser();

$action = $_GET['action'] ?? '';

if ($action === 'clear_logs') {
    Storage::save('logs', []);
    Storage::log("Logs cleared by admin", $currentUser['id']);
    echo json_encode(['success' => true, 'message' => 'Логи успешно очищены']);
} elseif ($action === 'clear_chat') {
    Storage::save('chat', []);
    Storage::log("Chat history cleared by admin", $currentUser['id']);
    echo json_encode(['success' => true, 'message' => 'История чата очищена']);
} elseif ($action === 'cleanup_uploads') {
    $docs = Storage::read('docs');
    $usedFiles = array_column($docs, 'filename');
    $uploadDir = __DIR__ . '/../data/uploads/';
    $files = glob($uploadDir . '*');
    $count = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            $name = basename($file);
            // Don't delete .htaccess or index.php if exists
            if ($name === '.htaccess' || $name === 'index.html' || $name === 'index.php') continue;

            if (!in_array($name, $usedFiles)) {
                unlink($file);
                $count++;
            }
        }
    }
    Storage::log("System cleanup: deleted $count orphaned files", $currentUser['id']);
    echo json_encode(['success' => true, 'message' => "Удалено неиспользуемых файлов: $count"]);
} elseif ($action === 'system_check') {
    $entities = ['clients', 'leads', 'tasks', 'users', 'chat', 'logs', 'docs', 'interactions'];
    $results = [];
    foreach ($entities as $entity) {
        $path = __DIR__ . '/../data/' . $entity . '.json';
        $exists = file_exists($path);
        $readable = $exists ? is_readable($path) : false;
        $writable = $exists ? is_writable($path) : false;
        $results[] = [
            'entity' => $entity,
            'exists' => $exists,
            'readable' => $readable,
            'writable' => $writable,
            'size' => $exists ? round(filesize($path) / 1024, 2) . ' KB' : '0 KB'
        ];
    }
    echo json_encode(['success' => true, 'results' => $results]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
