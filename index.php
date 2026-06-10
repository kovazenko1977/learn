<?php

declare(strict_types=1);

/**
 * Sanatorium 2.0 - Встроенный автозагрузчик (PSR-4)
 * Позволяет системе работать без Composer на обычном хостинге.
 */
spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\Modules\\' => 'modules/',
        'App\\Plugins\\' => 'plugins/',
        'App\\' => 'core/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $file = __DIR__ . '/' . $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

use App\Core\App;
use App\Core\Container;

// Initialize Container
$container = new Container();

// Create App instance
$app = new App($container);

// Boot and run the application
$app->boot();
$app->run();
