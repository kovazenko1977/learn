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
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Транспортная служба</h2>
            <p class='text-slate-500 mt-2'>Трансферы, логистика и управление автопарком санатория.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <h3 class='font-bold text-slate-800 mb-6 flex items-center'>
                    <i class='fas fa-clock text-blue-500 mr-3'></i> Ближайшие рейсы
                </h3>
                <div class='space-y-4'>
                    <div class='p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between'>
                        <div>
                            <p class='text-sm font-bold text-slate-700'>Ж/Д Вокзал -> Санаторий</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase'>Микроавтобус • 14:00</p>
                        </div>
                        <span class='px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-[9px] font-bold'>ОЖИДАНИЕ</span>
                    </div>
                    <div class='p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between'>
                        <div>
                            <p class='text-sm font-bold text-slate-700'>Аэропорт -> Санаторий</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase'>Toyota Camry • 16:30</p>
                        </div>
                        <span class='px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-[9px] font-bold'>В ПУТИ</span>
                    </div>
                </div>
            </div>

            <div class='bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl shadow-slate-900/20'>
                <h3 class='font-bold mb-6'>Статус автопарка</h3>
                <div class='grid grid-cols-2 gap-4'>
                    <div class='p-4 bg-white/10 rounded-2xl'>
                        <p class='text-2xl font-bold'>8</p>
                        <p class='text-[9px] text-slate-400 font-bold uppercase'>Всего машин</p>
                    </div>
                    <div class='p-4 bg-white/10 rounded-2xl'>
                        <p class='text-2xl font-bold text-emerald-400'>5</p>
                        <p class='text-[9px] text-slate-400 font-bold uppercase'>Свободно</p>
                    </div>
                    <div class='p-4 bg-white/10 rounded-2xl'>
                        <p class='text-2xl font-bold text-blue-400'>2</p>
                        <p class='text-[9px] text-slate-400 font-bold uppercase'>На выезде</p>
                    </div>
                    <div class='p-4 bg-white/10 rounded-2xl'>
                        <p class='text-2xl font-bold text-rose-400'>1</p>
                        <p class='text-[9px] text-slate-400 font-bold uppercase'>В ремонте</p>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
