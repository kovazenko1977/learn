<?php
// Проверка существования публичной директории и перенаправление
$uri = $_SERVER['REQUEST_URI'];
if ($uri === '/' || $uri === '/index.php') {
    header('Location: /public/admin/index.php');
    exit;
}

// Если запрашивается файл, который существует в public, можно попробовать его отдать (необязательно, но полезно)
$publicFile = __DIR__ . '/public' . $uri;
if (is_file($publicFile)) {
    // В реальном окружении это делает веб-сервер (Nginx/Apache)
    // Здесь просто перенаправим
    header('Location: /public' . $uri);
    exit;
}

header('Location: /public/admin/index.php');
exit;
