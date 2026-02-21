<?php
require_once 'core/Autoloader.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$notificationStore = new \Hop\Core\JsonStore('data/notifications.json');
$notifications = $notificationStore->read();
$unread = array_filter($notifications, fn($n) => $n['user_id'] == $_SESSION['user_id'] && !$n['read']);

echo json_encode(['count' => count($unread)]);
