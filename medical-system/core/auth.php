<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $user = getCurrentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    if (!in_array($user['role'], $roles) && $user['role'] !== 'admin') {
        die('Доступ запрещен. Требуемая роль: ' . implode(', ', $roles));
    }
}

function checkCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF validation failed.');
        }
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
