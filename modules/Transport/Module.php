<?php

declare(strict_types=1);

namespace App\Modules\Transport;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/transport', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Транспорт и Логистика</h2>
            <p class='text-gray-500 mt-1'>Трансферы, экскурсии и управление автопарком.</p>
        </div>

        <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
            <h3 class='font-bold text-gray-800 mb-6'>Расписание трансферов (Сегодня)</h3>
            <div class='space-y-4'>
                <div class='flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100'>
                    <div class='flex items-center space-x-6'>
                        <div class='text-center'>
                            <p class='text-lg font-bold text-gray-800'>14:00</p>
                            <p class='text-[10px] text-gray-400 font-bold uppercase'>Выезд</p>
                        </div>
                        <div class='w-px h-10 bg-gray-200'></div>
                        <div>
                            <p class='font-bold text-gray-800'>Ж/Д Вокзал -> Санаторий</p>
                            <p class='text-xs text-gray-500'>Микроавтобус Mercedes (6 мест)</p>
                        </div>
                    </div>
                    <span class='px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-[10px] font-bold'>ЗАПЛАНИРОВАНО</span>
                </div>

                <div class='flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100'>
                    <div class='flex items-center space-x-6'>
                        <div class='text-center'>
                            <p class='text-lg font-bold text-gray-800'>16:30</p>
                            <p class='text-[10px] text-gray-400 font-bold uppercase'>Выезд</p>
                        </div>
                        <div class='w-px h-10 bg-gray-200'></div>
                        <div>
                            <p class='font-bold text-gray-800'>Аэропорт -> Санаторий</p>
                            <p class='text-xs text-gray-500'>Toyota Camry (VIP)</p>
                        </div>
                    </div>
                    <span class='px-3 py-1 bg-green-100 text-green-600 rounded-full text-[10px] font-bold'>В ПУТИ</span>
                </div>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Транспорт - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
