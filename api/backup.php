<?php
require_once __DIR__ . '/../includes/Auth.php';
header('Content-Type: application/json');

Auth::requireRole(['superadmin']);

$action = $_GET['action'] ?? '';

$mapping = [
    'users' => 'users.json',
    'orders' => 'orders.json',
    'pricelist' => 'pricelist.json',
    'messages' => 'messages.json',
    'logs' => 'logs.json',
    'settings' => 'settings.json'
];

switch ($action) {
    case 'export':
        $selected = $_POST['entities'] ?? [];
        if (empty($selected)) {
            $selected = json_decode(file_get_contents('php://input'), true)['entities'] ?? [];
        }

        if (empty($selected)) {
            http_response_code(400);
            echo json_encode(['error' => 'No entities selected']);
            exit;
        }

        $zip = new ZipArchive();
        $zipName = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            echo json_encode(['error' => 'Cannot create zip file']);
            exit;
        }

        foreach ($selected as $key) {
            if (isset($mapping[$key])) {
                $filePath = __DIR__ . '/../data/' . $mapping[$key];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $mapping[$key]);
                }
            }
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        Security::log('backup_export', $_SESSION['user_id'], 'system', ['entities' => $selected]);
        exit;

    case 'import':
        if (!isset($_FILES['backup_file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            break;
        }

        $file = $_FILES['backup_file'];
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                // Security check: only allow mapped filenames
                if (in_array($filename, array_values($mapping))) {
                    $zip->extractTo(__DIR__ . '/../data/', $filename);
                }
            }
            $zip->close();
            Security::log('backup_import', $_SESSION['user_id'], 'system');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to open ZIP archive']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
