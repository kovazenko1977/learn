<?php
require_once 'auth.php';
requireAdmin();
require_once __DIR__ . '/src/JsonStore.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }

    $store = new \App\JsonStore(__DIR__ . '/data/popups.json');
    $id = $_POST['id'] ?? null;
    if ($id) {
        $popup = $store->getById($id);
        if ($popup && !empty($popup['image'])) {
            secureUnlink($popup['image']);
        }
        $store->delete($id);
    }
}

header('Location: index.php');
exit;
