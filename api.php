<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Ensure critical directories exist with correct permissions
if (!file_exists('data')) {
    mkdir('data', 0755, true);
}
if (!file_exists('uploads')) {
    mkdir('uploads', 0755, true);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper function to read JSON files safely with a shared lock
function read_json($file) {
    if (!file_exists($file)) {
        return [];
    }
    $fp = fopen($file, 'r');
    if (!$fp) {
        return [];
    }
    flock($fp, LOCK_SH);
    $size = filesize($file);
    $content = $size > 0 ? fread($fp, $size) : '';
    flock($fp, LOCK_UN);
    fclose($fp);
    return json_decode($content, true) ?: [];
}

// Helper function to write JSON files safely with an exclusive lock
function write_json($file, $data) {
    $fp = fopen($file, 'w+');
    if (!$fp) {
        return false;
    }
    flock($fp, LOCK_EX);
    $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    fwrite($fp, $content);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

switch ($action) {
    case 'login':
        $input = json_decode(file_get_contents('php://input'), true);
        $pin = isset($input['pin']) ? trim($input['pin']) : '';
        $users = read_json('data/users.json');

        foreach ($users as $user) {
            // Verify using secure bcrypt password verification
            if (password_verify($pin, $user['pin'])) {
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name']
                    ]
                ]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Неверный PIN-код']);
        break;

    case 'change_pin':
        $input = json_decode(file_get_contents('php://input'), true);
        $old_pin = isset($input['old_pin']) ? trim($input['old_pin']) : '';
        $new_pin = isset($input['new_pin']) ? trim($input['new_pin']) : '';
        $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;

        if (strlen($new_pin) < 4) {
            echo json_encode(['success' => false, 'message' => 'PIN-код должен быть не менее 4 цифр']);
            exit;
        }

        $users = read_json('data/users.json');
        $updated = false;

        foreach ($users as &$user) {
            if ($user['id'] === $user_id && password_verify($old_pin, $user['pin'])) {
                // Securely hash the new PIN
                $user['pin'] = password_hash($new_pin, PASSWORD_DEFAULT);
                $updated = true;
                break;
            }
        }

        if ($updated) {
            write_json('data/users.json', $users);
            echo json_encode(['success' => true, 'message' => 'PIN-код успешно изменен']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Старый PIN-код неверен']);
        }
        break;

    case 'get_users':
        $users = read_json('data/users.json');
        $safe_users = [];
        foreach ($users as $u) {
            $safe_users[] = [
                'id' => $u['id'],
                'name' => $u['name']
            ];
        }
        echo json_encode(['success' => true, 'users' => $safe_users]);
        break;

    case 'update_username':
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        $new_name = isset($input['name']) ? trim($input['name']) : '';

        if (empty($new_name)) {
            echo json_encode(['success' => false, 'message' => 'Имя не должно быть пустым']);
            exit;
        }

        $users = read_json('data/users.json');
        $updated = false;
        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                $user['name'] = $new_name;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            write_json('data/users.json', $users);
            echo json_encode(['success' => true, 'message' => 'Имя пользователя успешно обновлено']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        }
        break;

    case 'get_data':
        $notes = read_json('data/notes.json');
        $tasks = read_json('data/tasks.json');
        $settings = read_json('data/settings.json');
        $discussions = read_json('data/discussions.json');
        echo json_encode([
            'success' => true,
            'notes' => $notes,
            'tasks' => $tasks,
            'settings' => $settings,
            'discussions' => $discussions
        ]);
        break;

    case 'save_data':
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['notes'])) {
            write_json('data/notes.json', $input['notes']);
        }
        if (isset($input['tasks'])) {
            write_json('data/tasks.json', $input['tasks']);
        }
        if (isset($input['settings'])) {
            write_json('data/settings.json', $input['settings']);
        }
        if (isset($input['discussions'])) {
            write_json('data/discussions.json', $input['discussions']);
        }
        echo json_encode(['success' => true]);
        break;

    case 'upload_audio':
        if (!isset($_FILES['audio'])) {
            echo json_encode(['success' => false, 'message' => 'Файл не найден']);
            exit;
        }

        $file = $_FILES['audio'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'webm';
        }

        // Strict whitelist of safe extensions to prevent RCE
        $allowed = ['webm', 'wav', 'mp3', 'ogg', 'm4a'];
        if (!in_array($ext, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Недопустимый формат файла']);
            exit;
        }

        $filename = 'audio_' . time() . '_' . uniqid() . '.' . $ext;
        $target = 'uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode([
                'success' => true,
                'url' => $target
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла']);
        }
        break;

    case 'export':
        $notes = read_json('data/notes.json');
        $tasks = read_json('data/tasks.json');
        $settings = read_json('data/settings.json');
        $discussions = read_json('data/discussions.json');

        $backup = [
            'notes' => $notes,
            'tasks' => $tasks,
            'settings' => $settings,
            'discussions' => $discussions
        ];

        header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'import':
        if (!isset($_FILES['backup'])) {
            echo json_encode(['success' => false, 'message' => 'Файл резервной копии не найден']);
            exit;
        }

        $file = $_FILES['backup'];
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (!$data || (!isset($data['notes']) && !isset($data['tasks']))) {
            echo json_encode(['success' => false, 'message' => 'Неверный формат резервной копии']);
            exit;
        }

        if (isset($data['notes'])) {
            write_json('data/notes.json', $data['notes']);
        }
        if (isset($data['tasks'])) {
            write_json('data/tasks.json', $data['tasks']);
        }
        if (isset($data['settings'])) {
            write_json('data/settings.json', $data['settings']);
        }
        if (isset($data['discussions'])) {
            write_json('data/discussions.json', $data['discussions']);
        }

        echo json_encode(['success' => true, 'message' => 'Данные успешно импортированы']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Неверное действие']);
        break;
}
