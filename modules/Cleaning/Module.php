<?php

declare(strict_types=1);

namespace App\Modules\Cleaning;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/cleaning', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $rooms = $storage->find('rooms');

        $content = "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>График уборки</h2>
            <p class='text-slate-500 mt-2'>Контроль чистоты и технического обслуживания номеров.</p>
        </div>

        <div class='grid grid-cols-2 md:grid-cols-4 gap-4 mb-10'>
            <div class='bg-rose-50 p-6 rounded-3xl border border-rose-100'>
                <p class='text-[10px] font-bold text-rose-400 uppercase tracking-widest mb-1'>Нужна уборка</p>
                <p class='text-2xl font-bold text-rose-600'>12</p>
            </div>
            <div class='bg-blue-50 p-6 rounded-3xl border border-blue-100'>
                <p class='text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1'>В процессе</p>
                <p class='text-2xl font-bold text-blue-600'>3</p>
            </div>
            <div class='bg-emerald-50 p-6 rounded-3xl border border-emerald-100'>
                <p class='text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1'>Готовы</p>
                <p class='text-2xl font-bold text-emerald-600'>45</p>
            </div>
            <div class='bg-amber-50 p-6 rounded-3xl border border-amber-100'>
                <p class='text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1'>Тех. работы</p>
                <p class='text-2xl font-bold text-amber-600'>2</p>
            </div>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <table class='w-full text-left'>
                <thead class='bg-slate-50/50 border-b border-slate-50'>
                    <tr class='text-[10px] font-bold text-slate-400 uppercase tracking-widest'>
                        <th class='px-8 py-5'>Номер</th>
                        <th class='px-8 py-5'>Тип уборки</th>
                        <th class='px-8 py-5'>Статус</th>
                        <th class='px-8 py-5 text-right'>Действия</th>
                    </tr>
                </thead>
                <tbody class='divide-y divide-slate-50'>";

        foreach ($rooms as $r) {
            $content .= "
                    <tr class='hover:bg-slate-50/50 transition-colors'>
                        <td class='px-8 py-6'>
                            <p class='font-bold text-slate-800'>№ {$r['number']}</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase'>{$r['type']}</p>
                        </td>
                        <td class='px-8 py-6 text-sm font-semibold text-slate-600'>
                            Текущая (ежедневная)
                        </td>
                        <td class='px-8 py-6'>
                            <span class='px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-widest rounded-full'>Чисто</span>
                        </td>
                        <td class='px-8 py-6 text-right'>
                            <button class='px-4 py-2 bg-slate-100 hover:bg-slate-900 hover:text-white rounded-xl text-[10px] font-bold transition-all'>Назначить</button>
                        </td>
                    </tr>";
        }

        $content .= "
                </tbody>
            </table>
        </div>";

        return $content;
    }
}
