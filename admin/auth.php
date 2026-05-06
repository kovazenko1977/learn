<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

function hasPermission($permission) {
    if (($_SESSION['role'] ?? '') === 'administrator') {
        return true;
    }
    return in_array($permission, $_SESSION['permissions'] ?? []);
}

// Map files to permissions
$permissionMap = [
    'dashboard.php' => 'view_dashboard',
    'today.php' => 'view_dashboard',
    'calendar.php' => 'view_calendar',
    'hourly_calendar.php' => 'view_calendar',
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
    'form_configurator.php' => 'manage_settings',
];

$currentFile = basename($_SERVER['PHP_SELF']);
if (isset($permissionMap[$currentFile])) {
    if (!hasPermission($permissionMap[$currentFile])) {
        echo "<div style='padding: 20px; color: white; background: #d83b01; border-radius: 8px; margin: 20px; font-family: sans-serif;'>";
        echo "<h2>Доступ запрещен</h2>";
        echo "<p>У вас недостаточно прав для просмотра этого раздела.</p>";
        echo "<a href='dashboard.php' style='color: white; font-weight: bold;'>Вернуться на главную</a>";
        echo "</div>";
        exit;
    }
}
