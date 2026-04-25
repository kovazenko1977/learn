<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

Auth::requireAdmin();

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'clear_all':
        Storage::write('tasks.json', []);
        echo json_encode(['success' => true, 'message' => 'Все задачи удалены']);
        break;

    case 'clear_period':
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['error' => 'Укажите период']);
            break;
        }
        $tasks = Storage::read('tasks.json');
        $filtered = array_filter($tasks, function($t) use ($start, $end) {
            $date = date('Y-m-d', strtotime($t['created_at']));
            return !($date >= $start && $date <= $end);
        });
        Storage::write('tasks.json', array_values($filtered));
        echo json_encode(['success' => true, 'message' => "Задачи за период $start - $end удалены"]);
        break;

    case 'cleanup_temp':
        $files = glob(__DIR__ . '/../uploads/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        echo json_encode(['success' => true, 'message' => 'Временные файлы очищены']);
        break;

    case 'backup':
        $backup = [
            'tasks' => Storage::read('tasks.json'),
            'users' => Storage::read('users.json'),
            'settings' => Storage::read('settings.json'),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents(__DIR__ . '/../data/' . $filename, json_encode($backup, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'backup_file' => $filename]);
        break;

    case 'list_backups':
        $files = glob(__DIR__ . '/../data/backup_*.json');
        $backups = array_map(function($f) {
            return basename($f);
        }, $files);
        echo json_encode($backups);
        break;

    case 'restore':
        $filename = $data['filename'] ?? '';
        $path = __DIR__ . '/../data/' . $filename;
        if (!$filename || !file_exists($path)) {
            http_response_code(404);
            echo json_encode(['error' => 'Файл бэкапа не найден']);
            break;
        }
        $backup = json_decode(file_get_contents($path), true);
        if ($backup) {
            Storage::write('tasks.json', $backup['tasks'] ?? []);
            Storage::write('users.json', $backup['users'] ?? []);
            Storage::write('settings.json', $backup['settings'] ?? []);
            echo json_encode(['success' => true, 'message' => 'Система восстановлена из бэкапа']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Ошибка чтения файла']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Неизвестное действие']);
}
