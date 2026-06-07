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
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Питание и меню</h2>
            <p class='text-slate-500 mt-2'>Управление расписанием приемов пищи и диетическими столами.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <h4 class='text-lg font-bold text-slate-800 mb-6 flex items-center'>
                    <i class='fas fa-utensils text-blue-500 mr-3'></i> Сегодня в меню
                </h4>
                <div class='space-y-4'>
                    <div class='p-4 bg-slate-50 rounded-2xl'>
                        <p class='text-[10px] font-bold text-blue-500 uppercase mb-1'>Завтрак (08:00 - 10:00)</p>
                        <p class='text-sm font-bold text-slate-700'>Шведский стол, каши, омлеты</p>
                    </div>
                    <div class='p-4 bg-slate-50 rounded-2xl'>
                        <p class='text-[10px] font-bold text-emerald-500 uppercase mb-1'>Обед (13:00 - 15:00)</p>
                        <p class='text-sm font-bold text-slate-700'>Супы, мясные блюда, гарниры</p>
                    </div>
                    <div class='p-4 bg-slate-50 rounded-2xl'>
                        <p class='text-[10px] font-bold text-amber-500 uppercase mb-1'>Ужин (18:00 - 20:00)</p>
                        <p class='text-sm font-bold text-slate-700'>Легкие закуски, рыба, овощи</p>
                    </div>
                </div>
            </div>

            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm md:col-span-2'>
                <h4 class='text-lg font-bold text-slate-800 mb-6'>Статистика по столам</h4>
                <div class='grid grid-cols-2 sm:grid-cols-4 gap-4'>
                    <div class='p-6 bg-slate-50 rounded-3xl text-center'>
                        <p class='text-2xl font-bold text-slate-800'>84</p>
                        <p class='text-[9px] font-bold text-slate-400 uppercase'>Общий стол</p>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-3xl text-center'>
                        <p class='text-2xl font-bold text-blue-500'>12</p>
                        <p class='text-[9px] font-bold text-slate-400 uppercase'>Диета №5</p>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-3xl text-center'>
                        <p class='text-2xl font-bold text-emerald-500'>8</p>
                        <p class='text-[9px] font-bold text-slate-400 uppercase'>Диета №9</p>
                    </div>
                    <div class='p-6 bg-slate-50 rounded-3xl text-center'>
                        <p class='text-2xl font-bold text-rose-500'>4</p>
                        <p class='text-[9px] font-bold text-slate-400 uppercase'>Безглютен</p>
                    </div>
                </div>
                <div class='mt-8 pt-8 border-t border-slate-50'>
                    <button class='w-full py-4 bg-slate-900 text-white rounded-2xl font-bold text-sm hover:bg-blue-600 transition-all shadow-xl shadow-slate-900/10'>
                        Сформировать отчет для кухни
                    </button>
                </div>
            </div>
        </div>
        ";
    }
}
