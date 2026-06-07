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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Аква-комплекс</h2>
            <p class='text-slate-500 mt-2 font-medium'>Мониторинг параметров воды и посещаемости бассейна.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
            <div class='bg-blue-600 p-10 rounded-[3rem] text-white shadow-2xl shadow-blue-600/20'>
                <p class='text-[10px] font-black text-blue-200 uppercase tracking-widest mb-3'>Температура воды</p>
                <p class='text-4xl font-black'>28.5°C</p>
                <div class='mt-8 flex items-center text-[10px] font-black uppercase tracking-widest'>
                    <span class='w-3 h-3 bg-emerald-400 rounded-full mr-3 shadow-[0_0_10px_#34d399] animate-pulse'></span> В пределах нормы
                </div>
            </div>
            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3'>Заполнение зоны</p>
                <p class='text-4xl font-black text-slate-800'>18 / 30</p>
                <div class='mt-6 w-full h-2 bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-500' style='width: 60%'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3'>Уровень pH</p>
                <p class='text-4xl font-black text-emerald-500'>7.2</p>
                <p class='text-[10px] text-slate-400 mt-6 font-bold uppercase tracking-widest'>Оптимально</p>
            </div>
        </div>
        ";
    }
}
