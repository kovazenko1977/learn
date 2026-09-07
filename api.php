<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/includes/Storage.php';

$storage = new Storage();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper to send JSON response
function sendJson(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Get request body
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?? $_POST;

switch ($action) {

    // --- PIN SECURITY SYSTEM ---
    case 'verify_pin':
        $pin = $inputData['pin'] ?? '';
        $settings = $storage->getItem('settings', 'app_security');
        $storedPin = $settings['pin'] ?? '1111';
        if ($pin === $storedPin) {
            sendJson(['success' => true, 'message' => 'PIN verified']);
        } else {
            sendJson(['error' => 'Неверный PIN-код'], 401);
        }
        break;

    case 'change_pin':
        $currentPin = $inputData['current_pin'] ?? '';
        $newPin = $inputData['new_pin'] ?? '';

        if (strlen($newPin) < 4) {
            sendJson(['error' => 'PIN-код должен состоять минимум из 4 цифр'], 400);
        }

        $settings = $storage->getItem('settings', 'app_security') ?? ['id' => 'app_security'];
        $storedPin = $settings['pin'] ?? '1111';

        if ($currentPin !== $storedPin) {
            sendJson(['error' => 'Текущий PIN-код указан неверно'], 401);
        }

        $settings['pin'] = $newPin;
        $storage->saveItem('settings', $settings);
        sendJson(['success' => true, 'message' => 'PIN-код успешно изменен']);
        break;


    // --- TASKS MODULE ---
    case 'get_tasks':
        $tasks = $storage->getCollection('tasks');
        sendJson(['success' => true, 'tasks' => $tasks]);
        break;

    case 'save_task':
        if (empty($inputData['title'])) {
            sendJson(['error' => 'Task title is required'], 400);
        }
        $task = [
            'id' => $inputData['id'] ?? null,
            'title' => trim($inputData['title']),
            'description' => trim($inputData['description'] ?? ''),
            'completed' => !empty($inputData['completed']),
            'priority' => $inputData['priority'] ?? 'medium', // low, medium, high, urgent
            'category' => $inputData['category'] ?? 'Личное',
            'due_date' => $inputData['due_date'] ?? null, // YYYY-MM-DD
            'due_time' => $inputData['due_time'] ?? null, // HH:MM
            'subtasks' => is_array($inputData['subtasks'] ?? null) ? $inputData['subtasks'] : [],
            'reminder' => !empty($inputData['reminder']),
            'reminder_sound' => $inputData['reminder_sound'] ?? 'chime',
            'voice_note_url' => $inputData['voice_note_url'] ?? null
        ];
        $saved = $storage->saveItem('tasks', $task);
        sendJson(['success' => true, 'task' => $saved]);
        break;

    case 'delete_task':
        $id = $inputData['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            sendJson(['error' => 'Missing task ID'], 400);
        }
        $res = $storage->deleteItem('tasks', $id);
        sendJson(['success' => $res]);
        break;

    case 'toggle_task':
        $id = $inputData['id'] ?? null;
        if (!$id) sendJson(['error' => 'Missing task ID'], 400);
        $task = $storage->getItem('tasks', $id);
        if (!$task) sendJson(['error' => 'Task not found'], 404);
        $task['completed'] = !$task['completed'];
        $storage->saveItem('tasks', $task);
        sendJson(['success' => true, 'task' => $task]);
        break;


    // --- PLANNER / DAILY SCHEDULE & HABITS ---
    case 'get_planner':
        $date = $_GET['date'] ?? date('Y-m-d');
        $allPlanner = $storage->getCollection('planner');
        $dayPlanner = array_values(array_filter($allPlanner, fn($p) => isset($p['date']) && $p['date'] === $date));
        $habits = $storage->getCollection('habits');
        sendJson([
            'success' => true,
            'date' => $date,
            'planner' => $dayPlanner,
            'habits' => $habits
        ]);
        break;

    case 'save_planner_event':
        if (empty($inputData['title']) || empty($inputData['date'])) {
            sendJson(['error' => 'Title and date are required'], 400);
        }
        $event = [
            'id' => $inputData['id'] ?? null,
            'date' => $inputData['date'],
            'time' => $inputData['time'] ?? '09:00',
            'duration' => (int)($inputData['duration'] ?? 30),
            'title' => trim($inputData['title']),
            'description' => trim($inputData['description'] ?? ''),
            'completed' => !empty($inputData['completed']),
            'color' => $inputData['color'] ?? '#4f46e5'
        ];
        $saved = $storage->saveItem('planner', $event);
        sendJson(['success' => true, 'event' => $saved]);
        break;

    case 'delete_planner_event':
        $id = $inputData['id'] ?? $_GET['id'] ?? null;
        if (!$id) sendJson(['error' => 'Missing ID'], 400);
        $res = $storage->deleteItem('planner', $id);
        sendJson(['success' => $res]);
        break;

    case 'save_habit':
        if (empty($inputData['title'])) {
            sendJson(['error' => 'Habit title is required'], 400);
        }
        $habit = [
            'id' => $inputData['id'] ?? null,
            'title' => trim($inputData['title']),
            'icon' => $inputData['icon'] ?? '⚡',
            'logs' => is_array($inputData['logs'] ?? null) ? $inputData['logs'] : [] // Array of 'YYYY-MM-DD' dates
        ];
        $saved = $storage->saveItem('habits', $habit);
        sendJson(['success' => true, 'habit' => $saved]);
        break;

    case 'toggle_habit_log':
        $id = $inputData['id'] ?? null;
        $date = $inputData['date'] ?? date('Y-m-d');
        if (!$id) sendJson(['error' => 'Missing habit ID'], 400);
        $habit = $storage->getItem('habits', $id);
        if (!$habit) sendJson(['error' => 'Habit not found'], 404);

        $logs = $habit['logs'] ?? [];
        if (in_array($date, $logs)) {
            $logs = array_values(array_filter($logs, fn($d) => $d !== $date));
        } else {
            $logs[] = $date;
        }
        $habit['logs'] = $logs;
        $storage->saveItem('habits', $habit);
        sendJson(['success' => true, 'habit' => $habit]);
        break;

    case 'delete_habit':
        $id = $inputData['id'] ?? $_GET['id'] ?? null;
        if (!$id) sendJson(['error' => 'Missing habit ID'], 400);
        $res = $storage->deleteItem('habits', $id);
        sendJson(['success' => $res]);
        break;


    // --- NOTES MODULE ---
    case 'get_notes':
        $notes = $storage->getCollection('notes');
        sendJson(['success' => true, 'notes' => $notes]);
        break;

    case 'save_note':
        if (empty($inputData['title']) && empty($inputData['content']) && empty($inputData['audio_url'])) {
            sendJson(['error' => 'Note cannot be completely empty'], 400);
        }
        $note = [
            'id' => $inputData['id'] ?? null,
            'title' => trim($inputData['title'] ?? 'Без названия'),
            'content' => trim($inputData['content'] ?? ''),
            'category' => $inputData['category'] ?? 'Заметки',
            'pinned' => !empty($inputData['pinned']),
            'color' => $inputData['color'] ?? '#ffffff',
            'tags' => is_array($inputData['tags'] ?? null) ? $inputData['tags'] : [],
            'audio_url' => $inputData['audio_url'] ?? null,
            'transcript' => $inputData['transcript'] ?? null
        ];
        $saved = $storage->saveItem('notes', $note);
        sendJson(['success' => true, 'note' => $saved]);
        break;

    case 'delete_note':
        $id = $inputData['id'] ?? $_GET['id'] ?? null;
        if (!$id) sendJson(['error' => 'Missing note ID'], 400);
        $res = $storage->deleteItem('notes', $id);
        sendJson(['success' => $res]);
        break;


    // --- VOICE AUDIO UPLOAD ---
    case 'upload_audio':
        if (!isset($_FILES['audio'])) {
            sendJson(['error' => 'No audio file uploaded'], 400);
        }
        $file = $_FILES['audio'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            sendJson(['error' => 'Upload failed with code ' . $file['error']], 500);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['webm', 'mp3', 'wav', 'ogg', 'm4a', 'aac', '3gp'])) {
            $ext = 'webm';
        }
        $filename = 'voice_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $uploadPath = __DIR__ . '/uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $publicUrl = 'uploads/' . $filename;
            sendJson(['success' => true, 'url' => $publicUrl, 'filename' => $filename]);
        } else {
            sendJson(['error' => 'Failed to move uploaded audio file'], 500);
        }
        break;


    // --- STATS & ANALYTICS ---
    case 'get_stats':
        $tasks = $storage->getCollection('tasks');
        $planner = $storage->getCollection('planner');
        $habits = $storage->getCollection('habits');
        $notes = $storage->getCollection('notes');

        $totalTasks = count($tasks);
        $completedTasks = count(array_filter($tasks, fn($t) => !empty($t['completed'])));
        $taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $totalPlanner = count($planner);
        $completedPlanner = count(array_filter($planner, fn($p) => !empty($p['completed'])));

        // Habit statistics
        $habitStats = [];
        $today = date('Y-m-d');
        foreach ($habits as $h) {
            $logs = $h['logs'] ?? [];
            $streak = 0;
            $checkDate = new DateTime();
            while (true) {
                $dStr = $checkDate->format('Y-m-d');
                if (in_array($dStr, $logs)) {
                    $streak++;
                    $checkDate->modify('-1 day');
                } else {
                    // Check if today was missed but yesterday was logged
                    if ($dStr === $today && $streak === 0) {
                        $checkDate->modify('-1 day');
                        continue;
                    }
                    break;
                }
            }
            $habitStats[] = [
                'id' => $h['id'],
                'title' => $h['title'],
                'icon' => $h['icon'] ?? '⚡',
                'streak' => $streak,
                'completed_today' => in_array($today, $logs)
            ];
        }

        sendJson([
            'success' => true,
            'summary' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'task_completion_rate' => $taskCompletionRate,
                'total_planner_events' => $totalPlanner,
                'completed_planner_events' => $completedPlanner,
                'total_notes' => count($notes),
                'total_habits' => count($habits)
            ],
            'habit_stats' => $habitStats
        ]);
        break;


    // --- EXPORT & IMPORT DATA BACKUP ---
    case 'export_data':
        $data = [
            'tasks' => $storage->getCollection('tasks'),
            'planner' => $storage->getCollection('planner'),
            'habits' => $storage->getCollection('habits'),
            'notes' => $storage->getCollection('notes'),
            'settings' => $storage->getCollection('settings'),
            'export_date' => date('Y-m-d H:i:s')
        ];
        sendJson(['success' => true, 'data' => $data]);
        break;

    case 'import_data':
        $importData = $inputData['data'] ?? null;
        if (!is_array($importData)) {
            sendJson(['error' => 'Invalid backup data format'], 400);
        }
        if (isset($importData['tasks']) && is_array($importData['tasks'])) {
            $storage->saveCollection('tasks', $importData['tasks']);
        }
        if (isset($importData['planner']) && is_array($importData['planner'])) {
            $storage->saveCollection('planner', $importData['planner']);
        }
        if (isset($importData['habits']) && is_array($importData['habits'])) {
            $storage->saveCollection('habits', $importData['habits']);
        }
        if (isset($importData['notes']) && is_array($importData['notes'])) {
            $storage->saveCollection('notes', $importData['notes']);
        }
        if (isset($importData['settings']) && is_array($importData['settings'])) {
            $storage->saveCollection('settings', $importData['settings']);
        }
        sendJson(['success' => true, 'message' => 'Data imported successfully']);
        break;

    default:
        sendJson(['error' => 'Unknown or missing action parameter'], 400);
        break;
}
