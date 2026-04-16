<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAuth();

$action = $_GET['action'] ?? '';

if ($action === 'upload') {
    if (!isset($_FILES['file'])) {
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    $filename = uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../data/uploads/';

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $docs = Storage::read('docs');
        $docEntry = [
            'id' => uniqid(),
            'name' => $file['name'],
            'filename' => $filename,
            'client_id' => $_POST['client_id'] ?? null,
            'category' => $_POST['category'] ?? 'General',
            'date' => date('Y-m-d H:i:s')
        ];
        $docs[] = $docEntry;
        Storage::save('docs', $docs);
        Storage::log("Uploaded document: " . $file['name']);
        echo json_encode(['success' => true, 'doc' => $docEntry]);
    } else {
        echo json_encode(['error' => 'Upload failed']);
    }
} elseif ($action === 'list') {
    $client_id = $_GET['client_id'] ?? null;
    $docs = Storage::read('docs');
    if ($client_id) {
        $docs = array_filter($docs, function($d) use ($client_id) { return $d['client_id'] === $client_id; });
    }
    echo json_encode(array_values($docs));
} elseif ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    $docs = Storage::read('docs');
    foreach ($docs as $key => $doc) {
        if ($doc['id'] === $id) {
            $filePath = __DIR__ . '/../data/uploads/' . $doc['filename'];
            if (file_exists($filePath)) unlink($filePath);
            unset($docs[$key]);
            break;
        }
    }
    Storage::save('docs', array_values($docs));
    echo json_encode(['success' => true]);
} elseif ($action === 'download') {
    $id = $_GET['id'] ?? '';
    $docs = Storage::read('docs');
    foreach ($docs as $doc) {
        if ($doc['id'] === $id) {
            $filePath = __DIR__ . '/../data/uploads/' . $doc['filename'];
            if (file_exists($filePath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $doc['name'] . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            }
        }
    }
    http_response_code(404);
    echo "File not found";
} elseif ($action === 'download_backup') {
    Auth::requireAdmin();
    $file = $_GET['file'] ?? '';
    if (empty($file) || !preg_match('/^backup_.*\.zip$/', $file)) {
        http_response_code(400);
        exit("Invalid backup file");
    }

    $filePath = __DIR__ . '/../data/uploads/' . $file;
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        // Optional: delete after download
        // unlink($filePath);
        exit;
    }
    http_response_code(404);
    echo "Backup not found";
}
