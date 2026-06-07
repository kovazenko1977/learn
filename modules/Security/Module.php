<?php

declare(strict_types=1);

namespace App\Modules\Security;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/security', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Пост охраны и Безопасность</h2>
            <p class='text-slate-500 mt-2 font-medium'>Ситуационный центр: КПП, видеонаблюдение и пожарная сигнализация.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8 mb-10'>
            <div class='bg-slate-900 p-10 rounded-[2.5rem] text-white flex flex-col justify-between shadow-2xl shadow-slate-900/30'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4'>Режим охраны</p>
                <p class='text-3xl font-black text-emerald-400'>ШТАТНЫЙ</p>
                <div class='mt-10 flex items-center space-x-3'>
                    <div class='w-3 h-3 bg-emerald-400 rounded-full shadow-[0_0_12px_#10b981] animate-pulse'></div>
                    <span class='text-[10px] font-black uppercase tracking-widest text-emerald-400/80'>Все периметры закрыты</span>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Камеры (Online)</p>
                <p class='text-4xl font-black text-slate-800 tabular-nums'>64 / 64</p>
                <div class='mt-6 w-full h-1.5 bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-500 w-full'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4'>Тревожные кнопки</p>
                <p class='text-4xl font-black text-slate-300 tabular-nums'>0</p>
                <p class='text-[10px] text-slate-400 mt-6 font-bold uppercase tracking-widest'>Активных угроз нет</p>
            </div>
        </div>

        <div class='bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-8 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Протокол событий КПП (Live)</h3>
                <span class='text-[9px] font-black text-blue-600 bg-blue-50 px-3 py-1 rounded-full'>СИНХРОНИЗАЦИЯ...</span>
            </div>
            <div class='p-10 space-y-6'>
                <div class='flex items-center space-x-6 text-sm border-b border-slate-50 pb-6 last:border-0 last:pb-0'>
                    <span class='text-slate-400 font-black tabular-nums'>10:15:32</span>
                    <div class='w-2 h-2 bg-blue-500 rounded-full'></div>
                    <span class='font-black text-slate-800 uppercase tracking-tighter w-24'>КПП-1 (Въезд)</span>
                    <span class='text-slate-600 font-medium'>Автомобиль **Toyota Camry (А001АА777)** проследовал на территорию.</span>
                </div>
                <div class='flex items-center space-x-6 text-sm border-b border-slate-50 pb-6 last:border-0 last:pb-0'>
                    <span class='text-slate-400 font-black tabular-nums'>10:12:05</span>
                    <div class='w-2 h-2 bg-emerald-500 rounded-full'></div>
                    <span class='font-black text-slate-800 uppercase tracking-tighter w-24'>Корпус 2</span>
                    <span class='text-slate-600 font-medium'>Доступ предоставлен по смарт-карте **4502** (Смирнова А.В., гость).</span>
                </div>
                <div class='flex items-center space-x-6 text-sm border-b border-slate-50 pb-6 last:border-0 last:pb-0'>
                    <span class='text-slate-400 font-black tabular-nums'>09:58:14</span>
                    <div class='w-2 h-2 bg-slate-300 rounded-full'></div>
                    <span class='font-black text-slate-800 uppercase tracking-tighter w-24'>Система</span>
                    <span class='text-slate-600 font-medium'>Автоматическая самодиагностика датчиков дыма в Корпусе №1 завершена. Ошибок нет.</span>
                </div>
            </div>
        </div>
        ";
    }
}
