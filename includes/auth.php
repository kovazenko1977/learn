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
