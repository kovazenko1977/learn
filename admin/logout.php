<?php
session_start();
require_once __DIR__ . '/../includes/AuthManager.php';
AuthManager::logout();
header('Location: login.php');
exit;
