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

        $guests = $storage->find('guests');

        $content = "
        <div class='mb-8 flex justify-between items-center'>
            <div>
                <h2 class='text-3xl font-bold text-gray-800'>Картотека гостей</h2>
                <p class='text-gray-500 mt-1'>Централизованная база данных пациентов и отдыхающих.</p>
            </div>
            <div class='flex space-x-3'>
                <button class='bg-white border text-gray-700 px-4 py-2 rounded-xl font-bold flex items-center hover:bg-gray-50'>
                    <i class='fas fa-file-export mr-2'></i> Экспорт
                </button>
                <button class='bg-blue-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-blue-200 flex items-center hover:bg-blue-700'>
                    <i class='fas fa-user-plus mr-2'></i> Регистрация
                </button>
            </div>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
            <div class='bg-blue-600 p-6 rounded-2xl text-white'>
                <h4 class='text-blue-200 text-xs font-bold uppercase mb-2'>Всего в базе</h4>
                <p class='text-4xl font-bold'>" . count($guests) . "</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100'>
                <h4 class='text-gray-400 text-xs font-bold uppercase mb-2'>Новых за неделю</h4>
                <p class='text-4xl font-bold text-gray-800'>24</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100'>
                <h4 class='text-gray-400 text-xs font-bold uppercase mb-2'>Активные пациенты</h4>
                <p class='text-4xl font-bold text-gray-800'>86</p>
            </div>
        </div>

        <div class='bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden'>
            <table class='w-full text-left'>
                <thead class='bg-gray-50 border-b'>
                    <tr class='text-gray-400 text-xs uppercase tracking-wider'>
                        <th class='px-6 py-4 font-medium'>Гость</th>
                        <th class='px-6 py-4 font-medium'>Статус</th>
                        <th class='px-6 py-4 font-medium'>Баланс</th>
                        <th class='px-6 py-4 font-medium'>Действия</th>
                    </tr>
                </thead>
                <tbody class='divide-y'>
        ";

        if (empty($guests)) {
            $content .= "<tr><td colspan='4' class='px-6 py-12 text-center text-gray-500'>Гости не найдены.</td></tr>";
        } else {
            foreach ($guests as $guest) {
                $content .= "
                <tr class='hover:bg-gray-50 transition-colors'>
                    <td class='px-6 py-4'>
                        <div class='flex items-center space-x-3'>
                            <div class='w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold'>
                                " . substr($guest['name'], 0, 1) . "
                            </div>
                            <div>
                                <p class='font-bold text-gray-800'>{$guest['name']}</p>
                                <p class='text-xs text-gray-500'>{$guest['phone']} • {$guest['birthdate']}</p>
                            </div>
                        </div>
                    </td>
                    <td class='px-6 py-4'>
                        <span class='px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase'>Лечение</span>
                    </td>
                    <td class='px-6 py-4 font-bold text-gray-800'>0 ₽</td>
                    <td class='px-6 py-4'>
                        <div class='flex space-x-2'>
                            <button class='w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all'>
                                <i class='fas fa-eye text-xs'></i>
                            </button>
                            <button class='w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all'>
                                <i class='fas fa-pen text-xs'></i>
                            </button>
                            <button class='w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-orange-600 hover:text-white transition-all'>
                                <i class='fas fa-notes-medical text-xs'></i>
                            </button>
                        </div>
                    </td>
                </tr>
                ";
            }
        }

        $content .= "
                    </tbody>
                </table>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Гости - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
