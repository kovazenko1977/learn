<?php
$_POST['login'] = 'admin';
$_POST['password'] = 'admin123456';

// Simulate POST request to api/index.php?module=auth&action=login
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['module'] = 'auth';
$_GET['action'] = 'login';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// We need to provide the body
// PHP doesn't easily allow mocking php://input, but we can bypass the router and call the logic if we were testing in a real server.
// Let's just use curl against the running server.
