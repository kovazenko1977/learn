<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function checkRole($allowedRoles) {
    if (!isset($_SESSION['user_role'])) {
        header('Location: login.php');
        exit;
    }

    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        die('Доступ запрещен. Ваша роль: ' . $_SESSION['user_role']);
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function checkCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            http_response_code(403);
            die('Ошибка CSRF: Неверный токен безопасности.');
        }
    }
}
