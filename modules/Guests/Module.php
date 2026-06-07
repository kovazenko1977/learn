<?php

declare(strict_types=1);

namespace App\Modules\Guests;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/guests', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $bookings = $storage->find('bookings');

        $content = "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Регистратура и Картотека</h2>
            <p class='text-slate-500 mt-2 font-medium'>Единый реестр отдыхающих, архив медицинских и финансовых документов.</p>
        </div>

        <div class='bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-10 border-b border-slate-50 flex flex-col lg:flex-row justify-between items-center gap-6 bg-slate-50/20'>
                <div class='relative w-full lg:w-[480px]'>
                    <i class='fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300'></i>
                    <input type='text' placeholder='Поиск по фамилии, телефону или номеру паспорта...' class='w-full bg-white border border-slate-100 rounded-[1.2rem] pl-14 pr-6 py-4 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10 shadow-inner'>
                </div>
                <div class='flex w-full lg:w-auto gap-4'>
                    <button class='flex-grow lg:flex-none px-10 py-4 bg-slate-100 text-slate-600 rounded-[1.2rem] text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-all'>Экспорт БД</button>
                    <button class='flex-grow lg:flex-none px-10 py-4 bg-slate-900 text-white rounded-[1.2rem] text-xs font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-900/10'>+ Новый гость</button>
                </div>
            </div>
            <div class='overflow-x-auto'>
                <table class='w-full text-left'>
                    <thead>
                        <tr class='text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                            <th class='px-10 py-6'>Пациент / Контакты</th>
                            <th class='px-10 py-6'>Документы</th>
                            <th class='px-10 py-6'>Текущий статус</th>
                            <th class='px-10 py-6 text-right'>Действия</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-50'>";

        if (empty($bookings)) {
            $content .= "<tr><td colspan='4' class='px-10 py-24 text-center font-bold text-slate-300 text-lg'>Данные об отдыхающих отсутствуют</td></tr>";
        } else {
            foreach ($bookings as $b) {
                $content .= "
                        <tr class='hover:bg-slate-50/80 transition-colors group'>
                            <td class='px-10 py-8'>
                                <div class='flex items-center space-x-6'>
                                    <div class='w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 font-black text-lg shadow-inner group-hover:bg-blue-600 group-hover:text-white transition-all'>" . mb_substr($b['guest_name'], 0, 1) . "</div>
                                    <div>
                                        <p class='font-black text-slate-800 text-base'>{$b['guest_name']}</p>
                                        <p class='text-xs font-bold text-slate-400 mt-1'>+7 (___) ___-__-__ • {$b['guest_age']} лет</p>
                                    </div>
                                </div>
                            </td>
                            <td class='px-10 py-8'>
                                <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest'>Паспорт РФ:</p>
                                <p class='text-sm font-bold text-slate-700 mt-1'>" . ($b['passport'] ?? 'Не указан') . "</p>
                            </td>
                            <td class='px-10 py-8'>
                                <span class='px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-emerald-100'>Находится в корпусе</span>
                            </td>
                            <td class='px-10 py-8 text-right'>
                                <div class='flex justify-end space-x-2'>
                                    <button class='w-10 h-10 rounded-xl bg-slate-100 text-slate-400 hover:text-blue-600 hover:bg-white hover:shadow-md transition-all flex items-center justify-center border border-transparent hover:border-blue-100'><i class='fas fa-file-invoice'></i></button>
                                    <button onclick=\"wm.createWindow('Мед. карта: {$b['guest_name']}', '{$renderer->url('/medical/patient/'.$b['id'])}', 'fa-file-medical', 'text-rose-500')\" class='w-10 h-10 rounded-xl bg-slate-100 text-slate-400 hover:text-rose-500 hover:bg-white hover:shadow-md transition-all flex items-center justify-center border border-transparent hover:border-rose-100'><i class='fas fa-heart-pulse'></i></button>
                                </div>
                            </td>
                        </tr>";
            }
        }

        $content .= "
                    </tbody>
                </table>
            </div>
        </div>";

        return $renderer->render('layout', [
            'title' => 'Регистратура - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
