<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

$filePath = __DIR__ . '/data/history.json';

// Atomic write using flock
$fp = fopen($filePath, 'c+');
if (flock($fp, LOCK_EX)) {
    $content = stream_get_contents($fp);
    $history = json_decode($content, true) ?: [];

    // Add timestamp and ID
    $data['id'] = uniqid();
    $data['date'] = date('Y-m-d H:i:s');

    array_unshift($history, $data); // Add to the beginning

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not lock file']);
}
fclose($fp);
