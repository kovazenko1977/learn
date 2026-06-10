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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Товарный склад и МТО</h2>
            <p class='text-slate-500 mt-2 font-medium'>Контроль складских остатков, инвентаризация и управление закупками.</p>
        </div>

        <div class='bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-10 border-b border-slate-50 bg-slate-50/30 flex flex-col lg:flex-row justify-between items-center gap-6'>
                 <div>
                    <h3 class='text-sm font-black text-slate-800 uppercase tracking-widest'>Текущие остатки ТМЦ</h3>
                    <p class='text-xs text-slate-400 mt-1'>Автоматическое списание при оказании услуг</p>
                 </div>
                 <div class='flex gap-3'>
                    <button class='px-8 py-3 bg-white border border-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all'>Инвентаризация</button>
                    <button class='px-8 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-900/10'>+ Приход товара</button>
                 </div>
            </div>
            <div class='overflow-x-auto'>
            <table class='w-full text-left'>
                <thead>
                    <tr class='text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                        <th class='px-10 py-6'>Номенклатура</th>
                        <th class='px-10 py-6'>Группа учета</th>
                        <th class='px-10 py-6'>Ед. изм.</th>
                        <th class='px-10 py-6'>Количество</th>
                        <th class='px-10 py-6'>Состояние</th>
                    </tr>
                </thead>
                <tbody class='divide-y divide-slate-50'>
                    <tr class='hover:bg-slate-50 transition-colors'>
                        <td class='px-10 py-6 text-sm font-black text-slate-800'>Комплект постельного белья (Сатин)</td>
                        <td class='px-10 py-6 text-xs text-slate-400 font-bold uppercase'>Мягкий инвентарь</td>
                        <td class='px-10 py-6 text-xs text-slate-500 font-bold'>шт.</td>
                        <td class='px-10 py-6 text-sm font-black'>142</td>
                        <td class='px-10 py-6'><span class='px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-full border border-emerald-100'>В наличии</span></td>
                    </tr>
                    <tr class='hover:bg-slate-50 transition-colors'>
                        <td class='px-10 py-6 text-sm font-black text-slate-800'>Антисептик для рук (5 л)</td>
                        <td class='px-10 py-6 text-xs text-slate-400 font-bold uppercase'>Хозтовары / Химия</td>
                        <td class='px-10 py-6 text-xs text-slate-500 font-bold'>канистра</td>
                        <td class='px-10 py-6 text-sm font-black text-rose-600'>3</td>
                        <td class='px-10 py-6'><span class='px-3 py-1 bg-rose-50 text-rose-600 text-[9px] font-black uppercase rounded-full border border-rose-100'>Крит. остаток</span></td>
                    </tr>
                    <tr class='hover:bg-slate-50 transition-colors'>
                        <td class='px-10 py-6 text-sm font-black text-slate-800'>Тапочки одноразовые</td>
                        <td class='px-10 py-6 text-xs text-slate-400 font-bold uppercase'>Расходные материалы</td>
                        <td class='px-10 py-6 text-xs text-slate-500 font-bold'>пара</td>
                        <td class='px-10 py-6 text-sm font-black'>850</td>
                        <td class='px-10 py-6'><span class='px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-full border border-emerald-100'>В наличии</span></td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
        ";
    }
}
