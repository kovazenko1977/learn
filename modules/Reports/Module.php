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
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Центр отчетов</h2>
            <p class='text-slate-500 mt-2'>Генерация аналитических и финансовых документов.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                 <h4 class='text-lg font-bold text-slate-800 mb-6'>Популярные отчеты</h4>
                 <div class='space-y-4'>
                    <button class='w-full flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-blue-50 transition-colors group text-left'>
                        <div class='flex items-center'>
                            <div class='w-10 h-10 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-blue-500 mr-4 shadow-sm'><i class='fas fa-file-invoice-dollar'></i></div>
                            <div>
                                <p class='text-sm font-bold text-slate-700'>Отчет по выручке</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>За период (неделя/месяц)</p>
                            </div>
                        </div>
                        <i class='fas fa-chevron-right text-[10px] text-slate-300'></i>
                    </button>
                    <button class='w-full flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-blue-50 transition-colors group text-left'>
                        <div class='flex items-center'>
                            <div class='w-10 h-10 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-blue-500 mr-4 shadow-sm'><i class='fas fa-users-viewfinder'></i></div>
                            <div>
                                <p class='text-sm font-bold text-slate-700'>Загрузка номерного фонда</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Прогноз на 14 дней</p>
                            </div>
                        </div>
                        <i class='fas fa-chevron-right text-[10px] text-slate-300'></i>
                    </button>
                    <button class='w-full flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-blue-50 transition-colors group text-left'>
                        <div class='flex items-center'>
                            <div class='w-10 h-10 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-blue-500 mr-4 shadow-sm'><i class='fas fa-notes-medical'></i></div>
                            <div>
                                <p class='text-sm font-bold text-slate-700'>Медицинская статистика</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>По видам процедур</p>
                            </div>
                        </div>
                        <i class='fas fa-chevron-right text-[10px] text-slate-300'></i>
                    </button>
                 </div>
            </div>

            <div class='bg-slate-900 p-10 rounded-[2.5rem] text-white flex flex-col justify-center items-center text-center shadow-2xl shadow-slate-900/20'>
                <div class='w-20 h-20 rounded-[2rem] bg-white/10 flex items-center justify-center text-blue-400 text-3xl mb-6'>
                    <i class='fas fa-chart-pie'></i>
                </div>
                <h4 class='text-xl font-bold mb-2'>Конструктор отчетов</h4>
                <p class='text-slate-400 text-sm mb-8'>Создайте собственный шаблон отчета с произвольными полями и фильтрами.</p>
                <button class='px-10 py-4 bg-blue-600 text-white rounded-2xl font-bold text-xs hover:bg-blue-500 transition-all'>Запустить конструктор</button>
            </div>
        </div>
        ";
    }
}
