<?php
require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Logger.php';

Auth::init();

// Global error handling to prevent non-JSON output
set_exception_handler(function($e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$action = isset($_GET['action']) ? $_GET['action'] : '';
$module = isset($_GET['module']) ? $_GET['module'] : '';

// Ensure default admin exists
$users = Storage::list('users');
if (empty($users)) {
    $adminId = uniqid();
    $adminUser = [
        'id' => $adminId,
        'login' => 'admin',
        'password_hash' => Security::hashPassword('admin123456'),
        'role' => 'admin',
        'name' => 'Administrator',
        'status' => 'active',
        'created_at' => date('c')
    ];
    Storage::write('users', $adminId, $adminUser);
    Logger::log("Default admin created", "info", "auth.log");
}

header('Content-Type: application/json');

// Global Auth Actions
if ($module === 'auth') {
    if ($action === 'login') {
        $input = json_decode(file_get_contents('php://input'), true);
        $login = isset($input['login']) ? $input['login'] : '';
        $password = isset($input['password']) ? $input['password'] : '';

        $users = Storage::list('users');
        $foundUser = null;
        foreach ($users as $u) {
            if ($u['login'] === $login) {
                $foundUser = $u;
                break;
            }
        }

        if ($foundUser && Auth::login($foundUser, $password)) {
            Logger::log("User logged in: $login", "info", "auth.log");
            echo json_encode(['success' => true, 'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['name'],
                'role' => $_SESSION['role']
            ]]);
        } else {
            Logger::log("Failed login attempt: $login", "warning", "auth.log");
            echo json_encode(['error' => 'Invalid credentials']);
        }
        exit;
    }

    if ($action === 'logout') {
        Auth::logout();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'check') {
        if (Auth::isLoggedIn()) {
            echo json_encode([
                'isLoggedIn' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['name'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode(['isLoggedIn' => false]);
        }
        exit;
    }
}

// Module routing with whitelisting
$allowedModules = [
    'patients', 'doctors', 'services', 'rooms',
    'appointments', 'settings', 'tasks', 'finance',
    'analytics', 'documents', 'online_booking', 'tags', 'sources', 'users', 'chat', 'versions', 'templates'
];

if ($module) {
    // Check if module is disabled in settings
    $modulesSettings = Storage::read('settings', 'modules');
    if ($modulesSettings && isset($modulesSettings[$module]) && $modulesSettings[$module] === false) {
        echo json_encode(['error' => 'Module is disabled']);
        exit;
    }

    if (in_array($module, $allowedModules) && file_exists(__DIR__ . '/' . $module . '.php')) {
        require_once __DIR__ . '/' . $module . '.php';
    } else {
        echo json_encode(['error' => 'Module not found or access denied']);
    }
} else {
    echo json_encode(['message' => 'API is running']);
}
