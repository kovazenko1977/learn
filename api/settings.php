<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAdmin();
$currentUser = Auth::getUser();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = Storage::read('settings');
    if (empty($settings)) {
        $settings = [
            'storage_mode' => 'json',
            'categories' => ['IT', 'Repair', 'Cleaning', 'Security'],
            'priorities' => ['Low', 'Medium', 'High', 'Critical'],
            'sla' => [
                'Low' => 48,
                'Medium' => 24,
                'High' => 4,
                'Critical' => 1
            ]
        ];
    }
    echo json_encode($settings);

} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));

    // Switch to MySQL logic (simplified for now)
    if (($data['storage_mode'] ?? '') === 'mysql') {
        // Implementation for table generation would go here
    }

    Storage::save('settings', $data);
    echo json_encode(['success' => true]);
}
