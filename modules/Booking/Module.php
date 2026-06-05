<?php

declare(strict_types=1);

namespace App\Modules\Booking;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);

        $router->addRoute('GET', '/booking', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $rooms = $storage->find('rooms');

        $content = "
        <div class='mb-6'>
             <h3 class='text-2xl font-bold text-gray-900'>Шахматка бронирования</h3>
             <p class='text-gray-500'>Визуальное управление загрузкой санатория.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4'>
        ";

        if (empty($rooms)) {
             $content .= "<div class='col-span-full text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300 text-gray-500'>
                Нет данных для отображения. Пожалуйста, добавьте номера в модуле Размещение.
             </div>";
        } else {
            foreach ($rooms as $room) {
                $statusColor = match($room['status']) {
                    'свободен' => 'bg-green-500',
                    'занят' => 'bg-red-500',
                    'бронь' => 'bg-blue-500',
                    'уборка' => 'bg-yellow-500',
                    default => 'bg-gray-500'
                };

                $content .= "
                <div class='bg-white rounded-lg shadow p-4 border-l-4 border-l-" . ($room['status'] === 'свободен' ? 'green' : ($room['status'] === 'занят' ? 'red' : 'blue')) . "-500'>
                    <div class='flex justify-between items-start'>
                        <div>
                            <span class='text-xs font-bold text-gray-400 uppercase'>{$room['type']}</span>
                            <h4 class='text-xl font-bold'>№ {$room['number']}</h4>
                        </div>
                        <span class='px-2 py-1 text-xs font-medium text-white rounded {$statusColor}'>{$room['status']}</span>
                    </div>
                    <div class='mt-4 flex justify-between items-center'>
                        <span class='text-sm text-gray-500'>{$room['floor']} этаж</span>
                        <button class='text-blue-600 text-sm font-semibold hover:underline'>Забронировать</button>
                    </div>
                </div>
                ";
            }
        }

        $content .= "</div>";

        return $renderer->render('layout', [
            'title' => 'Бронирование - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
