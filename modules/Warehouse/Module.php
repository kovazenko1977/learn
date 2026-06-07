<?php

declare(strict_types=1);

namespace App\Modules\Warehouse;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/warehouse', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Складской учет</h2>
            <p class='text-slate-500 mt-2'>Управление запасами расходных материалов, продуктов и медикаментов.</p>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-8 border-b border-slate-50 bg-slate-50/20 flex justify-between items-center'>
                 <h3 class='text-sm font-bold text-slate-800 uppercase tracking-widest'>Остатки на складе</h3>
                 <button class='px-6 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-bold uppercase'>Поступление</button>
            </div>
            <table class='w-full text-left'>
                <thead>
                    <tr class='text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                        <th class='px-8 py-5'>Наименование</th>
                        <th class='px-8 py-5'>Категория</th>
                        <th class='px-8 py-5'>Количество</th>
                        <th class='px-8 py-5'>Статус</th>
                    </tr>
                </thead>
                <tbody class='divide-y divide-slate-50'>
                    <tr class='hover:bg-slate-50 transition-colors'>
                        <td class='px-8 py-5 text-sm font-bold text-slate-700'>Постельное белье (комплект)</td>
                        <td class='px-8 py-5 text-xs text-slate-400 font-bold'>Хозтовары</td>
                        <td class='px-8 py-5 text-sm font-bold'>124 шт.</td>
                        <td class='px-8 py-5'><span class='px-2 py-0.5 bg-emerald-50 text-emerald-500 text-[9px] font-bold uppercase rounded-full'>Норма</span></td>
                    </tr>
                    <tr class='hover:bg-slate-50 transition-colors'>
                        <td class='px-8 py-5 text-sm font-bold text-slate-700'>Маски медицинские</td>
                        <td class='px-8 py-5 text-xs text-slate-400 font-bold'>Медицина</td>
                        <td class='px-8 py-5 text-sm font-bold'>2 500 шт.</td>
                        <td class='px-8 py-5'><span class='px-2 py-0.5 bg-emerald-50 text-emerald-500 text-[9px] font-bold uppercase rounded-full'>Норма</span></td>
                    </tr>
                    <tr class='hover:bg-slate-50 transition-colors'>
                        <td class='px-8 py-5 text-sm font-bold text-slate-700'>Средство для дезинфекции</td>
                        <td class='px-8 py-5 text-xs text-slate-400 font-bold'>Химия</td>
                        <td class='px-8 py-5 text-sm font-bold'>5 л.</td>
                        <td class='px-8 py-5'><span class='px-2 py-0.5 bg-rose-50 text-rose-500 text-[9px] font-bold uppercase rounded-full'>Мало</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        ";
    }
}
