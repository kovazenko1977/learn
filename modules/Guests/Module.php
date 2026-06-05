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
        <div class='bg-white shadow overflow-hidden sm:rounded-lg'>
            <div class='px-4 py-5 sm:px-6 flex justify-between items-center'>
                <div>
                    <h3 class='text-lg leading-6 font-medium text-gray-900'>Картотека гостей</h3>
                    <p class='mt-1 max-w-2xl text-sm text-gray-500'>Список всех пациентов и отдыхающих.</p>
                </div>
                <button class='bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition'>Регистрация нового гостя</button>
            </div>
            <div class='border-t border-gray-200'>
                <table class='min-w-full divide-y divide-gray-200'>
                    <thead class='bg-gray-50'>
                        <tr>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>ФИО</th>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Дата рождения</th>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Телефон</th>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Действия</th>
                        </tr>
                    </thead>
                    <tbody class='bg-white divide-y divide-gray-200'>
        ";

        if (empty($guests)) {
            $content .= "<tr><td colspan='4' class='px-6 py-4 text-center text-gray-500'>Гости не найдены.</td></tr>";
        } else {
            foreach ($guests as $guest) {
                $content .= "
                <tr>
                    <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>{$guest['name']}</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>{$guest['birthdate']}</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>{$guest['phone']}</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>
                        <a href='#' class='text-blue-600 hover:text-blue-900'>Карточка</a>
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
