<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Notifier.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    Auth::requireLogin();
    $user = Auth::getCurrentUser();
    $tasks = Storage::read('tasks.json');

    if (!Auth::isAdmin()) {
        if (Auth::isHeadOfDepartment()) {
            $tasks = array_filter($tasks, function($t) use ($user) {
                return $t['department_id'] == $user['department_id'];
            });
        } elseif (Auth::isExecutor()) {
            $tasks = array_filter($tasks, function($t) use ($user) {
                return $t['executor_id'] == $user['id'];
            });
        } else {
            // Employee sees their own
            $tasks = array_filter($tasks, function($t) use ($user) {
                return $t['created_by'] == $user['id'];
            });
        }
    }

    echo json_encode(array_values($tasks));
    exit;
}

if ($method === 'POST') {
    // Can be employee (from index.php) or admin
    $user = Auth::authenticate(); // Optional for public submissions if allowed, but here we require login
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        exit;
    }

    if (!isset($data['id'])) {
        $data['id'] = time();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = 'new';
        $data['created_by'] = $user ? $user['id'] : 0;
        $data['deadline'] = SLAProvider::calculateDeadline($data['priority'] ?? 'medium');
        $data['history'] = [
            ['at' => $data['created_at'], 'msg' => 'Заявка создана', 'user' => $user ? $user['full_name'] : 'System']
        ];
    } else {
        Auth::requireLogin();
        $existing = Storage::getById('tasks.json', $data['id']);
        if ($existing) {
            if (isset($data['status']) && $data['status'] !== $existing['status']) {
                $data['history'][] = [
                    'at' => date('Y-m-d H:i:s'),
                    'msg' => "Статус изменен на: " . $data['status'],
                    'user' => $user['full_name']
                ];
                // Notify creator
                Notifier::send($existing['created_by'], "Статус вашей заявки #{$existing['id']} изменен на {$data['status']}");
            }
            if (isset($data['executor_id']) && $data['executor_id'] !== $existing['executor_id']) {
                 $data['history'][] = [
                    'at' => date('Y-m-d H:i:s'),
                    'msg' => "Назначен исполнитель",
                    'user' => $user['full_name']
                ];
                Notifier::send($data['executor_id'], "Вам назначена новая заявка #{$existing['id']}");
            }
        }
    }

    Storage::saveItem('tasks.json', $data);
    echo json_encode(['success' => true, 'id' => $data['id']]);
    exit;
}
