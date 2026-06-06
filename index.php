<?php

declare(strict_types=1);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Ошибка 500: Окружение не настроено</h1>";
    echo "<p>Файл <code>vendor/autoload.php</code> не найден.</p>";
    echo "<p><b>Решение:</b> Выполните команду <code>composer dump-autoload</code> в корневой директории проекта.</p>";
    echo "<p>Также убедитесь, что версия PHP >= 8.3.</p>";
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Container;

// Initialize Container
$container = new Container();

// Create App instance
$app = new App($container);

// Boot and run the application
$app->boot();
$app->run();
