<?php
// launcher.php - Check backend status and environment
$backend_url = "http://localhost:8000/health";
$status = @file_get_contents($backend_url);
$is_backend_up = ($status !== false);

header('Content-Type: application/json');
echo json_encode([
    'backend_status' => $is_backend_up ? 'running' : 'stopped',
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
    'message' => $is_backend_up ? 'System ready' : 'Please start the Python backend (python main.py)'
]);
?>
