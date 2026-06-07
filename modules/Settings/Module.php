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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Конфигурация ERP-системы</h2>
            <p class='text-slate-500 mt-2 font-medium'>Глобальные настройки Sanatorium 2.0, права доступа и параметры безопасности.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-10'>
            <div class='lg:col-span-1 space-y-3'>
                <button class='w-full text-left p-5 rounded-2xl bg-blue-600 text-white font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-600/20 flex items-center justify-between group'>
                    <span class='flex items-center'><i class='fas fa-sliders mr-4'></i> Общие параметры</span>
                    <i class='fas fa-chevron-right text-[10px] opacity-50'></i>
                </button>
                <button class='w-full text-left p-5 rounded-2xl hover:bg-white text-slate-500 font-black text-xs uppercase tracking-widest flex items-center border border-transparent hover:border-slate-100 transition-all'>
                    <i class='fas fa-user-shield mr-4 text-slate-400'></i> Безопасность и RBAC
                </button>
                <button class='w-full text-left p-5 rounded-2xl hover:bg-white text-slate-500 font-black text-xs uppercase tracking-widest flex items-center border border-transparent hover:border-slate-100 transition-all'>
                    <i class='fas fa-puzzle-piece mr-4 text-slate-400'></i> Управление модулями
                </button>
                <button class='w-full text-left p-5 rounded-2xl hover:bg-white text-slate-500 font-black text-xs uppercase tracking-widest flex items-center border border-transparent hover:border-slate-100 transition-all'>
                    <i class='fas fa-database mr-4 text-slate-400'></i> База данных JSON/SQLite
                </button>
            </div>

            <div class='lg:col-span-2'>
                <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                    <h3 class='text-xl font-black text-slate-800 uppercase text-xs tracking-widest mb-10'>Основные реквизиты</h3>
                    <div class='space-y-8'>
                        <div class='flex flex-col sm:flex-row sm:items-center justify-between p-8 bg-slate-50 rounded-[2rem] border border-slate-100 gap-4'>
                            <div>
                                <p class='text-sm font-black text-slate-800'>Название организации</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase mt-1'>Для заголовков и печатных форм</p>
                            </div>
                            <input type='text' value='Санаторий \"Солнечный Берег\"' class='bg-white border border-slate-200 rounded-xl px-6 py-3 text-sm font-black text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 w-full sm:w-auto shadow-inner'>
                        </div>
                        <div class='flex flex-col sm:flex-row sm:items-center justify-between p-8 bg-slate-50 rounded-[2rem] border border-slate-100 gap-4'>
                            <div>
                                <p class='text-sm font-black text-slate-800'>Валюта учета</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase mt-1'>Основная денежная единица</p>
                            </div>
                            <select class='bg-white border border-slate-200 rounded-xl px-6 py-3 text-sm font-black text-slate-700 outline-none w-full sm:w-auto'>
                                <option>RUB (Российский рубль)</option>
                                <option>BYN (Белорусский рубль)</option>
                                <option>USD (Доллар США)</option>
                            </select>
                        </div>
                        <div class='flex flex-col sm:flex-row sm:items-center justify-between p-8 bg-slate-50 rounded-[2rem] border border-slate-100 gap-4'>
                            <div>
                                <p class='text-sm font-black text-slate-800'>Режим отладки (Debug)</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase mt-1'>Вывод технических логов в браузер</p>
                            </div>
                            <div class='w-14 h-8 bg-slate-200 rounded-full relative cursor-pointer group'>
                                <div class='absolute left-1 top-1 w-6 h-6 bg-white rounded-full transition-all shadow-md group-hover:scale-110'></div>
                            </div>
                        </div>
                    </div>
                    <div class='mt-12 flex justify-end'>
                        <button class='w-full sm:w-auto px-12 py-5 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-blue-600 transition-all shadow-2xl shadow-slate-900/10 active:scale-95'>Сохранить конфигурацию</button>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
