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
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Служба Безопасности</h2>
            <p class='text-slate-500 mt-2'>Мониторинг КПП, видеонаблюдение и контроль доступа.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-10'>
            <div class='bg-slate-900 p-8 rounded-[2.5rem] text-white flex flex-col justify-between'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest'>Статус объекта</p>
                <p class='text-2xl font-bold text-emerald-400'>ПОД ОХРАНОЙ</p>
                <div class='mt-8 flex items-center space-x-2'>
                    <div class='w-2 h-2 bg-emerald-400 rounded-full animate-pulse'></div>
                    <span class='text-[10px] font-bold uppercase'>Системы в норме</span>
                </div>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1'>Активных камер</p>
                <p class='text-3xl font-bold text-slate-800'>64 / 64</p>
                <p class='text-[10px] text-emerald-500 mt-2 font-bold uppercase'>Связь установлена</p>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1'>Проходов через КПП</p>
                <p class='text-3xl font-bold text-slate-800'>142</p>
                <p class='text-[10px] text-slate-400 mt-2 font-bold uppercase'>За последние 24ч</p>
            </div>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center'>
                <h3 class='font-bold text-slate-800 text-sm uppercase tracking-wider'>Журнал событий (Live)</h3>
                <span class='text-[10px] font-bold text-slate-400'>ОБНОВЛЯЕТСЯ В РЕАЛЬНОМ ВРЕМЕНИ</span>
            </div>
            <div class='p-6 space-y-4'>
                <div class='flex items-center space-x-4 text-xs'>
                    <span class='text-slate-400 font-bold'>10:15:32</span>
                    <span class='w-2 h-2 bg-blue-500 rounded-full'></span>
                    <span class='font-bold text-slate-700'>КПП-1:</span>
                    <span class='text-slate-500'>Въезд а/м Toyota (А001АА777)</span>
                </div>
                <div class='flex items-center space-x-4 text-xs'>
                    <span class='text-slate-400 font-bold'>10:12:05</span>
                    <span class='w-2 h-2 bg-emerald-500 rounded-full'></span>
                    <span class='font-bold text-slate-700'>Корпус 2:</span>
                    <span class='text-slate-500'>Доступ разрешен (Карта: 4502 - Смирнова А.В.)</span>
                </div>
                <div class='flex items-center space-x-4 text-xs'>
                    <span class='text-slate-400 font-bold'>09:58:14</span>
                    <span class='w-2 h-2 bg-slate-300 rounded-full'></span>
                    <span class='font-bold text-slate-700'>Система:</span>
                    <span class='text-slate-500'>Плановая проверка датчиков дыма завершена</span>
                </div>
            </div>
        </div>
        ";
    }
}
