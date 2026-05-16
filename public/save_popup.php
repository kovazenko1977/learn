<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;

session_start();
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store = new JsonStore(__DIR__ . '/../data');

    $data = [
        'code' => $_POST['code'],
        'title' => $_POST['title'],
        'content' => $_POST['content'],
        'animation' => $_POST['animation'],
        'image' => $_POST['image']
    ];

    if (!empty($_POST['id'])) {
        $data['id'] = (int)$_POST['id'];
    }

    $store->save('popups', $data);
}

header('Location: popups.php');
