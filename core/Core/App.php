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

        // 3. Auth & RBAC
        $this->container->set(Session::class, fn() => new Session());
        $this->container->set(AuthManager::class, fn($c) => new AuthManager($c->get(Session::class), $c->get(StorageManager::class)));
        $this->container->set(RBAC::class, fn() => new RBAC());

        // 4. View Renderer
        $this->container->set(Renderer::class, function($c) {
            $renderer = new Renderer(__DIR__ . '/../View/templates');
            $renderer->setGlobal('basePath', $c->get(Request::class)->getBasePath());
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
                'title' => 'Аналитический дашборд',
                'content' => '
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                                <span class="text-green-500 text-sm font-bold">+12% <i class="fas fa-arrow-up"></i></span>
                            </div>
                            <h3 class="text-gray-500 text-sm font-medium">Гости в санатории</h3>
                            <p class="text-2xl font-bold text-gray-800">1,248</p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-600">
                                    <i class="fas fa-door-open text-xl"></i>
                                </div>
                                <span class="text-gray-400 text-sm font-medium">Всего 500</span>
                            </div>
                            <h3 class="text-gray-500 text-sm font-medium">Свободно номеров</h3>
                            <p class="text-2xl font-bold text-gray-800">142</p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600">
                                    <i class="fas fa-stethoscope text-xl"></i>
                                </div>
                                <span class="text-blue-500 text-sm font-bold">85% занято</span>
                            </div>
                            <h3 class="text-gray-500 text-sm font-medium">Процедур сегодня</h3>
                            <p class="text-2xl font-bold text-gray-800">384</p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600">
                                    <i class="fas fa-ruble-sign text-xl"></i>
                                </div>
                                <span class="text-green-500 text-sm font-bold">+2.4М</span>
                            </div>
                            <h3 class="text-gray-500 text-sm font-medium">Выручка (мес)</h3>
                            <p class="text-2xl font-bold text-gray-800">12.8М</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-6">Динамика заездов</h3>
                            <canvas id="occupancyChart" height="200"></canvas>
                        </div>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-6">Популярные услуги</h3>
                            <canvas id="servicesChart" height="200"></canvas>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-800">Последние бронирования</h3>
                            <button class="text-blue-600 text-sm font-bold hover:underline">Смотреть все</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-gray-400 text-xs uppercase tracking-wider border-b">
                                        <th class="pb-3 font-medium">Гость</th>
                                        <th class="pb-3 font-medium">Период</th>
                                        <th class="pb-3 font-medium">Номер</th>
                                        <th class="pb-3 font-medium">Статус</th>
                                        <th class="pb-3 font-medium text-right">Сумма</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-sm">
                                    <tr>
                                        <td class="py-4 font-medium text-gray-800">Николаев А.С.</td>
                                        <td class="py-4 text-gray-500">12.06 - 24.06</td>
                                        <td class="py-4">Люкс №302</td>
                                        <td class="py-4"><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">Оплачено</span></td>
                                        <td class="py-4 text-right font-bold">48,000 ₽</td>
                                    </tr>
                                    <tr>
                                        <td class="py-4 font-medium text-gray-800">Васильева Е.М.</td>
                                        <td class="py-4 text-gray-500">14.06 - 20.06</td>
                                        <td class="py-4">Стандарт №105</td>
                                        <td class="py-4"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">Бронь</span></td>
                                        <td class="py-4 text-right font-bold">24,500 ₽</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <script>
                        const ctx1 = document.getElementById("occupancyChart").getContext("2d");
                        new Chart(ctx1, {
                            type: "line",
                            data: {
                                labels: ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"],
                                datasets: [{
                                    label: "Загрузка %",
                                    data: [65, 72, 68, 75, 82, 90, 88],
                                    borderColor: "#3b82f6",
                                    tension: 0.4,
                                    fill: true,
                                    backgroundColor: "rgba(59, 130, 246, 0.1)"
                                }]
                            },
                            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
                        });

                        const ctx2 = document.getElementById("servicesChart").getContext("2d");
                        new Chart(ctx2, {
                            type: "doughnut",
                            data: {
                                labels: ["Грязелечение", "Массаж", "Бассейн", "Диета"],
                                datasets: [{
                                    data: [30, 25, 20, 25],
                                    backgroundColor: ["#3b82f6", "#10b981", "#f59e0b", "#8b5cf6"]
                                }]
                            },
                            options: { plugins: { legend: { position: "bottom" } } }
                        });
                    </script>
                ',
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
