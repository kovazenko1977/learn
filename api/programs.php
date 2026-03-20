<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/ProgramManager.php';

$programs = ProgramManager::getAll();
echo json_encode(array_values($programs));
