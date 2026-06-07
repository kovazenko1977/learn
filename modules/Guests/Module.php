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
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Картотека гостей</h2>
            <p class='text-slate-500 mt-2'>Централизованная база данных всех отдыхающих санатория.</p>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-6 sm:p-8 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/30'>
                <div class='relative w-full sm:w-96'>
                    <i class='fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300'></i>
                    <input type='text' placeholder='Поиск по ФИО или телефону...' class='w-full bg-white border border-slate-100 rounded-2xl pl-12 pr-4 py-3 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10'>
                </div>
                <button class='w-full sm:w-auto px-8 py-3 bg-slate-900 text-white rounded-2xl text-xs font-bold hover:bg-blue-600 transition-all'>
                    Добавить гостя
                </button>
            </div>
            <div class='overflow-x-auto'>
                <table class='w-full text-left'>
                    <thead>
                        <tr class='text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                            <th class='px-8 py-5'>Гость</th>
                            <th class='px-8 py-5'>Контакты</th>
                            <th class='px-8 py-5'>Статус</th>
                            <th class='px-8 py-5 text-right'>Действия</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-50'>";

        if (empty($bookings)) {
            $content .= "<tr><td colspan='4' class='px-8 py-20 text-center text-slate-400 font-medium'>База гостей пуста</td></tr>";
        } else {
            foreach ($bookings as $b) {
                $content .= "
                        <tr class='hover:bg-slate-50/80 transition-colors'>
                            <td class='px-8 py-6'>
                                <div class='flex items-center space-x-4'>
                                    <div class='w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 font-bold'>" . substr($b['guest_name'], 0, 1) . "</div>
                                    <div>
                                        <p class='font-bold text-slate-800'>{$b['guest_name']}</p>
                                        <p class='text-[10px] text-slate-400 font-bold uppercase tracking-tighter'>{$b['guest_age']} лет • " . ($b['guest_gender'] == 'male' ? 'Мужской' : 'Женский') . "</p>
                                    </div>
                                </div>
                            </td>
                            <td class='px-8 py-6'>
                                <p class='text-sm font-bold text-slate-600'>+7 (999) 000-00-00</p>
                                <p class='text-[10px] text-slate-400'>example@mail.ru</p>
                            </td>
                            <td class='px-8 py-6'>
                                <span class='px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-widest rounded-full'>Постоянный</span>
                            </td>
                            <td class='px-8 py-6 text-right'>
                                <button class='w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-300 hover:text-blue-500 transition-all'><i class='fas fa-id-card'></i></button>
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
            'title' => 'Гости - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
