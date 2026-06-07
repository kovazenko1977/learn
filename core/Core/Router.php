<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private Request $request;
    private Response $response;
    private ?Security $security;
    private ?\App\Auth\RBAC $rbac;

    public function __construct(Request $request, Response $response, ?Security $security = null, ?\App\Auth\RBAC $rbac = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->security = $security;
        $this->rbac = $rbac;
    }

    public function addRoute(string $method, string $path, callable|array $handler, array $roles = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'roles' => $roles
        ];
    }

    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();

        // 1. CSRF Protection for POST/PUT/DELETE
        if ($method !== 'GET' && $this->security) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? null;
            if (!$this->security->validateCsrfToken($token)) {
                $this->response->setStatusCode(403);
                $this->response->setContent("Ошибка безопасности: Некорректный CSRF токен.");
                $this->response->send();
                return;
            }
        }

        // Standardize URI
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {

                // 2. RBAC Check
                if (!empty($route['roles']) && $this->rbac) {
                    $session = new \App\Auth\Session();
                    $user = $session->get('user');
                    $userRole = $user['role'] ?? 'guest';

                    $hasPermission = false;
                    foreach ($route['roles'] as $requiredRole) {
                        if ($this->rbac->hasRole($userRole, $requiredRole)) {
                            $hasPermission = true;
                            break;
                        }
                    }

                    if (!$hasPermission) {
                        $this->response->setStatusCode(403);
                        $this->response->setContent("Доступ запрещен: недостаточно прав для выполнения операции.");
                        $this->response->send();
                        return;
                    }
                }

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
