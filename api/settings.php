<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';

Auth::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = Storage::read('settings');
    if (empty($settings)) {
        $settings = [
            'categories' => ['IT', 'Ремонт', 'Уборка', 'Охрана'],
            'priorities' => ['Низкий', 'Средний', 'Высокий', 'Критический'],
            'sla' => [
                'Низкий' => 48,
                'Средний' => 24,
                'Высокий' => 8,
                'Критический' => 2
            ],
            'announcement' => 'Добро пожаловать в новую CRM систему v2.0!',
            'mysql_mode' => false,
            'db_host' => 'localhost',
            'db_name' => 'crm_db',
            'db_user' => 'root',
            'db_pass' => '',
            'work_start' => '09:00',
            'work_end' => '18:00',
            'status_labels' => [
                'new' => 'Новая',
                'assigned' => 'Назначена',
                'in_work' => 'В работе',
                'completed' => 'Выполнено',
                'rejected' => 'Отклонено'
            ],
            'form_fields' => [
                ['id' => 'room', 'label' => 'Номер кабинета', 'type' => 'text', 'required' => true],
                ['id' => 'inventory_id', 'label' => 'Инвентарный номер', 'type' => 'text', 'required' => false]
            ]
        ];
    }
    echo json_encode($settings);

} elseif ($method === 'POST') {
    $data = Security::sanitize(json_decode(file_get_contents('php://input'), true));
    Storage::save('settings', $data);
    echo json_encode(['success' => true]);
}
