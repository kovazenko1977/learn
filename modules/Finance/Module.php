<?php

declare(strict_types=1);

namespace App\Modules\Finance;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/finance', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Финансовый контроль</h2>
            <p class='text-gray-500 mt-1'>Учет платежей, счетов и финансовая аналитика.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8'>
            <div class='lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100'>
                <div class='flex justify-between items-center mb-6'>
                    <h3 class='text-lg font-bold'>История транзакций</h3>
                    <div class='flex space-x-2'>
                        <button class='text-xs font-bold px-3 py-1 bg-blue-50 text-blue-600 rounded-full'>Все</button>
                        <button class='text-xs font-bold px-3 py-1 text-gray-400 hover:bg-gray-50 rounded-full transition-all'>Доходы</button>
                        <button class='text-xs font-bold px-3 py-1 text-gray-400 hover:bg-gray-50 rounded-full transition-all'>Расходы</button>
                    </div>
                </div>
                <div class='space-y-4'>
                    <div class='flex items-center justify-between p-4 bg-gray-50 rounded-xl'>
                        <div class='flex items-center space-x-4'>
                            <div class='w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center'>
                                <i class='fas fa-arrow-down'></i>
                            </div>
                            <div>
                                <p class='font-bold text-gray-800'>Оплата проживания №102</p>
                                <p class='text-xs text-gray-400'>Сегодня, 14:20 • Петрова А.С.</p>
                            </div>
                        </div>
                        <p class='font-bold text-green-600'>+42,000 ₽</p>
                    </div>
                    <div class='flex items-center justify-between p-4 bg-gray-50 rounded-xl'>
                        <div class='flex items-center space-x-4'>
                            <div class='w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center'>
                                <i class='fas fa-arrow-down'></i>
                            </div>
                            <div>
                                <p class='font-bold text-gray-800'>Медицинские услуги (Массаж)</p>
                                <p class='text-xs text-gray-400'>Вчера, 10:15 • Иванов И.И.</p>
                            </div>
                        </div>
                        <p class='font-bold text-green-600'>+2,500 ₽</p>
                    </div>
                    <div class='flex items-center justify-between p-4 bg-gray-50 rounded-xl'>
                        <div class='flex items-center space-x-4'>
                            <div class='w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center'>
                                <i class='fas fa-arrow-up'></i>
                            </div>
                            <div>
                                <p class='font-bold text-gray-800'>Закупка медикаментов</p>
                                <p class='text-xs text-gray-400'>08.06.2024 • ООО 'ФармСнаб'</p>
                            </div>
                        </div>
                        <p class='font-bold text-red-600'>-125,000 ₽</p>
                    </div>
                </div>
            </div>

            <div class='space-y-8'>
                <div class='bg-gradient-to-br from-blue-600 to-blue-800 p-8 rounded-3xl text-white shadow-xl shadow-blue-200'>
                    <div class='flex justify-between items-start mb-12'>
                        <i class='fas fa-credit-card text-3xl opacity-50'></i>
                        <span class='text-xs font-bold tracking-widest opacity-80 uppercase'>Основной счет</span>
                    </div>
                    <p class='text-xs opacity-60 mb-1'>Текущий баланс</p>
                    <p class='text-4xl font-bold mb-8 tracking-tight'>4.82M <span class='text-lg opacity-60'>₽</span></p>
                    <div class='flex justify-between items-end'>
                        <div>
                            <p class='text-[10px] opacity-40 uppercase font-bold tracking-tighter mb-1'>Владелец</p>
                            <p class='text-sm font-bold tracking-wide'>Sanatorium 2.0 Core</p>
                        </div>
                        <div class='w-12 h-8 bg-white bg-opacity-20 rounded-md'></div>
                    </div>
                </div>

                <div class='bg-white p-6 rounded-2xl border border-gray-100'>
                    <h3 class='text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider'>Распределение по категориям</h3>
                    <canvas id='financePieChart' height='200'></canvas>
                </div>
            </div>
        </div>

        <script>
            const ctxP = document.getElementById('financePieChart').getContext('2d');
            new Chart(ctxP, {
                type: 'doughnut',
                data: {
                    labels: ['Проживание', 'Медицина', 'Питание', 'SPA'],
                    datasets: [{
                        data: [60, 20, 15, 5],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } },
                    cutout: '70%'
                }
            });
        </script>
        ";

        return $renderer->render('layout', [
            'title' => 'Финансы - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
