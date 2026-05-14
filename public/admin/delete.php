<?php
require_once 'auth.php';
requireAdmin();
require_once __DIR__ . '/../../src/JsonStore.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store = new \App\JsonStore(__DIR__ . '/../../data/popups.json');
    $id = $_POST['id'] ?? null;
    if ($id) {
        $popup = $store->getById($id);
        if ($popup && !empty($popup['image'])) {
            $oldImagePath = __DIR__ . '/..' . $popup['image'];
            if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        $store->delete($id);
    }
}

header('Location: index.php');
exit;
