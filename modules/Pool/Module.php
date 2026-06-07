<?php

declare(strict_types=1);

namespace App\Modules\Pool;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/pool', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Аква-зона и Бассейн</h2>
            <p class='text-slate-500 mt-2'>Контроль температуры воды, посещаемости и расписания сеансов.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
            <div class='bg-blue-600 p-8 rounded-[2.5rem] text-white shadow-2xl shadow-blue-600/20'>
                <p class='text-[10px] font-bold text-blue-200 uppercase tracking-widest mb-2'>Температура воды</p>
                <p class='text-4xl font-bold'>28.5°C</p>
                <div class='mt-6 flex items-center text-[10px] font-bold uppercase'>
                    <span class='w-2 h-2 bg-emerald-400 rounded-full mr-2'></span> Норма
                </div>
            </div>
             <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Загрузка сейчас</p>
                <p class='text-4xl font-bold text-slate-800'>18 / 30</p>
                <div class='mt-4 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-500' style='width: 60%'></div>
                </div>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Хлорирование</p>
                <p class='text-4xl font-bold text-emerald-500'>0.4 мг/л</p>
                <p class='text-[10px] text-slate-400 mt-2 font-bold'>Автоматический режим</p>
            </div>
        </div>

        <div class='mt-10 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
            <h3 class='font-bold text-slate-800 mb-6'>Расписание групповых занятий</h3>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                <div class='p-4 bg-slate-50 rounded-2xl flex items-center justify-between'>
                    <div>
                        <p class='text-sm font-bold text-slate-700'>Аквааэробика</p>
                        <p class='text-[10px] text-slate-400 font-bold uppercase'>10:00 - 11:00 • Инструктор: Елена</p>
                    </div>
                    <button class='px-4 py-2 bg-blue-500 text-white rounded-xl text-[10px] font-bold uppercase'>Записать</button>
                </div>
                <div class='p-4 bg-slate-50 rounded-2xl flex items-center justify-between'>
                    <div>
                        <p class='text-sm font-bold text-slate-700'>ЛФК в воде</p>
                        <p class='text-[10px] text-slate-400 font-bold uppercase'>12:30 - 13:30 • Инструктор: Игорь</p>
                    </div>
                    <button class='px-4 py-2 bg-blue-500 text-white rounded-xl text-[10px] font-bold uppercase'>Записать</button>
                </div>
            </div>
        </div>
        ";
    }
}
