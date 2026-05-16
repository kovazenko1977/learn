<?php
require_once "auth.php";
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = (int)$_POST['id'];
    $guest = $store->findOne('guests', $id);
    if ($guest) {
        $guest['name'] = $_POST['name'];
        $guest['phone'] = $_POST['phone'];
        $guest['citizenship'] = $_POST['citizenship'];
        $guest['address'] = $_POST['address'];
        $store->save('guests', $guest);
    }
}

header('Location: guests.php');
exit;
