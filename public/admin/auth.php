<?php
session_start();

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}
