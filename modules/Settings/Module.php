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
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Настройки системы</h2>
            <p class='text-slate-500 mt-2'>Конфигурация модулей, прав доступа и общих параметров Sanatorium 2.0.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
            <div class='md:col-span-1 space-y-2'>
                <button class='w-full text-left p-4 rounded-2xl bg-blue-50 text-blue-600 font-bold text-sm flex items-center'>
                    <i class='fas fa-sliders mr-3'></i> Основные
                </button>
                <button class='w-full text-left p-4 rounded-2xl hover:bg-slate-50 text-slate-500 font-bold text-sm flex items-center transition-all'>
                    <i class='fas fa-user-shield mr-3'></i> Безопасность
                </button>
                <button class='w-full text-left p-4 rounded-2xl hover:bg-slate-50 text-slate-500 font-bold text-sm flex items-center transition-all'>
                    <i class='fas fa-puzzle-piece mr-3'></i> Модули
                </button>
                <button class='w-full text-left p-4 rounded-2xl hover:bg-slate-50 text-slate-500 font-bold text-sm flex items-center transition-all'>
                    <i class='fas fa-database mr-3'></i> Хранилище
                </button>
            </div>

            <div class='md:col-span-2'>
                <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                    <h3 class='text-xl font-bold mb-8 text-slate-800'>Общие параметры</h3>
                    <div class='space-y-6'>
                        <div class='flex items-center justify-between p-6 bg-slate-50 rounded-3xl'>
                            <div>
                                <p class='text-sm font-bold text-slate-800'>Название организации</p>
                                <p class='text-xs text-slate-400'>Отображается в отчетах и чеках</p>
                            </div>
                            <input type='text' value='Санаторий \"Солнечный\"' class='bg-white border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500/10'>
                        </div>
                        <div class='flex items-center justify-between p-6 bg-slate-50 rounded-3xl'>
                            <div>
                                <p class='text-sm font-bold text-slate-800'>Валюта системы</p>
                                <p class='text-xs text-slate-400'>Основная валюта для расчетов</p>
                            </div>
                            <select class='bg-white border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold outline-none'>
                                <option>RUB (₽)</option>
                                <option>USD ($)</option>
                                <option>EUR (€)</option>
                            </select>
                        </div>
                        <div class='flex items-center justify-between p-6 bg-slate-50 rounded-3xl'>
                            <div>
                                <p class='text-sm font-bold text-slate-800'>Темная тема</p>
                                <p class='text-xs text-slate-400'>Автоматическое переключение</p>
                            </div>
                            <div class='w-12 h-6 bg-slate-200 rounded-full relative cursor-pointer'>
                                <div class='absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-all'></div>
                            </div>
                        </div>
                    </div>
                    <div class='mt-10 flex justify-end'>
                        <button class='px-8 py-3 bg-slate-900 text-white font-bold rounded-2xl hover:bg-blue-600 transition-all'>Сохранить изменения</button>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
