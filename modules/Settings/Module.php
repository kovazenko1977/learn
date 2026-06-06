<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/settings', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Системные настройки</h2>
            <p class='text-gray-500 mt-1'>Конфигурация ядра, безопасность и управление доступом.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-4 gap-8'>
            <aside class='lg:col-span-1 space-y-2'>
                <button class='w-full text-left px-4 py-3 bg-white border border-blue-100 rounded-xl text-blue-600 font-bold shadow-sm shadow-blue-50'>
                    <i class='fas fa-sliders-h mr-3'></i> Общие
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all'>
                    <i class='fas fa-user-lock mr-3'></i> Пользователи
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all'>
                    <i class='fas fa-shield-alt mr-3'></i> Безопасность
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all'>
                    <i class='fas fa-database mr-3'></i> Резервные копии
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all text-red-500'>
                    <i class='fas fa-history mr-3'></i> Журнал аудита
                </button>
            </aside>

            <div class='lg:col-span-3 space-y-6'>
                <div class='bg-white p-8 rounded-2xl border border-gray-100 shadow-sm'>
                    <h3 class='text-xl font-bold text-gray-800 mb-6 border-b pb-4'>Глобальные параметры</h3>

                    <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Название учреждения</label>
                            <input type='text' value='Санаторий Солнечный' class='w-full border-gray-200 rounded-xl focus:ring-blue-500'>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Часовой пояс</label>
                            <select class='w-full border-gray-200 rounded-xl'>
                                <option>Europe/Moscow (GMT+3)</option>
                                <option>Europe/Minsk (GMT+3)</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Валюта учета</label>
                            <select class='w-full border-gray-200 rounded-xl'>
                                <option>Российский рубль (₽)</option>
                                <option>Белорусский рубль (BYN)</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Режим хранения</label>
                            <div class='flex items-center mt-2 space-x-4'>
                                <span class='px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-[10px] font-bold uppercase'>JSON (Active)</span>
                                <span class='text-gray-300 text-[10px] font-bold uppercase cursor-pointer hover:text-blue-400'>Switch to SQLite</span>
                            </div>
                        </div>
                    </div>

                    <div class='mt-10 flex justify-end space-x-3'>
                        <button class='px-6 py-2 bg-gray-100 text-gray-500 font-bold rounded-xl'>Сбросить</button>
                        <button class='px-8 py-2 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-100'>Сохранить изменения</button>
                    </div>
                </div>

                <div class='bg-orange-50 border border-orange-100 p-6 rounded-2xl flex items-start space-x-4'>
                    <div class='w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center flex-shrink-0'>
                        <i class='fas fa-exclamation-triangle'></i>
                    </div>
                    <div>
                        <h4 class='font-bold text-orange-800 mb-1'>Внимание: Режим отладки</h4>
                        <p class='text-sm text-orange-700 opacity-80'>В данный момент включен расширенный режим логирования. Это может замедлить работу системы при большом количестве одновременных пользователей.</p>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Настройки - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
