<?php
// api/router.php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';

// Allow CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Normalize URI path to strip prefix
$path = preg_replace('#^/api/?#', '', $uri);
$parts = array_values(array_filter(explode('/', $path)));

$controllerName = !empty($parts[0]) ? ucfirst($parts[0]) . 'Controller' : 'ProductsController';
$actionName = !empty($parts[1]) ? $parts[1] : 'index';

$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    json_out(['error' => 'Маршрут или контроллер не найден: ' . $controllerName], 404);
}

require_once $controllerFile;

if (!class_exists($controllerName)) {
    json_out(['error' => 'Класс контроллера не найден'], 404);
}

$controller = new $controllerName();

if (!method_exists($controller, $actionName)) {
    json_out(['error' => "Метод '{$actionName}' не найден в {$controllerName}"], 404);
}

// Pass URL arguments beyond action to controller method
$params = array_slice($parts, 2);
call_user_func_array([$controller, $actionName], $params);
