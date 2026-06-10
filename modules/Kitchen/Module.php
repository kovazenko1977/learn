<?php

declare(strict_types=1);

namespace App\Modules\Kitchen;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/kitchen', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Управление диетическим питанием</h2>
            <p class='text-slate-500 mt-2 font-medium'>Контроль меню, учет диет и планирование загрузки пищеблока.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-8'>
            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <h4 class='text-sm font-black text-slate-800 mb-8 uppercase tracking-widest flex items-center'>
                    <i class='fas fa-utensils text-orange-500 mr-3 text-lg'></i> Актуальное меню
                </h4>
                <div class='space-y-6'>
                    <div class='p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100'>
                        <p class='text-[10px] font-black text-blue-500 uppercase tracking-widest mb-2'>Завтрак • 08:30</p>
                        <p class='text-sm font-bold text-slate-700 leading-relaxed'>Овсяная каша на молоке, омлет с сыром, творожная запеканка, чай/кофе.</p>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100'>
                        <p class='text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-2'>Обед • 13:30</p>
                        <p class='text-sm font-bold text-slate-700 leading-relaxed'>Борщ сибирский, запеченная куриная грудка, гречневая крупа, салат витаминный.</p>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100'>
                        <p class='text-[10px] font-black text-amber-500 uppercase tracking-widest mb-2'>Ужин • 18:30</p>
                        <p class='text-sm font-bold text-slate-700 leading-relaxed'>Рыба на пару, овощное рагу, кефир, печенье диетическое.</p>
                    </div>
                </div>
            </div>

            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm lg:col-span-2'>
                <h4 class='text-sm font-black text-slate-800 mb-8 uppercase tracking-widest'>Сводная ведомость по диет-столам</h4>
                <div class='grid grid-cols-2 sm:grid-cols-4 gap-6'>
                    <div class='p-8 bg-slate-50 rounded-[2rem] text-center border border-slate-100'>
                        <p class='text-3xl font-black text-slate-800 tabular-nums'>84</p>
                        <p class='text-[10px] font-black text-slate-400 uppercase mt-2'>Общий стол</p>
                    </div>
                    <div class='p-8 bg-blue-50 rounded-[2rem] text-center border border-blue-100'>
                        <p class='text-3xl font-black text-blue-600 tabular-nums'>12</p>
                        <p class='text-[10px] font-black text-blue-400 uppercase mt-2'>Диета №5</p>
                    </div>
                    <div class='p-8 bg-emerald-50 rounded-[2rem] text-center border border-emerald-100'>
                        <p class='text-3xl font-black text-emerald-600 tabular-nums'>8</p>
                        <p class='text-[10px] font-black text-emerald-400 uppercase mt-2'>Диета №9</p>
                    </div>
                    <div class='p-8 bg-rose-50 rounded-[2rem] text-center border border-rose-100'>
                        <p class='text-3xl font-black text-rose-600 tabular-nums'>4</p>
                        <p class='text-[10px] font-black text-rose-400 uppercase mt-2'>Без глютена</p>
                    </div>
                </div>

                <div class='mt-10 p-8 bg-slate-900 rounded-[2.5rem] flex items-center justify-between shadow-2xl shadow-slate-900/20'>
                    <div class='text-white'>
                        <p class='text-sm font-bold'>Заявка на продукты (Склад)</p>
                        <p class='text-xs text-slate-400 mt-1'>Сформировано на завтра, 14.01</p>
                    </div>
                    <button class='px-10 py-4 bg-white text-slate-900 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-orange-500 hover:text-white transition-all'>Передать в работу</button>
                </div>
            </div>
        </div>
        ";
    }
}
