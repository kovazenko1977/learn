<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$storage = new Storage('registrations.json');
$regs = $storage->getAll();

foreach ($regs as $reg) {
    if ($reg['id'] === $id) {
        // Return only necessary info
        echo json_encode([
            'success' => true,
            'status' => $reg['status'],
            'discount' => $reg['discount'],
            'name' => $reg['name'] ?? ''
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Not found']);
