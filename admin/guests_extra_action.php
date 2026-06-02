<?php
require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Guests\GuestManager;

$store = new JsonStore(__DIR__ . '/../data');
$guestManager = new GuestManager($store);

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;

if ($action === 'toggle_blacklist' && $id) {
    $reason = $_POST['reason'] ?? '';
    $guestManager->toggleBlacklist($id, $reason);
}

header('Location: guests.php');
