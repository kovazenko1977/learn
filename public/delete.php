<?php
require_once 'auth.php';
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;
use App\Helpers\Security;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }

    $store = new JsonStore(__DIR__ . '/../data');
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? '';

    if ($id && $type) {
        if ($type === 'popup') {
            $item = $store->findOne('popups', $id);
            if ($item && !empty($item['image'])) {
                Security::secureUnlink(__DIR__ . '/uploads/' . basename($item['image']));
            }
            $store->delete('popups', $id);
            header('Location: popups.php');
            exit;
        } elseif ($type === 'booking') {
            $store->delete('bookings', $id);
            header('Location: dashboard.php');
            exit;
        }
    }
}

header('Location: index.php');
exit;
