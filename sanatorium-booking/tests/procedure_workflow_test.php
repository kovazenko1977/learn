<?php
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Procedures\ProceduresManager;

$store = new JsonStore(__DIR__ . '/../data');
$procManager = new ProceduresManager($store);

function assert_true($cond, $msg) {
    if ($cond) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
        exit(1);
    }
}

echo "Starting Procedure Workflow Test...\n";

// 1. Prepare data
$procedureId = 1; // Assuming ID 1 exists
$guestId = 1;     // Assuming ID 1 exists
$date = date('Y-m-d');
$time = "10:00";

// 2. Doctor assigns procedure
echo "Step 1: Doctor assigns procedure...\n";
$assignmentId = $procManager->assignProcedure([
    'guest_id' => $guestId,
    'procedure_id' => $procedureId,
    'date' => $date,
    'time' => $time,
    'status' => 'assigned',
    'price' => 500.0
]);
assert_true($assignmentId > 0, "Assignment created with ID $assignmentId");

// 3. Verify availability (slot 10:00 should be busy)
echo "Step 2: Verifying availability...\n";
$slots = $procManager->getAvailableSlots($procedureId, $date);
assert_true(!in_array($time, $slots), "Time slot $time is now busy");

// 4. Cashier marks as paid
echo "Step 3: Cashier marks as paid...\n";
$res = $procManager->updateAssignmentStatus($assignmentId, 'paid');
assert_true($res, "Status updated to 'paid'");
$a = $procManager->getAssignment($assignmentId);
assert_true($a['status'] === 'paid', "Assignment status is 'paid'");

// 5. Nurse marks as completed
echo "Step 4: Nurse marks as completed...\n";
$res = $procManager->updateAssignmentStatus($assignmentId, 'completed');
assert_true($res, "Status updated to 'completed'");
$a = $procManager->getAssignment($assignmentId);
assert_true($a['status'] === 'completed', "Assignment status is 'completed'");

echo "\nWorkflow Test Passed Successfully!\n";
