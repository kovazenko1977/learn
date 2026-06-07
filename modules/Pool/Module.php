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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter uppercase'>Аква-термальный комплекс</h2>
            <p class='text-slate-500 mt-2 font-medium'>Инженерный и административный контроль зоны бассейнов, саун и водолечебницы.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10'>
            <div class='bg-blue-600 p-8 rounded-[2.5rem] text-white shadow-2xl shadow-blue-600/30 relative overflow-hidden'>
                <p class='text-[10px] font-black text-blue-200 uppercase tracking-widest mb-3'>Температура воды</p>
                <p class='text-3xl font-black tabular-nums'>+28.5°C</p>
                <div class='mt-6 flex items-center text-[10px] font-black uppercase tracking-widest'>
                    <span class='w-2.5 h-2.5 bg-emerald-400 rounded-full mr-3 shadow-[0_0_12px_#34d399] animate-pulse'></span> Стабильно
                </div>
                <i class='fas fa-temperature-half absolute -right-4 -bottom-4 text-7xl opacity-10'></i>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3'>Загрузка зеркала воды</p>
                <p class='text-3xl font-black text-slate-800 tabular-nums'>18 / 30 <span class='text-xs text-slate-400 font-bold'>чел.</span></p>
                <div class='mt-6 w-full h-1.5 bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-500' style='width: 60%'></div>
                </div>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3'>Водородный показатель</p>
                <p class='text-3xl font-black text-emerald-500 tabular-nums'>7.2 pH</p>
                <p class='text-[10px] text-slate-400 mt-6 font-bold uppercase tracking-widest'>Норма: 7.0 - 7.4</p>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3'>Свободный хлор</p>
                <p class='text-3xl font-black text-slate-800 tabular-nums'>0.42 <span class='text-xs text-slate-400 font-bold'>мг/л</span></p>
                <div class='mt-6 flex items-center text-[10px] font-black text-emerald-500 uppercase'>
                    <i class='fas fa-check-circle mr-2'></i> Автоматика ОК
                </div>
            </div>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-10'>
            <div class='lg:col-span-2 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <div class='flex justify-between items-center mb-10'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Расписание групповых программ</h3>
                    <div class='flex space-x-2'>
                        <button class='px-4 py-2 bg-slate-50 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest'>На сегодня</button>
                    </div>
                </div>
                <div class='space-y-4'>
                    <div class='p-6 bg-slate-50 rounded-[2rem] border border-slate-100 flex items-center justify-between group hover:border-blue-200 transition-all'>
                        <div class='flex items-center'>
                            <div class='w-14 h-14 rounded-2xl bg-white flex items-center justify-center mr-6 shadow-sm text-blue-500'><i class='fas fa-person-swimming text-xl'></i></div>
                            <div>
                                <p class='text-base font-black text-slate-800'>Аквааэробика (Силовая)</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1'>10:00 - 11:00 • Тренер: Елена Маркова</p>
                            </div>
                        </div>
                        <button class='px-8 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all'>Записать</button>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-[2rem] border border-slate-100 flex items-center justify-between group hover:border-blue-200 transition-all'>
                        <div class='flex items-center'>
                            <div class='w-14 h-14 rounded-2xl bg-white flex items-center justify-center mr-6 shadow-sm text-blue-500'><i class='fas fa-wheelchair-move text-xl'></i></div>
                            <div>
                                <p class='text-base font-black text-slate-800'>ЛФК в воде (Реабилитация)</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1'>12:30 - 13:30 • Тренер: Игорь Васильев</p>
                            </div>
                        </div>
                        <button class='px-8 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all'>Записать</button>
                    </div>
                </div>
            </div>

            <div class='bg-slate-900 p-10 rounded-[3rem] text-white flex flex-col justify-between shadow-2xl shadow-slate-900/40'>
                <div>
                    <h3 class='font-black uppercase text-xs tracking-[0.2em] mb-8'>Технический статус</h3>
                    <div class='space-y-6'>
                        <div class='flex items-center justify-between'>
                            <span class='text-xs text-slate-400 font-bold'>Фильтрация (Основная)</span>
                            <span class='text-[10px] font-black text-emerald-400 uppercase'>Активна</span>
                        </div>
                        <div class='flex items-center justify-between'>
                            <span class='text-xs text-slate-400 font-bold'>Нагрев (Котел №2)</span>
                            <span class='text-[10px] font-black text-emerald-400 uppercase'>Работает</span>
                        </div>
                        <div class='flex items-center justify-between'>
                            <span class='text-xs text-slate-400 font-bold'>Вентиляция зоны</span>
                            <span class='text-[10px] font-black text-emerald-400 uppercase'>100% мощн.</span>
                        </div>
                    </div>
                </div>
                <div class='mt-12'>
                    <button class='w-full py-4 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all'>Журнал сан-контроля</button>
                </div>
            </div>
        </div>
        ";
    }
}
