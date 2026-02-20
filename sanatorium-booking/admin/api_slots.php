<?php
require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Procedures\ProceduresManager;

header('Content-Type: application/json');

$store = new JsonStore(__DIR__ . '/../data');
$procManager = new ProceduresManager($store);

$procId = isset($_GET['procedure_id']) ? (int)$_GET['procedure_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$procId || !$date) {
    echo json_encode([]);
    exit;
}

$slots = $procManager->getAvailableSlots($procId, $date);
echo json_encode($slots);
