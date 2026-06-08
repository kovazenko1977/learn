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
        $this->container->set(Session::class, fn() => new Session());
        $this->container->set(Security::class, fn($c) => new Security($c->get(Session::class)));
        $this->container->set(RBAC::class, fn() => new RBAC());
        $this->container->set(Request::class, fn() => new Request());
        $this->container->set(Response::class, fn() => new Response());

        $this->container->set(Router::class, function($c) {
             return new Router(
                 $c->get(Request::class),
                 $c->get(Response::class),
                 $c->get(Security::class),
                 $c->get(RBAC::class)
             );
        });

        // 2. Storage
        $this->container->set(StorageManager::class, function() {
            $driver = new JsonDriver(__DIR__ . '/../../storage');
            return new StorageManager($driver);
        });

        // 2.1 Config
        $this->container->set(Config::class, fn($c) => new Config($c->get(StorageManager::class)));

        // 3. Auth & RBAC
        $this->container->set(AuthManager::class, fn($c) => new AuthManager($c->get(Session::class), $c->get(StorageManager::class)));

        // 4. View Renderer
        $this->container->set(Renderer::class, function($c) {
            $renderer = new Renderer(__DIR__ . '/../View/templates');
            $renderer->setGlobal('basePath', $c->get(Request::class)->getBasePath());
            $renderer->setGlobal('config', $c->get(Config::class));
            $renderer->setGlobal('security', $c->get(Security::class));

            $auth = $c->get(AuthManager::class);
            if ($auth->isLoggedIn()) {
                $renderer->setGlobal('user', $auth->getCurrentUser());
            } else {
                $renderer->setGlobal('user', ['username' => 'Гость', 'role' => 'guest']);
            }

            return $renderer;
        });

        // 5. Module Manager
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
            [
                'id' => 'room_101',
                'number' => '101',
                'type' => 'Стандарт',
                'floor' => 1,
                'status' => 'свободен',
                'price' => 3500,
                'places' => 1,
                'occupied_places' => 0,
                'amenities' => ['wifi', 'tv', 'shower'],
                'image' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=800',
                'description' => 'Уютный одноместный номер со всеми удобствами и быстрым Wi-Fi.'
            ],
            [
                'id' => 'room_201',
                'number' => '201',
                'type' => 'Люкс',
                'floor' => 2,
                'status' => 'свободен',
                'price' => 8500,
                'places' => 2,
                'occupied_places' => 0,
                'amenities' => ['wifi', 'tv', 'fridge', 'ac', 'shower', 'safe', 'balcony'],
                'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&q=80&w=800',
                'description' => 'Просторный двухкомнатный люкс с панорамным видом на парк и полным оснащением.'
            ],
        ]);

        $storage->seed('payments', [
            ['id' => 'p1', 'amount' => 15000, 'date' => date('Y-m-d', strtotime('-5 days')), 'category' => 'Проживание'],
            ['id' => 'p2', 'amount' => 12000, 'date' => date('Y-m-d', strtotime('-4 days')), 'category' => 'Медицина'],
            ['id' => 'p3', 'amount' => 18000, 'date' => date('Y-m-d', strtotime('-3 days')), 'category' => 'Проживание'],
            ['id' => 'p4', 'amount' => 8000, 'date' => date('Y-m-d', strtotime('-2 days')), 'category' => 'Доп. услуги'],
            ['id' => 'p5', 'amount' => 22000, 'date' => date('Y-m-d', strtotime('-1 day')), 'category' => 'Проживание'],
            ['id' => 'p6', 'amount' => 14000, 'date' => date('Y-m-d'), 'category' => 'Медицина'],
        ]);

        $storage->seed('users', [
            [
                'id' => '6500000000000',
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_BCRYPT),
                'role' => 'admin',
                'created_at' => date('Y-m-d 00:00:00')
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
