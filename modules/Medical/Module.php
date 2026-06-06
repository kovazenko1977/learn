<?php

declare(strict_types=1);

namespace App\Modules\Medical;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/medical', [$this, 'index']);
        $router->addRoute('GET', '/medical/patients', [$this, 'patients']);
        $router->addRoute('GET', '/medical/history/{id}', [$this, 'history']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8 flex justify-between items-center'>
            <div>
                <h2 class='text-3xl font-bold text-gray-800'>Медицинский центр</h2>
                <p class='text-gray-500 mt-1'>Назначения, процедуры и медицинские карты пациентов.</p>
            </div>
            <button class='bg-blue-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg shadow-blue-200 flex items-center hover:bg-blue-700'>
                <i class='fas fa-plus mr-2'></i> Новое назначение
            </button>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-8'>
            <div class='lg:col-span-2 space-y-6'>
                <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                    <h3 class='font-bold text-gray-800 mb-6 flex items-center'>
                        <i class='fas fa-clock text-blue-500 mr-2'></i> Ближайшие процедуры
                    </h3>
                    <div class='space-y-4'>
                        <div class='flex items-center justify-between p-4 bg-blue-50 rounded-xl border border-blue-100'>
                            <div class='flex items-center space-x-4'>
                                <div class='text-center'>
                                    <p class='text-xs font-bold text-blue-600 uppercase'>10:30</p>
                                    <p class='text-[10px] text-blue-400'>Каб. 204</p>
                                </div>
                                <div class='w-px h-8 bg-blue-200'></div>
                                <div>
                                    <p class='font-bold text-gray-800'>Грязелечение общее</p>
                                    <p class='text-xs text-gray-500'>Пациент: Николаев А.С.</p>
                                </div>
                            </div>
                            <button class='w-8 h-8 rounded-lg bg-white text-green-500 flex items-center justify-center shadow-sm'>
                                <i class='fas fa-check'></i>
                            </button>
                        </div>
                        <div class='flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100 opacity-60'>
                            <div class='flex items-center space-x-4'>
                                <div class='text-center'>
                                    <p class='text-xs font-bold text-gray-400 uppercase'>11:15</p>
                                    <p class='text-[10px] text-gray-300'>Каб. 102</p>
                                </div>
                                <div class='w-px h-8 bg-gray-200'></div>
                                <div>
                                    <p class='font-bold text-gray-800'>Массаж шейно-воротниковой зоны</p>
                                    <p class='text-xs text-gray-500'>Пациент: Васильева Е.М.</p>
                                </div>
                            </div>
                            <button class='w-8 h-8 rounded-lg bg-white text-gray-300 flex items-center justify-center'>
                                <i class='fas fa-ellipsis-h'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                    <h3 class='font-bold text-gray-800 mb-6'>Аналитика здоровья (общая)</h3>
                    <div class='h-64 flex items-end justify-between space-x-4 px-4'>
                        <div class='w-full bg-blue-100 rounded-t-lg' style='height: 45%' title='Гипертония'></div>
                        <div class='w-full bg-green-100 rounded-t-lg' style='height: 85%' title='Опорно-двигательный'></div>
                        <div class='w-full bg-orange-100 rounded-t-lg' style='height: 30%' title='ЖКТ'></div>
                        <div class='w-full bg-purple-100 rounded-t-lg' style='height: 60%' title='Нервная система'></div>
                        <div class='w-full bg-red-100 rounded-t-lg' style='height: 15%' title='Прочее'></div>
                    </div>
                    <div class='flex justify-between mt-4 text-[10px] font-bold text-gray-400 uppercase tracking-tighter text-center'>
                        <div class='w-full'>Сердце</div>
                        <div class='w-full'>Спина</div>
                        <div class='w-full'>Желудок</div>
                        <div class='w-full'>Стресс</div>
                        <div class='w-full'>Другое</div>
                    </div>
                </div>
            </div>

            <div class='space-y-6'>
                <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                    <h3 class='font-bold text-gray-800 mb-4'>Статистика кабинетов</h3>
                    <div class='space-y-3'>
                        <div class='flex justify-between text-xs mb-1'>
                            <span class='text-gray-500'>Водолечебница</span>
                            <span class='font-bold'>92%</span>
                        </div>
                        <div class='w-full h-1.5 bg-gray-100 rounded-full overflow-hidden'>
                            <div class='bg-blue-500 h-full' style='width: 92%'></div>
                        </div>

                        <div class='flex justify-between text-xs mb-1 mt-4'>
                            <span class='text-gray-500'>Грязелечебница</span>
                            <span class='font-bold'>78%</span>
                        </div>
                        <div class='w-full h-1.5 bg-gray-100 rounded-full overflow-hidden'>
                            <div class='bg-green-500 h-full' style='width: 78%'></div>
                        </div>
                    </div>
                </div>

                <div class='bg-gradient-to-br from-purple-600 to-indigo-700 p-6 rounded-2xl text-white shadow-xl shadow-indigo-100'>
                    <i class='fas fa-info-circle text-2xl mb-4 opacity-50'></i>
                    <h4 class='font-bold mb-2'>Система назначений</h4>
                    <p class='text-xs opacity-80 leading-relaxed'>Автоматический учет противопоказаний и совместимости процедур включен. При назначении учитывайте аллергический статус пациента.</p>
                </div>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Медицинский блок - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function patients($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $guests = $storage->find('guests');

        $content = "
        <div class='mb-8 flex justify-between items-center'>
            <div>
                <h2 class='text-3xl font-bold text-gray-800'>Картотека пациентов</h2>
                <p class='text-gray-500 mt-1'>История лечения и текущие назначения.</p>
            </div>
        </div>

        <div class='bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden'>
            <table class='w-full text-left'>
                <thead class='bg-gray-50 border-b'>
                    <tr class='text-[10px] font-bold text-gray-400 uppercase tracking-widest'>
                        <th class='px-8 py-4'>ФИО</th>
                        <th class='px-8 py-4'>Диагноз</th>
                        <th class='px-8 py-4'>Лечащий врач</th>
                        <th class='px-8 py-4 text-right'>Действия</th>
                    </tr>
                </thead>
                <tbody class='divide-y'>
                    <tr class='hover:bg-gray-50 transition-colors'>
                        <td class='px-8 py-4 font-bold text-gray-800'>Николаев Александр Сергеевич</td>
                        <td class='px-8 py-4 text-gray-500 text-sm'>Остеохондроз позвоночника</td>
                        <td class='px-8 py-4 text-gray-500 text-sm'>Иванов И.И.</td>
                        <td class='px-8 py-4 text-right'>
                            <a href='{$renderer->url('/medical/history/1')}' class='text-blue-600 font-bold text-xs'>История болезни</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Пациенты - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function history($request, $response, $id): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <a href='{$renderer->url('/medical/patients')}' class='text-blue-600 font-bold mb-4 flex items-center'>
                <i class='fas fa-arrow-left mr-2'></i> К списку пациентов
            </a>
            <h2 class='text-3xl font-bold text-gray-800'>История болезни #{$id}</h2>
            <p class='text-gray-500'>Пациент: Николаев Александр Сергеевич</p>
        </div>

        <div class='space-y-6'>
            <div class='bg-white p-8 rounded-3xl border border-gray-100 shadow-sm'>
                <h3 class='font-bold text-gray-800 mb-4'>Анамнез</h3>
                <p class='text-gray-600 leading-relaxed text-sm'>Жалобы на боли в поясничном отделе позвоночника в течение 2-х недель. Ранее проходил лечение в 2022 году. Аллергических реакций на грязелечение не выявлено.</p>
            </div>

            <div class='bg-white p-8 rounded-3xl border border-gray-100 shadow-sm'>
                <h3 class='font-bold text-gray-800 mb-4'>Назначенные процедуры</h3>
                <ul class='space-y-3'>
                    <li class='flex items-center justify-between p-4 bg-blue-50 rounded-2xl'>
                        <span class='font-medium text-blue-800'>Подводный душ-массаж</span>
                        <span class='text-xs font-bold text-blue-500'>10 сеансов</span>
                    </li>
                    <li class='flex items-center justify-between p-4 bg-green-50 rounded-2xl'>
                        <span class='font-medium text-green-800'>Электрофорез с новокаином</span>
                        <span class='text-xs font-bold text-green-500'>5 сеансов</span>
                    </li>
                </ul>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Медицинская карта - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
