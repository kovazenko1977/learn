<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';

$phone = $_GET['phone'] ?? '';
if (!$phone) {
    echo json_encode(['success' => false, 'message' => 'Missing phone']);
    exit;
}

// Clean phone for comparison
$cleanPhone = preg_replace('/[^\d]/', '', $phone);

$storage = new Storage('registrations.json');
$regs = $storage->getAll();

foreach ($regs as $reg) {
    $regPhone = preg_replace('/[^\d]/', '', $reg['phone'] ?? '');
    if ($regPhone === $cleanPhone) {
        echo json_encode([
            'success' => true,
            'exists' => true,
            'status' => $reg['status'],
            'discount' => $reg['discount'],
            'name' => $reg['name'] ?? ''
        ]);
        exit;
    }
}

echo json_encode(['success' => true, 'exists' => false]);
