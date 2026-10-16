<?php
/**
 * API Router
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';

try {
    // Parse Request URI
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uriSegments = explode('/', trim($requestUri, '/'));

    // Expected pattern: api/{controller}/{action} or api/{controller}
    if (empty($uriSegments) || $uriSegments[0] !== 'api') {
        json_out(['error' => 'Invalid API Endpoint'], 404);
    }

    $controllerName = isset($uriSegments[1]) && !empty($uriSegments[1]) ? $uriSegments[1] : 'products';
    $actionName     = isset($uriSegments[2]) && !empty($uriSegments[2]) ? $uriSegments[2] : '';

    // Map controller slug to class name
    $controllerClass = ucfirst($controllerName) . 'Controller';
    $controllerFile  = __DIR__ . '/controllers/' . $controllerClass . '.php';

    if (!file_exists($controllerFile)) {
        json_out(['error' => "Controller '$controllerName' not found"], 404);
    }

    require_once $controllerFile;

    if (!class_exists($controllerClass)) {
        json_out(['error' => "Controller class '$controllerClass' not found"], 404);
    }

    $controller = new $controllerClass();
    $method = $_SERVER['REQUEST_METHOD'];

    // Handle action based routing or default REST handle
    if (!empty($actionName)) {
        $camelName = str_replace(' ', '', ucwords(str_replace('-', ' ', $actionName)));
        $actionMethod = 'action' . $camelName;

        if (!method_exists($controller, $actionMethod)) {
            $rawCamel = lcfirst($camelName);
            if (method_exists($controller, $rawCamel)) {
                $actionMethod = $rawCamel;
            } else {
                json_out(['error' => "Action '$actionName' not found on '$controllerName'"], 404);
            }
        }
        $controller->$actionMethod();
    } else {
        // REST standard handle method
        if (method_exists($controller, 'handle')) {
            $controller->handle($method);
        } else {
            json_out(['error' => "Handler for $method not implemented in $controllerClass"], 405);
        }
    }
} catch (\Throwable $e) {
    json_out(['error' => 'Внутренняя ошибка сервера: ' . $e->getMessage()], 500);
}
