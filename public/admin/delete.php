<?php
require_once __DIR__ . '/../../src/JsonStore.php';
$store = new \App\JsonStore(__DIR__ . '/../../data/popups.json');

$id = $_GET['id'] ?? null;
if ($id) {
    $store->delete($id);
}

header('Location: index.php');
exit;
