<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/reports', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Аналитика и Отчетность</h2>
            <p class='text-slate-500 mt-2 font-medium'>Формирование сводных ведомостей и статистических данных.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest mb-10'>Шаблоны отчетов</h3>
                <div class='space-y-4'>
                    <button class='w-full p-6 bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center justify-between hover:bg-blue-50 hover:border-blue-200 transition-all group'>
                        <div class='flex items-center'>
                            <i class='fas fa-file-invoice-dollar text-slate-400 group-hover:text-blue-600 mr-5 text-xl'></i>
                            <div class='text-left'>
                                <p class='text-sm font-black text-slate-800'>Финансовая ведомость</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Выручка и затраты за период</p>
                            </div>
                        </div>
                        <i class='fas fa-chevron-right text-slate-300 group-hover:text-blue-600'></i>
                    </button>
                    <button class='w-full p-6 bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center justify-between hover:bg-emerald-50 hover:border-emerald-200 transition-all group'>
                        <div class='flex items-center'>
                            <i class='fas fa-user-check text-slate-400 group-hover:text-emerald-600 mr-5 text-xl'></i>
                            <div class='text-left'>
                                <p class='text-sm font-black text-slate-800'>Загрузка номерного фонда</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Анализ заселяемости по корпусам</p>
                            </div>
                        </div>
                        <i class='fas fa-chevron-right text-slate-300 group-hover:text-emerald-600'></i>
                    </button>
                </div>
            </div>

            <div class='bg-slate-900 p-12 rounded-[3.5rem] text-white flex flex-col items-center justify-center text-center shadow-3xl shadow-slate-900/30 relative overflow-hidden'>
                <div class='relative z-10'>
                    <div class='w-24 h-24 bg-white/10 rounded-[2.5rem] flex items-center justify-center text-blue-400 text-4xl mb-8 mx-auto'><i class='fas fa-chart-pie'></i></div>
                    <h3 class='text-2xl font-black mb-4'>Конструктор отчетов</h3>
                    <p class='text-slate-400 text-sm mb-10 leading-relaxed'>Создавайте произвольные выборки данных по любым параметрам системы без программирования.</p>
                    <button class='px-12 py-5 bg-blue-600 text-white rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-blue-600/30 hover:bg-blue-500 hover:scale-105 active:scale-95 transition-all'>Запустить конструктор</button>
                </div>
                <i class='fas fa-rocket absolute -right-10 -bottom-10 text-[15rem] opacity-5 -rotate-12'></i>
            </div>
        </div>
        ";
    }
}
