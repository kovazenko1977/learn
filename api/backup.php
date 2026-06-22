<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAdmin();

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $zip = new ZipArchive();
    $filename = "backup_" . date('Y-m-d_H-i-s') . ".zip";
    $filePath = __DIR__ . '/../data/uploads/' . $filename;

    if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
        $files = ['clients', 'leads', 'tasks', 'users', 'chat', 'logs', 'interactions', 'docs'];
        foreach ($files as $file) {
            $path = __DIR__ . '/../data/' . $file . '.json';
            if (file_exists($path)) {
                $zip->addFile($path, $file . '.json');
            }
        }
        $zip->close();
        echo json_encode(['success' => true, 'download_url' => 'api/uploads.php?action=download_backup&file=' . $filename]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not create zip']);
    }
}
