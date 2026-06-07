<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();

        // Standardize URI for matching
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    $result = call_user_func_array($handler, [$this->request, $this->response, ...$params]);
                } elseif (is_array($handler)) {
                    [$controllerClass, $methodName] = $handler;
                    $controller = is_object($controllerClass) ? $controllerClass : new $controllerClass();
                    $result = call_user_func_array([$controller, $methodName], [$this->request, $this->response, ...$params]);
                } else {
                    throw new \Exception("Некорректный обработчик маршрута");
                }

                if ($result !== null) {
                    $this->response->setContent((string)$result);
                    $this->response->send();
                }

                return;
            }
        }

        $this->response->setStatusCode(404);
        $this->response->setContent("404 Страница не найдена");
        $this->response->send();
    }

    private function matchPath(string $path, string $uri, &$params): bool
    {
        $params = [];
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return $path === $uri;
    }
}
