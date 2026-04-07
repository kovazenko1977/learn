<?php
require_once __DIR__ . '/../includes/Security.php';

Security::checkAuth();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$filesDataFile = __DIR__ . '/../data/files.json';

if ($action === 'list') {
    $files = json_decode(file_get_contents($filesDataFile), true) ?: [];
    echo json_encode($files);
} elseif ($action === 'groups') {
    $groupsFile = __DIR__ . '/../data/groups.json';
    $groups = json_decode(file_get_contents($groupsFile), true) ?: ["Общее"];
    if (!in_array('Общее', $groups)) {
        array_unshift($groups, 'Общее');
    }
    echo json_encode(array_values($groups));
} elseif ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    $files = json_decode(file_get_contents($filesDataFile), true) ?: [];
    $found = false;
    foreach ($files as $key => $file) {
        if ($file['id'] === $id) {
            $filePath = __DIR__ . '/../data/uploads/' . $file['fileName'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            array_splice($files, $key, 1);
            $found = true;
            Security::log("File deleted: " . $file['name']);
            break;
        }
    }
    if ($found) {
        file_put_contents($filesDataFile, json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'File not found']);
    }
} elseif ($action === 'update_description') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $description = $data['description'] ?? '';
    $files = json_decode(file_get_contents($filesDataFile), true) ?: [];
    $found = false;
    foreach ($files as &$file) {
        if ($file['id'] === $id) {
            $file['description'] = $description;
            $found = true;
            Security::log("Description updated for file: " . $file['name']);
            break;
        }
    }
    if ($found) {
        file_put_contents($filesDataFile, json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'File not found']);
    }
} elseif ($action === 'stats') {
    $files = json_decode(file_get_contents($filesDataFile), true) ?: [];
    $totalSize = 0;
    foreach ($files as $file) {
        $totalSize += $file['size'];
    }
    echo json_encode([
        'totalFiles' => count($files),
        'totalSize' => $totalSize
    ]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
