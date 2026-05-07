<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');
$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);

// Determine root path for redirects
$currentPath = $_SERVER['PHP_SELF'];
if (strpos($currentPath, '/admin/') !== false || strpos($currentPath, '/mobile/') !== false) {
    $rootPath = '../';
} else {
    $rootPath = './';
}

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($settings['auth_enabled']) && $settings['auth_enabled'] === false) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'Администратор';
        $_SESSION['role'] = 'administrator';
    } else {
        header('Location: ' . $rootPath . 'admin/login.php');
        die();
    }
}

function hasPermission($permission) {
    if (($_SESSION['role'] ?? '') === 'administrator') {
        return true;
    }
    return in_array($permission, $_SESSION['permissions'] ?? []);
}

$permissionMap = [
    'dashboard.php' => 'view_dashboard',
    'index.php' => 'view_dashboard',
    'calendar.php' => 'view_calendar',
    'hourly_calendar.php' => 'view_calendar',
    'sauna_calendar.php' => 'view_calendar',
    'guests.php' => 'manage_guests',
    'rooms.php' => 'manage_rooms',
    'room_classes.php' => 'manage_rooms',
    'procedures.php' => 'manage_catalog',
    'services.php' => 'manage_catalog',
    'packages.php' => 'manage_catalog',
    'analytics.php' => 'view_analytics',
    'planning.php' => 'manage_planning',
    'users.php' => 'manage_users',
    'settings.php' => 'manage_settings',
    'text_blocks.php' => 'manage_settings',
    'import_csv.php' => 'manage_settings',
    'constructor.php' => 'manage_settings',
    'system_health.php' => 'manage_settings',
];

$currentFile = basename($_SERVER['PHP_SELF']);
if (isset($permissionMap[$currentFile])) {
    if (!hasPermission($permissionMap[$currentFile])) {
        echo "<div style='padding: 20px; color: white; background: #d83b01; border-radius: 8px; margin: 20px; font-family: sans-serif;'>";
        echo "<h2>Доступ запрещен</h2>";
        echo "<p>У вас недостаточно прав для просмотра этого раздела.</p>";
        echo "<a href='{$rootPath}index.php' style='color: white; font-weight: bold;'>Вернуться на главную</a>";
        echo "</div>";
        die();
    }
}
?>
