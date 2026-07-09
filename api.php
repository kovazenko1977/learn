<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Session configuration
session_name('PWA_SESSION_SECURE');
session_start();

$usersFile = __DIR__ . '/data/users.json';
$notesFile = __DIR__ . '/data/notes.json';
$tasksFile = __DIR__ . '/data/tasks.json';
$settingsFile = __DIR__ . '/data/settings.json';
$discussionsFile = __DIR__ . '/data/discussions.json';
$uploadsDir = __DIR__ . '/uploads';

// Utility to read JSON
function readJsonFile($filePath, $default = []) {
    if (!file_exists($filePath)) {
        return $default;
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

// Utility to write JSON with dynamic creation and lock protection
function writeJsonFile($filePath, $data) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fp = fopen($filePath, 'w');
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    }
    return false;
}

// Helper to send json response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Check PIN helper
function authenticate() {
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    // Token/Header Authorization fallback
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s(\d+)/i', $authHeader, $matches)) {
        $_SESSION['user_id'] = $matches[1];
        return $matches[1];
    }
    jsonResponse(['error' => 'Unauthorized. Please login with PIN code.'], 401);
}

// Read raw body
$rawBody = file_get_contents('php://input');
$requestData = json_decode($rawBody, true) ?? [];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $pin = $requestData['pin'] ?? '';
        if (empty($pin)) {
            jsonResponse(['error' => 'PIN is required'], 400);
        }
        $users = readJsonFile($usersFile, []);
        $foundUser = null;
        foreach ($users as $user) {
            if ($user['pin'] === $pin) {
                $foundUser = $user;
                break;
            }
        }
        if ($foundUser) {
            $_SESSION['user_id'] = $foundUser['id'];
            $_SESSION['user_name'] = $foundUser['name'];
            jsonResponse([
                'success' => true,
                'user' => [
                    'id' => $foundUser['id'],
                    'name' => $foundUser['name']
                ]
            ]);
        } else {
            jsonResponse(['error' => 'Invalid PIN code'], 401);
        }
        break;

    case 'logout':
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'check_session':
        if (isset($_SESSION['user_id'])) {
            jsonResponse([
                'authorized' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'] ?? 'Коваженко С.Б.'
                ]
            ]);
        } else {
            jsonResponse(['authorized' => false]);
        }
        break;

    case 'change_pin':
        $userId = authenticate();
        $oldPin = $requestData['old_pin'] ?? '';
        $newPin = $requestData['new_pin'] ?? '';
        if (empty($oldPin) || empty($newPin)) {
            jsonResponse(['error' => 'Both old and new PIN are required'], 400);
        }
        $users = readJsonFile($usersFile, []);
        $updated = false;
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                if ($user['pin'] === $oldPin) {
                    $user['pin'] = $newPin;
                    $updated = true;
                } else {
                    jsonResponse(['error' => 'Incorrect current PIN code'], 400);
                }
            }
        }
        if ($updated) {
            writeJsonFile($usersFile, $users);
            jsonResponse(['success' => true, 'message' => 'PIN successfully changed']);
        }
        jsonResponse(['error' => 'User not found'], 404);
        break;

    // --- NOTES CRUD ---
    case 'get_notes':
        authenticate();
        $notes = readJsonFile($notesFile, []);
        jsonResponse($notes);
        break;

    case 'save_note':
        $userId = authenticate();
        $note = $requestData;
        if (empty($note['title'])) {
            jsonResponse(['error' => 'Note title is required'], 400);
        }

        $notes = readJsonFile($notesFile, []);
        if (empty($note['id'])) {
            $note['id'] = uniqid();
            $note['creator_id'] = $userId;
            $note['created_at'] = date('Y-m-d H:i:s');
            $note['updated_at'] = date('Y-m-d H:i:s');
            $note['shared'] = $note['shared'] ?? false;
            $notes[] = $note;
        } else {
            $found = false;
            foreach ($notes as &$n) {
                if ($n['id'] === $note['id']) {
                    $n['title'] = $note['title'];
                    $n['content'] = $note['content'] ?? '';
                    $n['category'] = $note['category'] ?? 'Личное';
                    $n['tags'] = $note['tags'] ?? [];
                    $n['priority'] = $note['priority'] ?? 'Medium';
                    $n['shared'] = $note['shared'] ?? false;
                    $n['audio_url'] = $note['audio_url'] ?? $n['audio_url'] ?? null;
                    $n['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    $note = $n;
                    break;
                }
            }
            if (!$found) {
                $note['creator_id'] = $userId;
                $note['created_at'] = date('Y-m-d H:i:s');
                $note['updated_at'] = date('Y-m-d H:i:s');
                $notes[] = $note;
            }
        }
        writeJsonFile($notesFile, $notes);
        jsonResponse($note);
        break;

    case 'delete_note':
        authenticate();
        $noteId = $_GET['id'] ?? '';
        if (empty($noteId)) {
            jsonResponse(['error' => 'Note ID is required'], 400);
        }
        $notes = readJsonFile($notesFile, []);
        $newNotes = [];
        $deleted = false;
        foreach ($notes as $n) {
            if ($n['id'] === $noteId) {
                $deleted = true;
                // Delete associated audio file safely
                if (!empty($n['audio_url'])) {
                    $safeFileName = basename($n['audio_url']);
                    $filePath = $uploadsDir . '/' . $safeFileName;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
                continue;
            }
            $newNotes[] = $n;
        }
        if ($deleted) {
            writeJsonFile($notesFile, $newNotes);
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Note not found'], 404);
        break;

    // --- TASKS CRUD ---
    case 'get_tasks':
        authenticate();
        $tasks = readJsonFile($tasksFile, []);
        jsonResponse($tasks);
        break;

    case 'save_task':
        $userId = authenticate();
        $task = $requestData;
        if (empty($task['title'])) {
            jsonResponse(['error' => 'Task title is required'], 400);
        }

        $tasks = readJsonFile($tasksFile, []);
        if (empty($task['id'])) {
            $task['id'] = uniqid();
            $task['creator_id'] = $userId;
            $task['status'] = $task['status'] ?? 'pending';
            $task['created_at'] = date('Y-m-d H:i:s');
            $task['updated_at'] = date('Y-m-d H:i:s');
            $tasks[] = $task;
        } else {
            $found = false;
            foreach ($tasks as &$t) {
                if ($t['id'] === $task['id']) {
                    $t['title'] = $task['title'];
                    $t['description'] = $task['description'] ?? '';
                    $t['category'] = $task['category'] ?? 'Личное';
                    $t['tags'] = $task['tags'] ?? [];
                    $t['priority'] = $task['priority'] ?? 'Medium';
                    $t['due_date'] = $task['due_date'] ?? null;
                    $t['reminder_time'] = $task['reminder_time'] ?? null;
                    $t['repeat_interval'] = $task['repeat_interval'] ?? 'none';
                    $t['status'] = $task['status'] ?? 'pending';
                    $t['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    $task = $t;
                    break;
                }
            }
            if (!$found) {
                $task['creator_id'] = $userId;
                $task['status'] = $task['status'] ?? 'pending';
                $task['created_at'] = date('Y-m-d H:i:s');
                $task['updated_at'] = date('Y-m-d H:i:s');
                $tasks[] = $task;
            }
        }
        writeJsonFile($tasksFile, $tasks);
        jsonResponse($task);
        break;

    case 'delete_task':
        authenticate();
        $taskId = $_GET['id'] ?? '';
        if (empty($taskId)) {
            jsonResponse(['error' => 'Task ID is required'], 400);
        }
        $tasks = readJsonFile($tasksFile, []);
        $newTasks = [];
        $deleted = false;
        foreach ($tasks as $t) {
            if ($t['id'] === $taskId) {
                $deleted = true;
                continue;
            }
            $newTasks[] = $t;
        }
        if ($deleted) {
            writeJsonFile($tasksFile, $newTasks);
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Task not found'], 404);
        break;

    // --- DISCUSSIONS ---
    case 'get_discussions':
        authenticate();
        $noteId = $_GET['note_id'] ?? '';
        if (empty($noteId)) {
            jsonResponse(['error' => 'Note ID is required'], 400);
        }
        $discussions = readJsonFile($discussionsFile, []);
        $result = [];
        foreach ($discussions as $msg) {
            if ($msg['note_id'] === $noteId) {
                $result[] = $msg;
            }
        }
        jsonResponse($result);
        break;

    case 'post_message':
        $userId = authenticate();
        $noteId = $requestData['note_id'] ?? '';
        $text = $requestData['text'] ?? '';
        if (empty($noteId) || empty($text)) {
            jsonResponse(['error' => 'Note ID and message text are required'], 400);
        }

        $discussions = readJsonFile($discussionsFile, []);
        $newMessage = [
            'id' => uniqid(),
            'note_id' => $noteId,
            'user_id' => $userId,
            'user_name' => $_SESSION['user_name'] ?? 'Коваженко С.Б.',
            'text' => $text,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $discussions[] = $newMessage;
        writeJsonFile($discussionsFile, $discussions);
        jsonResponse($newMessage);
        break;

    // --- FILE UPLOADS ---
    case 'upload_voice':
        $userId = authenticate();
        if (!isset($_FILES['audio'])) {
            jsonResponse(['error' => 'No audio file uploaded'], 400);
        }

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $file = $_FILES['audio'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'wav';
        }

        // Strict file extension and type validation for security
        $allowedExtensions = ['wav', 'mp3', 'ogg', 'webm', 'm4a'];
        if (!in_array($ext, $allowedExtensions)) {
            jsonResponse(['error' => 'Only audio files are allowed'], 400);
        }

        $fileName = 'voice_' . uniqid() . '.' . $ext;
        $targetPath = $uploadsDir . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $fileUrl = '/uploads/' . $fileName;
            jsonResponse([
                'success' => true,
                'audio_url' => $fileUrl
            ]);
        } else {
            jsonResponse(['error' => 'Failed to save audio file'], 500);
        }
        break;

    // --- EXPORT/IMPORT ---
    case 'export':
        authenticate();
        $data = [
            'users' => readJsonFile($usersFile, []),
            'notes' => readJsonFile($notesFile, []),
            'tasks' => readJsonFile($tasksFile, []),
            'settings' => readJsonFile($settingsFile, []),
            'discussions' => readJsonFile($discussionsFile, [])
        ];

        header('Content-Disposition: attachment; filename="pwa_backup_' . date('Ymd_His') . '.json"');
        jsonResponse($data);
        break;

    case 'import':
        authenticate();
        if (!isset($_FILES['backup_file'])) {
            jsonResponse(['error' => 'No backup file provided'], 400);
        }

        $file = $_FILES['backup_file'];
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            jsonResponse(['error' => 'Invalid JSON format'], 400);
        }

        if (isset($data['users']) && is_array($data['users'])) {
            writeJsonFile($usersFile, $data['users']);
        }
        if (isset($data['notes']) && is_array($data['notes'])) {
            writeJsonFile($notesFile, $data['notes']);
        }
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            writeJsonFile($tasksFile, $data['tasks']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            writeJsonFile($settingsFile, $data['settings']);
        }
        if (isset($data['discussions']) && is_array($data['discussions'])) {
            writeJsonFile($discussionsFile, $data['discussions']);
        }

        jsonResponse(['success' => true, 'message' => 'Data imported successfully']);
        break;

    // --- SETTINGS SYNC ---
    case 'get_settings':
        authenticate();
        $settings = readJsonFile($settingsFile, [
            "theme" => "light",
            "sync_interval" => 30,
            "notifications_enabled" => true,
            "categories" => ["Личное", "Работа", "Учеба", "Идеи", "Другое"]
        ]);
        jsonResponse($settings);
        break;

    case 'save_settings':
        authenticate();
        $settings = $requestData;
        writeJsonFile($settingsFile, $settings);
        jsonResponse(['success' => true, 'settings' => $settings]);
        break;

    default:
        jsonResponse(['error' => 'Action not found'], 404);
        break;
}
