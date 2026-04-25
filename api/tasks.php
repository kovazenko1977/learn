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
        if ($user['role'] === 'head') {
            $tasks = array_filter($tasks, function($t) use ($user) {
                return $t['department_id'] == $user['department_id'];
            });
        } elseif ($user['role'] === 'executor') {
            $tasks = array_filter($tasks, function($t) use ($user) {
                return $t['executor_id'] == $user['id'] || $t['department_id'] == $user['department_id'];
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
    Auth::requireLogin();
    $user = Auth::getCurrentUser();
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
            // Check for new message (chat)
            if (isset($data['new_message'])) {
                if (!isset($existing['messages'])) $existing['messages'] = [];
                $existing['messages'][] = [
                    'at' => date('Y-m-d H:i:s'),
                    'user' => $user['full_name'],
                    'user_id' => $user['id'],
                    'text' => $data['new_message']
                ];
                $data = $existing;
                unset($data['new_message']);
            }

            if (isset($data['status']) && $data['status'] !== $existing['status']) {
                // For Heads and Admin changing status, comment might be required
                if ($user['role'] !== 'admin' && empty($data['comment'])) {
                     echo json_encode(['success' => false, 'error' => 'Комментарий обязателен при смене статуса']);
                     exit;
                }

                $msg = "Статус изменен на: " . $data['status'];
                if (!empty($data['comment'])) {
                    $msg .= ". Комментарий: " . $data['comment'];
                }

                $existing['history'][] = [
                    'at' => date('Y-m-d H:i:s'),
                    'msg' => $msg,
                    'user' => $user['full_name']
                ];
                $existing['status'] = $data['status'];

                // Notify creator
                Notifier::send($existing['created_by'], "Статус вашей заявки #{$existing['id']} изменен на {$data['status']}");
                $data = $existing;
            }

            if (isset($data['executor_id']) && $data['executor_id'] !== $existing['executor_id']) {
                 $existing['history'][] = [
                    'at' => date('Y-m-d H:i:s'),
                    'msg' => "Назначен исполнитель: " . ($data['executor_name'] ?? 'ID '.$data['executor_id']),
                    'user' => $user['full_name']
                ];
                $existing['executor_id'] = $data['executor_id'];
                Notifier::send($data['executor_id'], "Вам назначена новая заявка #{$existing['id']}");
                $data = $existing;
            }
        }
    }

    Storage::saveItem('tasks.json', $data);
    echo json_encode(['success' => true, 'id' => $data['id']]);
    exit;
}
