<?php

declare(strict_types=1);

namespace App\Core;

use App\Storage\StorageManager;
use App\Storage\JsonDriver;
use App\Module\ModuleManager;
use App\View\Renderer;
use App\Auth\Session;
use App\Auth\AuthManager;
use App\Auth\RBAC;

class App
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function boot(): void
    {
        // 1. Core Services
        $this->container->set(Request::class, fn() => new Request());
        $this->container->set(Response::class, fn() => new Response());
        $this->container->set(Router::class, fn($c) => new Router($c->get(Request::class), $c->get(Response::class)));

        // 2. Storage
        $this->container->set(StorageManager::class, function() {
            $driver = new JsonDriver(__DIR__ . '/../../storage');
            return new StorageManager($driver);
        });

        // 2.1 Config
        $this->container->set(Config::class, fn($c) => new Config($c->get(StorageManager::class)));

        // 3. Auth & RBAC
        $this->container->set(Session::class, fn() => new Session());
        $this->container->set(AuthManager::class, fn($c) => new AuthManager($c->get(Session::class), $c->get(StorageManager::class)));
        $this->container->set(RBAC::class, fn() => new RBAC());

        // 4. View Renderer
        $this->container->set(Renderer::class, function($c) {
            $renderer = new Renderer(__DIR__ . '/../View/templates');
            $renderer->setGlobal('basePath', $c->get(Request::class)->getBasePath());
            $renderer->setGlobal('config', $c->get(Config::class));
            return $renderer;
        });

        // 5. Security
        $this->container->set(Security::class, fn($c) => new Security($c->get(Session::class)));

        // 6. Module Manager
        $this->container->set(ModuleManager::class, fn($c) => new ModuleManager($c, __DIR__ . '/../../modules'));

        // Load Modules
        $moduleManager = $this->container->get(ModuleManager::class);
        $moduleManager->loadModules();

        // Register default routes
        $this->registerDefaultRoutes();
    }

    private function registerDefaultRoutes(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/', function($req, $res) {
            $renderer = $this->container->get(Renderer::class);
            return $renderer->render('layout', [
                'title' => 'Центр управления',
                'content' => $renderer->render('dashboard', [
                    'user' => ['username' => 'Admin']
                ]),
                'user' => ['username' => 'Admin']
            ]);
        });
    }

    public function run(): void
    {
        try {
            $router = $this->container->get(Router::class);
            $router->dispatch();
        } catch (\Exception $e) {
            $response = $this->container->get(Response::class);
            $response->setStatusCode(500);
            $response->setContent("Error: " . $e->getMessage());
            $response->send();
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
