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
        $router->addRoute('GET', '/booking/create', [$this, 'create']);
        $router->addRoute('POST', '/booking/save', [$this, 'save']);
        $router->addRoute('GET', '/api/booking/available-rooms', [$this, 'getAvailableRooms']);
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
                        <a href='" . $renderer->url('/booking/create?room=' . $room['number']) . "' class='block text-center w-full py-2 bg-blue-50 text-blue-600 rounded-xl text-sm font-bold hover:bg-blue-600 hover:text-white transition-all'>
                            Оформить заезд
                        </a>
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

    public function create($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $params = $request->getBody();
        $selectedRoom = $params['room'] ?? '';

        $content = "
        <div class='max-w-4xl mx-auto'>
            <div class='mb-8'>
                <a href='" . $renderer->url('/booking') . "' class='text-blue-600 font-bold flex items-center mb-4'>
                    <i class='fas fa-arrow-left mr-2'></i> Назад к шахматке
                </a>
                <h2 class='text-3xl font-bold text-gray-800'>Новое бронирование</h2>
                <p class='text-gray-500'>Заполните данные для регистрации гостя в системе.</p>
            </div>

            <form action='" . $renderer->url('/booking/save') . "' method='POST' class='space-y-8'>
                <div class='bg-white p-8 rounded-3xl shadow-sm border border-gray-100'>
                    <h3 class='text-xl font-bold mb-6 text-gray-800 flex items-center'>
                        <i class='fas fa-info-circle text-blue-500 mr-3'></i> Основная информация
                    </h3>
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Выбор номера</label>
                            <select name='room_number' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3'>
                                <option value=''>Выберите номер...</option>
                                <option value='101' " . ($selectedRoom == '101' ? 'selected' : '') . ">№101 (Стандарт)</option>
                                <option value='102' " . ($selectedRoom == '102' ? 'selected' : '') . ">№102 (Стандарт)</option>
                                <option value='302' " . ($selectedRoom == '302' ? 'selected' : '') . ">№302 (Люкс)</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Тип размещения</label>
                            <select name='type' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3'>
                                <option value='full'>Номер целиком</option>
                                <option value='place'>По местам</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Дата заезда</label>
                            <input type='date' name='date_from' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3' required>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Дата выезда</label>
                            <input type='date' name='date_to' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3' required>
                        </div>
                    </div>
                </div>

                <div class='bg-white p-8 rounded-3xl shadow-sm border border-gray-100'>
                    <h3 class='text-xl font-bold mb-6 text-gray-800 flex items-center'>
                        <i class='fas fa-user-tag text-green-500 mr-3'></i> Данные гостя
                    </h3>
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                        <div class='md:col-span-2'>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>ФИО гостя</label>
                            <input type='text' name='guest_name' placeholder='Иванов Иван Иванович' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3' required>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Телефон</label>
                            <input type='tel' name='guest_phone' placeholder='+7 (___) ___-__-__' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3'>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Паспортные данные</label>
                            <input type='text' name='guest_passport' placeholder='Серия и номер' class='w-full border-gray-200 rounded-xl focus:ring-blue-500 p-3'>
                        </div>
                    </div>
                </div>

                <div class='flex items-center justify-between p-6 bg-blue-600 rounded-3xl shadow-xl shadow-blue-100'>
                    <div class='text-white'>
                        <p class='text-sm opacity-80'>Итоговая стоимость:</p>
                        <p class='text-3xl font-bold'>0.00 ₽</p>
                    </div>
                    <button type='submit' class='px-10 py-4 bg-white text-blue-600 font-bold rounded-2xl shadow-lg hover:bg-blue-50 transition-all'>
                        Подтвердить бронирование
                    </button>
                </div>
            </form>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Создание брони - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function save($request, $response): void
    {
        $data = $request->getBody();
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $bookingId = uniqid('BK_');
        $storage->insert('bookings', [
            'id' => $bookingId,
            'room_number' => $data['room_number'],
            'guest_name' => $data['guest_name'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'status' => 'confirmed',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Update room occupancy for demo
        $room = $storage->findOne('rooms', ['number' => $data['room_number']]);
        if ($room) {
            $room['occupied_places'] = ($room['occupied_places'] ?? 0) + 1;
            if ($room['occupied_places'] >= $room['places']) {
                $room['status'] = 'занят';
            }
            $storage->update('rooms', $room['id'] ?? $room['number'], $room);
        }

        $response->redirect($this->container->get(\App\View\Renderer::class)->url('/booking'));
    }

    public function getAvailableRooms($request, $response): void
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $rooms = $storage->find('rooms', ['status' => 'свободен']);
        $response->json($rooms);
    }
}
