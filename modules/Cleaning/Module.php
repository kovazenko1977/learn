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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Диспетчерская службы Housekeeping</h2>
            <p class='text-slate-500 mt-2 font-medium'>Оперативное управление санитарным состоянием номерного фонда и графиками горничных.</p>
        </div>

        <div class='grid grid-cols-2 md:grid-cols-4 gap-6 mb-10'>
            <div class='bg-rose-50 p-8 rounded-[2rem] border border-rose-100 shadow-sm'>
                <p class='text-[10px] font-black text-rose-400 uppercase tracking-widest mb-2'>Требует уборки</p>
                <p class='text-3xl font-black text-rose-600 tabular-nums'>12</p>
            </div>
            <div class='bg-blue-50 p-8 rounded-[2rem] border border-blue-100 shadow-sm'>
                <p class='text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2'>В процессе</p>
                <p class='text-3xl font-black text-blue-600 tabular-nums'>3</p>
            </div>
            <div class='bg-emerald-50 p-8 rounded-[2rem] border border-emerald-100 shadow-sm'>
                <p class='text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2'>Проверено</p>
                <p class='text-3xl font-black text-emerald-600 tabular-nums'>45</p>
            </div>
            <div class='bg-amber-50 p-8 rounded-[2rem] border border-amber-100 shadow-sm'>
                <p class='text-[10px] font-black text-amber-400 uppercase tracking-widest mb-2'>Тех. работы</p>
                <p class='text-3xl font-black text-amber-600 tabular-nums'>2</p>
            </div>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center'>
                 <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>План-график работ</h3>
                 <button class='px-6 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest'>Сформировать наряд</button>
            </div>
            <div class='overflow-x-auto'>
            <table class='w-full text-left'>
                <thead>
                    <tr class='text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                        <th class='px-8 py-6'>Объект / Категория</th>
                        <th class='px-8 py-6'>Вид регламентных работ</th>
                        <th class='px-8 py-6'>Текущий статус</th>
                        <th class='px-8 py-6 text-right'>Действия</th>
                    </tr>
                </thead>
                <tbody class='divide-y divide-slate-50'>";

        foreach ($rooms as $r) {
            $content .= "
                    <tr class='hover:bg-slate-50/50 transition-colors'>
                        <td class='px-8 py-7'>
                            <p class='font-black text-slate-800'>№ {$r['number']}</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase tracking-tighter'>{$r['type']}</p>
                        </td>
                        <td class='px-8 py-7'>
                            <p class='text-sm font-bold text-slate-600'>Текущая влажная уборка</p>
                            <p class='text-[10px] text-slate-400'>Норматив: 20 мин.</p>
                        </td>
                        <td class='px-8 py-7'>
                            <span class='px-4 py-1.5 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase tracking-widest rounded-full border border-emerald-100'>Санобработка пройдена</span>
                        </td>
                        <td class='px-8 py-7 text-right'>
                            <button class='px-5 py-2.5 bg-slate-100 hover:bg-blue-600 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all'>Назначить</button>
                        </td>
                    </tr>";
        }

        $content .= "
                </tbody>
            </table>
            </div>
        </div>";

        return $content;
    }
}
