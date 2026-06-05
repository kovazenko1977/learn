<?php

declare(strict_types=1);

namespace App\Modules\Accommodation;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);

        $router->addRoute('GET', '/accommodation', [$this, 'index']);
        $router->addRoute('GET', '/api/accommodation/rooms', [$this, 'getRooms']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $rooms = $storage->find('rooms');

        $content = "
        <div class='bg-white shadow overflow-hidden sm:rounded-lg'>
            <div class='px-4 py-5 sm:px-6 flex justify-between items-center'>
                <div>
                    <h3 class='text-lg leading-6 font-medium text-gray-900'>Управление номерами</h3>
                    <p class='mt-1 max-w-2xl text-sm text-gray-500'>Список корпусов, этажей и номеров.</p>
                </div>
                <button class='bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition'>Добавить номер</button>
            </div>
            <div class='border-t border-gray-200'>
                <table class='min-w-full divide-y divide-gray-200'>
                    <thead class='bg-gray-50'>
                        <tr>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Номер</th>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Тип</th>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Статус</th>
                            <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Действия</th>
                        </tr>
                    </thead>
                    <tbody class='bg-white divide-y divide-gray-200'>
        ";

        if (empty($rooms)) {
            $content .= "<tr><td colspan='4' class='px-6 py-4 text-center text-gray-500'>Нет доступных номеров. Загрузите демо-данные.</td></tr>";
        } else {
            foreach ($rooms as $room) {
                $statusColor = $room['status'] === 'свободен' ? 'green' : ($room['status'] === 'занят' ? 'red' : 'yellow');
                $content .= "
                <tr>
                    <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>{$room['number']}</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>{$room['type']}</td>
                    <td class='px-6 py-4 whitespace-nowrap'>
                        <span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{$statusColor}-100 text-{$statusColor}-800'>
                            {$room['status']}
                        </span>
                    </td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>
                        <a href='#' class='text-blue-600 hover:text-blue-900'>Редактировать</a>
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
            'title' => 'Размещение - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function getRooms($request, $response): void
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $response->json($storage->find('rooms'));
    }
}
