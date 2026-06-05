<?php

declare(strict_types=1);

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
