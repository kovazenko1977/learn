<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/storage.php';
require_once __DIR__ . '/../api/drivers/JsonDriver.php';
require_once __DIR__ . '/../api/helpers.php';

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $testName) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passed++;
    } else {
        echo " [FAIL] $testName\n";
        $failed++;
    }
}

echo "=== STARTING CRM BACKEND TESTS ===\n\n";

$jsonDriver = new JsonDriver(__DIR__ . '/../data');
assertTest($jsonDriver->testConnection(), "JsonDriver testConnection returns true");

$users = $jsonDriver->getAll('users');
assertTest(count($users) >= 4, "JsonDriver loaded at least 4 seeded users");

$adminUser = $jsonDriver->findOneBy('users', ['username' => 'admin']);
assertTest($adminUser !== null && $adminUser['role'] === 'admin', "JsonDriver finds admin by username");

$payload = ['sub' => $adminUser['id'], 'username' => 'admin', 'role' => 'admin'];
$token = AuthHelper::generateToken($payload, 3600);
assertTest(!empty($token) && substr_count($token, '.') === 2, "JWT Token generated successfully with 3 parts");

$validated = AuthHelper::validateToken($token);
assertTest($validated !== null && $validated['sub'] === $adminUser['id'] && $validated['username'] === 'admin', "JWT Token validated and decoded payload matches");

$invalidToken = $token . 'tampered';
assertTest(AuthHelper::validateToken($invalidToken) === null, "Tampered JWT token rejected");

assertTest(password_verify('admin123', $adminUser['password']), "Admin password 'admin123' verifies against bcrypt hash");

$executorUser = $jsonDriver->findOneBy('users', ['username' => 'executor']);
assertTest(password_verify('executor123', $executorUser['password']), "Executor password 'executor123' verifies against bcrypt hash");

$initialTicketsCount = count($jsonDriver->getAll('tickets'));

$newTicket = [
    'ticket_number' => 'TEST-2025-999',
    'title' => 'Тестовая аварийная заявка',
    'description' => 'Проверка тестового пайплайна',
    'category' => 'cat_electric',
    'priority' => 'critical',
    'status' => 'new',
    'requester_id' => 5,
    'requester_name' => 'Елена Новикова',
    'executor_id' => null,
    'executor_name' => null,
    'custom_fields' => ['room_number' => 'Кабинет 101'],
    'attachments' => [],
    'sla_hours' => 2,
    'deadline' => date('Y-m-d H:i:s', time() + 7200),
    'completed_at' => null,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$insertedTicket = $jsonDriver->insert('tickets', $newTicket);
assertTest(!empty($insertedTicket['id']), "Ticket inserted with generated ID: " . ($insertedTicket['id'] ?? 'none'));

$foundTicket = $jsonDriver->findById('tickets', $insertedTicket['id']);
assertTest($foundTicket !== null && $foundTicket['ticket_number'] === 'TEST-2025-999', "Ticket found by ID");

$updatedTicket = $jsonDriver->update('tickets', $insertedTicket['id'], [
    'status' => 'assigned',
    'executor_id' => $executorUser['id'],
    'executor_name' => $executorUser['name']
]);
assertTest($updatedTicket['status'] === 'assigned' && (int)$updatedTicket['executor_id'] === (int)$executorUser['id'], "Ticket updated to assigned");

$completedTicket = $jsonDriver->update('tickets', $insertedTicket['id'], [
    'status' => 'completed',
    'completed_at' => date('Y-m-d H:i:s')
]);
assertTest($completedTicket['status'] === 'completed' && !empty($completedTicket['completed_at']), "Ticket marked completed with completed_at timestamp");

$deleted = $jsonDriver->delete('tickets', $insertedTicket['id']);
assertTest($deleted, "Test ticket cleaned up/deleted successfully");
assertTest(count($jsonDriver->getAll('tickets')) === $initialTicketsCount, "Total ticket count returned to baseline");

$fields = $jsonDriver->getAll('fields');
assertTest(!empty($fields), "Dynamic fields loaded from fields.json");

$testField = [
    'id' => 'field_test_unit',
    'name' => 'test_unit_field',
    'label' => 'Тестовое поле',
    'type' => 'text',
    'required' => false,
    'placeholder' => 'unit-test',
    'options' => [],
    'order' => 99,
    'active' => true
];
$jsonDriver->insert('fields', $testField);
$foundField = $jsonDriver->findById('fields', 'field_test_unit');
assertTest($foundField !== null && $foundField['name'] === 'test_unit_field', "Custom dynamic field created");

$jsonDriver->delete('fields', 'field_test_unit');
assertTest($jsonDriver->findById('fields', 'field_test_unit') === null, "Custom dynamic field deleted");

echo "\n========================================\n";
echo "TEST RESULTS: Passed: $passed, Failed: $failed\n";
echo "========================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
