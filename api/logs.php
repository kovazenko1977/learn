<?php
Auth::requireRole(['admin', 'director', 'support']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = isset($_GET['type']) ? $_GET['type'] : 'system.log';
    $logDir = __DIR__ . '/../storage/logs/';
    $filePath = $logDir . $type;

    if (!file_exists($filePath)) {
        echo json_encode(['content' => [], 'size' => 0]);
        exit;
    }

    // Security check: ensure the file is within the logs directory and has .log extension
    $realPath = realpath($filePath);
    $realLogDir = realpath($logDir);
    if (strpos($realPath, $realLogDir) !== 0 || substr($type, -4) !== '.log') {
        echo json_encode(['error' => 'Invalid log file']);
        exit;
    }

    $lines = array_reverse(file($filePath));
    echo json_encode([
        'content' => array_slice($lines, 0, 200),
        'size' => filesize($filePath)
    ]);
}
