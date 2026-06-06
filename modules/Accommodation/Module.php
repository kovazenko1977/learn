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
        $router->addRoute('POST', '/accommodation/add', [$this, 'addRoom']);
        $router->addRoute('GET', '/accommodation/housekeeping', [$this, 'housekeeping']);
        $router->addRoute('POST', '/api/accommodation/status', [$this, 'updateStatus']);
        $router->addRoute('GET', '/api/accommodation/rooms', [$this, 'getRooms']);
    }

    public function addRoom($request, $response): void
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $data = $request->getBody();

        if (!empty($data['number'])) {
            $storage->insert('rooms', [
                'number' => $data['number'],
                'type' => $data['type'] ?? 'Стандарт',
                'floor' => $data['floor'] ?? 1,
                'status' => 'свободен',
                'price' => $data['price'] ?? 3500,
                'places' => $data['places'] ?? 1
            ]);
        }

        $renderer = $this->container->get(\App\View\Renderer::class);
        header('Location: ' . $renderer->url('/accommodation'));
        exit;
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $rooms = $storage->find('rooms');

        $content = "
        <div class='mb-8 flex justify-between items-end'>
            <div>
                <h2 class='text-3xl font-bold text-gray-800'>Управление номерным фондом</h2>
                <p class='text-gray-500 mt-1'>Конфигурация корпусов, категорий и мест.</p>
            </div>
            <button onclick=\"document.getElementById('addRoomModal').classList.remove('hidden')\" class='bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl shadow-lg shadow-blue-200 transition-all flex items-center'>
                <i class='fas fa-plus mr-2'></i> Добавить номер
            </button>
        </div>

        <div id='addRoomModal' class='fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4'>
            <div class='bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden'>
                <div class='bg-blue-600 p-6 text-white'>
                    <h3 class='text-xl font-bold'>Новый номер</h3>
                </div>
                <form action='{$renderer->url('/accommodation/add')}' method='POST' class='p-6 space-y-4'>
                    <div>
                        <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Номер комнаты</label>
                        <input type='text' name='number' required class='w-full border-gray-200 rounded-lg focus:ring-blue-500'>
                    </div>
                    <div class='grid grid-cols-2 gap-4'>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Тип</label>
                            <select name='type' class='w-full border-gray-200 rounded-lg'>
                                <option>Стандарт</option>
                                <option>Люкс</option>
                                <option>Полулюкс</option>
                                <option>Апартаменты</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Этаж</label>
                            <input type='number' name='floor' value='1' class='w-full border-gray-200 rounded-lg'>
                        </div>
                    </div>
                    <div class='grid grid-cols-2 gap-4'>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Мест</label>
                            <input type='number' name='places' value='1' class='w-full border-gray-200 rounded-lg'>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Цена (₽)</label>
                            <input type='number' name='price' value='3500' class='w-full border-gray-200 rounded-lg'>
                        </div>
                    </div>
                    <div class='flex justify-end space-x-3 pt-4 border-t'>
                        <button type='button' onclick=\"document.getElementById('addRoomModal').classList.add('hidden')\" class='px-4 py-2 text-gray-500 font-bold'>Отмена</button>
                        <button type='submit' class='px-6 py-2 bg-blue-600 text-white rounded-lg font-bold shadow-lg shadow-blue-100'>Создать</button>
                    </div>
                </form>
            </div>
        </div>

        <div class='bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden'>
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

    public function housekeeping($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $rooms = $storage->find('rooms');

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Служба Housekeeping</h2>
            <p class='text-gray-500'>Мониторинг чистоты и технического состояния номеров.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8'>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Требует уборки</p>
                <p class='text-3xl font-bold text-orange-500'>8</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>В процессе</p>
                <p class='text-3xl font-bold text-blue-500'>3</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Проверено</p>
                <p class='text-3xl font-bold text-green-500'>24</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Тех. неисправность</p>
                <p class='text-3xl font-bold text-red-500'>2</p>
            </div>
        </div>

        <div class='bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden'>
            <table class='w-full text-left'>
                <thead class='bg-gray-50 border-b'>
                    <tr class='text-[10px] font-bold text-gray-400 uppercase tracking-widest'>
                        <th class='px-8 py-4'>Номер</th>
                        <th class='px-8 py-4'>Тип</th>
                        <th class='px-8 py-4'>Статус чистоты</th>
                        <th class='px-8 py-4'>Последняя уборка</th>
                        <th class='px-8 py-4 text-right'>Действия</th>
                    </tr>
                </thead>
                <tbody class='divide-y'>";

        foreach ($rooms as $room) {
            $content .= "
                    <tr class='hover:bg-gray-50 transition-colors'>
                        <td class='px-8 py-4 font-bold text-gray-800'>№ {$room['number']}</td>
                        <td class='px-8 py-4 text-gray-500 text-sm'>{$room['type']}</td>
                        <td class='px-8 py-4'>
                            <span class='px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase'>Чисто</span>
                        </td>
                        <td class='px-8 py-4 text-gray-400 text-sm'>Сегодня, 09:15</td>
                        <td class='px-8 py-4 text-right'>
                            <button class='text-blue-600 hover:text-blue-800 font-bold text-xs'>Назначить уборку</button>
                        </td>
                    </tr>";
        }

        $content .= "
                </tbody>
            </table>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Housekeeping - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function updateStatus($request, $response): void
    {
        $data = $request->getBody();
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $room = $storage->findOne('rooms', ['number' => $data['number']]);
        if ($room) {
            $room['status'] = $data['status'];
            $storage->save('rooms', $room);
        }

        $response->json(['success' => true]);
    }
}
