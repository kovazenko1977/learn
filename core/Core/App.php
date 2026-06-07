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

        // Seed demo data
        $this->seedDemoData();

        // Load Modules
        $moduleManager = $this->container->get(ModuleManager::class);
        $moduleManager->loadModules();

        // Register default routes
        $this->registerDefaultRoutes();
    }

    private function seedDemoData(): void
    {
        $storage = $this->container->get(StorageManager::class);

        $storage->seed('rooms', [
            ['number' => '101', 'type' => 'Стандарт', 'floor' => 1, 'status' => 'свободен', 'price' => 3500, 'places' => 1, 'occupied_places' => 0],
            ['number' => '102', 'type' => 'Стандарт', 'floor' => 1, 'status' => 'занят', 'price' => 3500, 'places' => 1, 'occupied_places' => 1],
            ['number' => '103', 'type' => 'Стандарт', 'floor' => 1, 'status' => 'свободен', 'price' => 3500, 'places' => 2, 'occupied_places' => 0],
            ['number' => '104', 'type' => 'Стандарт', 'floor' => 1, 'status' => 'свободен', 'price' => 3500, 'places' => 2, 'occupied_places' => 0],
            ['number' => '201', 'type' => 'Люкс', 'floor' => 2, 'status' => 'свободен', 'price' => 7500, 'places' => 2, 'occupied_places' => 0],
            ['number' => '202', 'type' => 'Полулюкс', 'floor' => 2, 'status' => 'уборка', 'price' => 5500, 'places' => 1, 'occupied_places' => 0],
            ['number' => '301', 'type' => 'Апартаменты', 'floor' => 3, 'status' => 'свободен', 'price' => 12000, 'places' => 4, 'occupied_places' => 0],
        ]);

        $storage->seed('bookings', [
            [
                'id' => 'BK_DEMO_1',
                'room_number' => '102',
                'guest_name' => 'Петров Петр Петрович',
                'guest_gender' => 'male',
                'guest_age' => 45,
                'is_family' => 'no',
                'date_from' => date('Y-m-d', strtotime('-3 days')),
                'date_to' => date('Y-m-d', strtotime('+4 days')),
                'status' => 'confirmed'
            ]
        ]);
    }

    private function registerDefaultRoutes(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/', function($req, $res) {
            $renderer = $this->container->get(Renderer::class);
            return $renderer->render('layout', [
                'title' => 'Рабочий стол - Sanatorium 2.0',
                'user' => ['username' => 'Администратор']
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
            $response->setContent("Ошибка системы: " . $e->getMessage());
            $response->send();
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
