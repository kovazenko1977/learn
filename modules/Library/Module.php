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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Библиотечный фонд</h2>
            <p class='text-slate-500 mt-2 font-medium'>Учет книжного фонда и регистрации выдачи литературы гостям.</p>
        </div>

        <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
            <div class='flex justify-between items-center mb-10'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Популярное сейчас</h3>
                <button class='px-6 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest'>Весь каталог</button>
            </div>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
                <div class='flex items-center space-x-6 p-6 bg-slate-50 rounded-[2rem] border border-slate-100'>
                    <div class='w-16 h-24 bg-slate-300 rounded shadow-2xl flex-shrink-0 flex items-center justify-center text-white'><i class='fas fa-book-open text-2xl'></i></div>
                    <div>
                        <p class='font-black text-slate-800 italic'>«Война и Мир»</p>
                        <p class='text-xs text-slate-500 font-bold'>Л.Н. Толстой</p>
                        <span class='inline-block mt-4 px-3 py-1 bg-emerald-100 text-emerald-600 text-[9px] font-black uppercase rounded-full'>В наличии</span>
                    </div>
                </div>
                <div class='flex items-center space-x-6 p-6 bg-slate-50 rounded-[2rem] border border-slate-100 opacity-60'>
                    <div class='w-16 h-24 bg-slate-300 rounded shadow-2xl flex-shrink-0 flex items-center justify-center text-white'><i class='fas fa-book-open text-2xl'></i></div>
                    <div>
                        <p class='font-black text-slate-800 italic'>«Мастер и Маргарита»</p>
                        <p class='text-xs text-slate-500 font-bold'>М.А. Булгаков</p>
                        <span class='inline-block mt-4 px-3 py-1 bg-rose-100 text-rose-600 text-[9px] font-black uppercase rounded-full'>Выдано (к. 201)</span>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
