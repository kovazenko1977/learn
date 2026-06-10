<?php

declare(strict_types=1);

namespace App\Modules\Fitness;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/fitness', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter uppercase'>Спортивно-оздоровительный комплекс</h2>
            <p class='text-slate-500 mt-2 font-medium'>Управление тренажерным залом, залами ЛФК и расписанием персональных тренировок.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 gap-10'>
            <div class='bg-rose-600 p-10 rounded-[3.5rem] text-white shadow-3xl shadow-rose-600/30 relative overflow-hidden flex flex-col justify-between h-96'>
                <div class='relative z-10'>
                    <div class='inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-md rounded-xl text-[10px] font-black uppercase tracking-widest mb-8 border border-white/10'>
                        <span class='w-2 h-2 bg-emerald-400 rounded-full mr-3 animate-pulse'></span> Тренажерный зал открыт
                    </div>
                    <h3 class='text-4xl font-black tracking-tighter leading-tight'>Зона кардио и силовых нагрузок</h3>
                    <p class='text-rose-100 mt-4 font-medium'>Доступно для гостей с 07:00 до 22:30 ежедневно.</p>
                </div>
                <div class='relative z-10 flex items-center justify-between'>
                    <div class='flex flex-col'>
                        <span class='text-3xl font-black tabular-nums'>8 <span class='text-sm opacity-50'>чел.</span></span>
                        <span class='text-[9px] font-black uppercase opacity-60 mt-1'>Текущая загрузка</span>
                    </div>
                    <button class='px-8 py-4 bg-white text-rose-600 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl hover:scale-105 transition-all'>Показать Live-камеру</button>
                </div>
                <i class='fas fa-dumbbell absolute -right-16 -bottom-16 text-[20rem] opacity-5 rotate-12'></i>
            </div>

            <div class='bg-white p-12 rounded-[3.5rem] border border-slate-100 shadow-sm flex flex-col'>
                <div class='flex justify-between items-center mb-10'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-[0.2em]'>Групповые занятия</h3>
                    <span class='px-3 py-1 bg-slate-100 text-slate-400 rounded-lg text-[9px] font-black uppercase tracking-widest'>План на день</span>
                </div>
                <div class='space-y-6 flex-grow'>
                    <div class='flex items-center p-6 bg-slate-50 rounded-[2rem] border border-slate-50 hover:border-orange-200 transition-all group'>
                        <div class='w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center mr-6 group-hover:bg-orange-600 group-hover:text-white transition-all shadow-inner'><i class='fas fa-child-reaching text-xl'></i></div>
                        <div class='flex-grow'>
                            <p class='text-base font-black text-slate-800 tracking-tight'>Утренняя Йога / Растяжка</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase mt-1'>Зал №2 • 08:30 - 09:30 • Инстр: Анна К.</p>
                        </div>
                        <button class='w-10 h-10 rounded-xl bg-white text-slate-400 hover:text-orange-600 transition-all flex items-center justify-center border border-slate-200'><i class='fas fa-plus'></i></button>
                    </div>
                    <div class='flex items-center p-6 bg-slate-50 rounded-[2rem] border border-slate-50 hover:border-blue-200 transition-all group'>
                        <div class='w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mr-6 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-inner'><i class='fas fa-person-running text-xl'></i></div>
                        <div class='flex-grow'>
                            <p class='text-base font-black text-slate-800 tracking-tight'>Скандинавская ходьба</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase mt-1'>Парковая зона • 10:00 - 11:30</p>
                        </div>
                        <button class='w-10 h-10 rounded-xl bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center border border-slate-200'><i class='fas fa-plus'></i></button>
                    </div>
                </div>
                <div class='mt-10'>
                    <button class='w-full py-5 bg-slate-900 text-white rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-slate-900/10 hover:bg-rose-600 transition-all'>Заявка на перс. тренировку</button>
                </div>
            </div>
        </div>
        ";
    }
}
