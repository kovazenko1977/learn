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
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Диспетчер автотранспорта</h2>
            <p class='text-slate-500 mt-2 font-medium'>Управление трансферами гостей, служебными поездками и графиком работы водителей.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-2 gap-8'>
            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <div class='flex justify-between items-center mb-8'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>План рейсов на сегодня</h3>
                    <button class='text-blue-600 text-[10px] font-black uppercase tracking-widest'>+ Новый заказ</button>
                </div>
                <div class='space-y-4'>
                    <div class='p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100 flex items-center justify-between group hover:border-blue-200 transition-all'>
                        <div class='flex items-center'>
                            <div class='w-12 h-12 rounded-2xl bg-blue-500 text-white flex items-center justify-center mr-5 shadow-lg shadow-blue-200'>
                                <i class='fas fa-plane-arrival'></i>
                            </div>
                            <div>
                                <p class='text-sm font-black text-slate-800'>Аэропорт -> Санаторий</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Рейс SU-1240 • 14:30</p>
                            </div>
                        </div>
                        <span class='px-3 py-1 bg-blue-100 text-blue-600 rounded-lg text-[9px] font-black uppercase'>Ожидание</span>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100 flex items-center justify-between group hover:border-emerald-200 transition-all'>
                        <div class='flex items-center'>
                            <div class='w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center mr-5 shadow-lg shadow-emerald-200'>
                                <i class='fas fa-train'></i>
                            </div>
                            <div>
                                <p class='text-sm font-black text-slate-800'>Санаторий -> Ж/Д Вокзал</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Группа 4 чел. • 16:15</p>
                            </div>
                        </div>
                        <span class='px-3 py-1 bg-emerald-100 text-emerald-600 rounded-lg text-[9px] font-black uppercase'>В пути</span>
                    </div>
                </div>
            </div>

            <div class='bg-slate-900 p-10 rounded-[3rem] text-white shadow-2xl shadow-slate-900/30 relative overflow-hidden'>
                <h3 class='font-black uppercase text-xs tracking-[0.2em] mb-8 relative z-10'>Состояние автопарка</h3>
                <div class='grid grid-cols-2 gap-6 relative z-10'>
                    <div class='p-6 bg-white/5 rounded-[1.5rem] border border-white/5'>
                        <p class='text-3xl font-black tabular-nums'>8</p>
                        <p class='text-[10px] text-slate-500 font-bold uppercase mt-2'>Всего единиц</p>
                    </div>
                    <div class='p-6 bg-white/5 rounded-[1.5rem] border border-white/5'>
                        <p class='text-3xl font-black tabular-nums text-emerald-400'>5</p>
                        <p class='text-[10px] text-slate-500 font-bold uppercase mt-2'>Готовы к рейсу</p>
                    </div>
                    <div class='p-6 bg-white/5 rounded-[1.5rem] border border-white/5'>
                        <p class='text-3xl font-black tabular-nums text-blue-400'>2</p>
                        <p class='text-[10px] text-slate-500 font-bold uppercase mt-2'>На линии</p>
                    </div>
                    <div class='p-6 bg-white/5 rounded-[1.5rem] border border-white/5'>
                        <p class='text-3xl font-black tabular-nums text-rose-500'>1</p>
                        <p class='text-[10px] text-slate-500 font-bold uppercase mt-2'>В тех. зоне</p>
                    </div>
                </div>
                <i class='fas fa-car-side absolute -right-10 -bottom-10 text-[12rem] opacity-5 -rotate-12'></i>
            </div>
        </div>
        ";
    }
}
