<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Storage.php';

$storage = new Storage('settings.json');
$settings = $storage->getAll();

// Remove sensitive data before sending to public
unset($settings['admin_password']);
unset($settings['admin_password_hash']);

echo json_encode($settings);
