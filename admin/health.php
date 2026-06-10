<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

function get_dir_size($directory) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
    return round($bytes, 2) . ' ' . $units[$i];
}

$dataDir = __DIR__ . '/../data/';
$uploadDir = __DIR__ . '/../data/uploads/';

$health = [
    'php_version' => PHP_VERSION,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'data_writable' => is_writable($dataDir),
    'uploads_writable' => is_writable($uploadDir),
    'disk_free_space' => format_size(disk_free_space(".")),
    'data_size' => format_size(get_dir_size($dataDir)),
    'curl_enabled' => function_exists('curl_version'),
    'zip_enabled' => class_exists('ZipArchive'),
];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Состояние системы - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link active" href="health.php"><i class="bi bi-heart-pulse"></i> Состояние системы</a>
            <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Состояние системы</h2>
        </header>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="glass-card h-100">
                    <h5>Окружение</h5>
                    <table class="table mt-3">
                        <tr><td>Версия PHP</td><td><strong><?php echo $health['php_version']; ?></strong></td></tr>
                        <tr><td>Лимит загрузки</td><td><strong><?php echo $health['upload_max_filesize']; ?></strong></td></tr>
                        <tr><td>CURL (Webhooks)</td><td><?php echo $health['curl_enabled'] ? '<span class="text-success">Включен</span>' : '<span class="text-danger">Выключен</span>'; ?></td></tr>
                        <tr><td>ZIP (Резервные копии)</td><td><?php echo $health['zip_enabled'] ? '<span class="text-success">Включен</span>' : '<span class="text-danger">Выключен</span>'; ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card h-100">
                    <h5>Хранилище</h5>
                    <table class="table mt-3">
                        <tr><td>Права на /data/</td><td><?php echo $health['data_writable'] ? '<span class="text-success">OK (Запись разрешена)</span>' : '<span class="text-danger">Ошибка (Нет прав)</span>'; ?></td></tr>
                        <tr><td>Права на /uploads/</td><td><?php echo $health['uploads_writable'] ? '<span class="text-success">OK (Запись разрешена)</span>' : '<span class="text-danger">Ошибка (Нет прав)</span>'; ?></td></tr>
                        <tr><td>Объем данных</td><td><strong><?php echo $health['data_size']; ?></strong></td></tr>
                        <tr><td>Свободно на диске</td><td><strong><?php echo $health['disk_free_space']; ?></strong></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
