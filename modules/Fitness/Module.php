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
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Фитнес и Спорт</h2>
            <p class='text-slate-500 mt-2'>Тренажерный зал, йога и персональные тренировки.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
            <div class='bg-rose-600 p-10 rounded-[3rem] text-white shadow-2xl shadow-rose-600/20 relative overflow-hidden'>
                <div class='relative z-10'>
                    <h3 class='text-2xl font-bold mb-2'>Тренажерный зал</h3>
                    <p class='text-rose-100 text-sm mb-8'>Открыт с 07:00 до 22:00</p>
                    <div class='flex items-center space-x-4'>
                        <div class='px-4 py-2 bg-white/20 rounded-xl text-xs font-bold uppercase'>8 человек сейчас</div>
                        <div class='px-4 py-2 bg-emerald-500 rounded-xl text-xs font-bold uppercase tracking-widest'>Открыто</div>
                    </div>
                </div>
                <i class='fas fa-dumbbell absolute -right-10 -bottom-10 text-[12rem] opacity-10 rotate-12'></i>
            </div>

            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <h3 class='text-xl font-bold text-slate-800 mb-6'>Занятия по расписанию</h3>
                <div class='space-y-6'>
                    <div class='flex items-start'>
                        <div class='w-12 h-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mr-4 flex-shrink-0'>
                            <i class='fas fa-child-reaching'></i>
                        </div>
                        <div>
                            <p class='text-sm font-bold text-slate-800'>Йога (Утренняя)</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase'>Зал №2 • 08:30</p>
                        </div>
                    </div>
                    <div class='flex items-start'>
                        <div class='w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center mr-4 flex-shrink-0'>
                            <i class='fas fa-person-running'></i>
                        </div>
                        <div>
                            <p class='text-sm font-bold text-slate-800'>Скандинавская ходьба</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase'>Сбор у главного входа • 10:00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
