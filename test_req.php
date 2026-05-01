<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Storage.php';

$token = Auth::generateToken(['id' => 'u4', 'role' => 'employee', 'name' => 'User', 'department_id' => 'd1']);
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'create';

// Mock php://input by defining it globally or similar is hard in CLI without server
// But we can modify api/requests.php to accept a variable if it exists for testing
?>
