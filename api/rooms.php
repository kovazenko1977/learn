<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/SanatoriumManager.php';

$rooms = SanatoriumManager::getRooms();
echo json_encode(array_values($rooms));
