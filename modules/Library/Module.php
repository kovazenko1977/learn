<?php

declare(strict_types=1);

namespace App\Modules\Library;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/library', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Библиотечный фонд</h2>
            <p class='text-slate-500 mt-2'>Каталог книг, периодических изданий и учет выдачи литературы.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-4 gap-6'>
            <div class='md:col-span-1 bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm'>
                 <h4 class='font-bold text-slate-800 mb-4'>Категории</h4>
                 <div class='space-y-1'>
                    <button class='w-full text-left p-2 rounded-xl bg-blue-50 text-blue-600 text-xs font-bold'>Классика</button>
                    <button class='w-full text-left p-2 rounded-xl hover:bg-slate-50 text-slate-500 text-xs font-bold transition-all'>Детективы</button>
                    <button class='w-full text-left p-2 rounded-xl hover:bg-slate-50 text-slate-500 text-xs font-bold transition-all'>История</button>
                    <button class='w-full text-left p-2 rounded-xl hover:bg-slate-50 text-slate-500 text-xs font-bold transition-all'>Детское</button>
                    <button class='w-full text-left p-2 rounded-xl hover:bg-slate-50 text-slate-500 text-xs font-bold transition-all'>Журналы</button>
                 </div>
            </div>

            <div class='md:col-span-3 space-y-6'>
                 <div class='bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center justify-between'>
                    <div class='flex items-center space-x-6'>
                        <div class='w-16 h-20 bg-slate-200 rounded shadow-inner flex items-center justify-center text-slate-400'><i class='fas fa-book-open'></i></div>
                        <div>
                            <p class='font-bold text-slate-800 italic'>«Война и Мир»</p>
                            <p class='text-xs text-slate-500 font-medium'>Л.Н. Толстой • 1867</p>
                        </div>
                    </div>
                    <span class='text-[10px] font-bold text-emerald-500 uppercase tracking-widest bg-emerald-50 px-3 py-1 rounded-full'>В наличии</span>
                 </div>
                 <div class='bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center justify-between'>
                    <div class='flex items-center space-x-6'>
                        <div class='w-16 h-20 bg-slate-200 rounded shadow-inner flex items-center justify-center text-slate-400'><i class='fas fa-book-open'></i></div>
                        <div>
                            <p class='font-bold text-slate-800 italic'>«Мастер и Маргарита»</p>
                            <p class='text-xs text-slate-500 font-medium'>М.А. Булгаков • 1966</p>
                        </div>
                    </div>
                    <span class='text-[10px] font-bold text-rose-400 uppercase tracking-widest bg-rose-50 px-3 py-1 rounded-full'>На руках (возврат 15.01)</span>
                 </div>
            </div>
        </div>
        ";
    }
}
