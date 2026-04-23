<?php
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Storage.php';

function testSLA() {
    echo "Testing SLA Calculation...\n";
    $deadline = SLAProvider::calculateDeadline('Medium', 'IT');
    echo "Calculated deadline: $deadline\n";
    if ($deadline) echo "[OK] SLA logic executed.\n";
}

testSLA();
