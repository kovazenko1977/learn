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
        <div class='mb-8'>
             <h2 class='text-3xl font-bold text-gray-800'>Шахматка размещения</h2>
             <p class='text-gray-500 mt-1'>Визуальный контроль занятости по местам.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'>
        ";

        if (empty($rooms)) {
             $content .= "<div class='col-span-full text-center py-20 bg-white rounded-2xl border-2 border-dashed border-gray-200 text-gray-400'>
                <i class='fas fa-door-closed text-5xl mb-4'></i>
                <p>Нет данных для отображения. Добавьте номера в модуле Размещение.</p>
             </div>";
        } else {
            foreach ($rooms as $room) {
                $places = $room['places'] ?? 1;
                $occupied = $room['occupied_places'] ?? 0;

                $content .= "
                <div class='bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow'>
                    <div class='p-5 border-b bg-gray-50 flex justify-between items-center'>
                        <div>
                            <p class='text-xs font-bold text-blue-600 uppercase tracking-widest'>{$room['type']}</p>
                            <h4 class='text-xl font-bold text-gray-800'>№ {$room['number']}</h4>
                        </div>
                        <div class='text-right text-xs text-gray-400'>
                            <p>{$room['floor']} этаж</p>
                            <p class='font-bold text-gray-800'>{$room['price']} ₽ / сут</p>
                        </div>
                    </div>
                    <div class='p-5'>
                        <div class='flex items-center justify-between mb-4'>
                            <span class='text-xs font-semibold text-gray-500'>Занятость мест:</span>
                            <span class='text-xs font-bold " . ($occupied >= $places ? 'text-red-500' : 'text-green-500') . "'>$occupied / $places</span>
                        </div>
                        <div class='grid grid-cols-5 gap-2 mb-6'>
                ";

                for ($i = 1; $i <= $places; $i++) {
                    $isOccupied = $i <= $occupied;
                    $color = $isOccupied ? 'bg-red-500' : 'bg-green-400';
                    $icon = $isOccupied ? 'fa-user' : 'fa-bed';
                    $content .= "
                        <div class='h-10 rounded-lg {$color} flex items-center justify-center text-white cursor-pointer hover:opacity-80 transition-opacity' title='Место $i'>
                            <i class='fas {$icon} text-xs'></i>
                        </div>
                    ";
                }

                $content .= "
                        </div>
                        <button class='w-full py-2 bg-blue-50 text-blue-600 rounded-xl text-sm font-bold hover:bg-blue-600 hover:text-white transition-all'>
                            Оформить заезд
                        </button>
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
