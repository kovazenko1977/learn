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
        $router->addRoute('GET', '/medical/patient/{id}', [$this, 'patientCard']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $bookings = $storage->find('bookings', ['status' => 'confirmed']);

        $content = "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Медицинский центр</h2>
            <p class='text-slate-500 mt-2'>Управление назначениями, процедурами и электронными медицинскими картами.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8 mb-12'>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Пациентов сегодня</p>
                <p class='text-4xl font-bold text-slate-900'>" . count($bookings) . "</p>
                <div class='mt-4 h-1.5 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-500 w-2/3'></div>
                </div>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Процедур выполнено</p>
                <p class='text-4xl font-bold text-emerald-500'>142</p>
                <div class='mt-4 h-1.5 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-emerald-500 w-full'></div>
                </div>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Критиков</p>
                <p class='text-4xl font-bold text-rose-500'>0</p>
                <div class='mt-4 h-1.5 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-rose-500 w-0'></div>
                </div>
            </div>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50'>
                <h3 class='font-bold text-slate-800'>Активные пациенты</h3>
                <div class='flex space-x-2'>
                    <div class='relative'>
                        <i class='fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs'></i>
                        <input type='text' placeholder='Поиск по ФИО...' class='bg-white border-slate-100 rounded-xl pl-10 pr-4 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500/10 outline-none'>
                    </div>
                </div>
            </div>
            <table class='w-full text-left'>
                <thead>
                    <tr class='text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                        <th class='px-8 py-5'>Пациент</th>
                        <th class='px-8 py-5'>Номер</th>
                        <th class='px-8 py-5'>Назначения</th>
                        <th class='px-8 py-5'>Статус</th>
                        <th class='px-8 py-5 text-right'>Действия</th>
                    </tr>
                </thead>
                <tbody class='divide-y divide-slate-50'>";

        foreach ($bookings as $b) {
            $content .= "
                    <tr class='hover:bg-blue-50/30 transition-colors group'>
                        <td class='px-8 py-6'>
                            <div class='flex items-center space-x-4'>
                                <div class='w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-bold text-slate-400 group-hover:bg-blue-500 group-hover:text-white transition-all'>
                                    " . substr($b['guest_name'], 0, 1) . "
                                </div>
                                <div>
                                    <p class='font-bold text-slate-800 text-sm'>{$b['guest_name']}</p>
                                    <p class='text-[10px] text-slate-400 font-bold uppercase'>" . ($b['guest_gender'] == 'male' ? 'Мужчина' : 'Женщина') . ", {$b['guest_age']} лет</p>
                                </div>
                            </div>
                        </td>
                        <td class='px-8 py-6'>
                            <span class='px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-bold'>№ {$b['room_number']}</span>
                        </td>
                        <td class='px-8 py-6'>
                            <div class='flex -space-x-2'>
                                <div class='w-7 h-7 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center' title='ЛФК'><i class='fas fa-person-walking text-[10px] text-blue-500'></i></div>
                                <div class='w-7 h-7 rounded-full bg-emerald-100 border-2 border-white flex items-center justify-center' title='Массаж'><i class='fas fa-hands-holding text-[10px] text-emerald-500'></i></div>
                                <div class='w-7 h-7 rounded-full bg-purple-100 border-2 border-white flex items-center justify-center' title='Ванны'><i class='fas fa-water text-[10px] text-purple-500'></i></div>
                            </div>
                        </td>
                        <td class='px-8 py-6'>
                            <span class='flex items-center text-[10px] font-bold text-emerald-500 uppercase tracking-tighter'>
                                <span class='w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse'></span>
                                На лечении
                            </span>
                        </td>
                        <td class='px-8 py-6 text-right'>
                            <button onclick=\"wm.createWindow('Карта: {$b['guest_name']}', '{$renderer->url('/medical/patient/'.$b['id'])}', 'fa-file-medical')\" class='px-4 py-2 bg-slate-100 hover:bg-slate-900 hover:text-white text-slate-600 text-[10px] font-bold rounded-xl transition-all'>Открыть карту</button>
                        </td>
                    </tr>";
        }

        $content .= "
                </tbody>
            </table>
        </div>";

        return $renderer->render('layout', [
            'title' => 'Медицина - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function patientCard($request, $response, $id): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $patient = $storage->findOne('bookings', ['id' => $id]);

        if (!$patient) return "Пациент не найден";

        return "
        <div class='flex flex-col space-y-8'>
            <div class='flex items-start space-x-8'>
                <div class='w-32 h-32 rounded-[2.5rem] bg-slate-100 flex items-center justify-center text-4xl text-slate-300 border-4 border-white shadow-xl'>
                    <i class='fas fa-user-injured'></i>
                </div>
                <div class='flex-grow'>
                    <h2 class='text-3xl font-bold text-slate-800'>{$patient['guest_name']}</h2>
                    <p class='text-slate-400 font-bold uppercase text-[10px] tracking-widest mt-1'>ID: $id • " . ($patient['guest_gender'] == 'male' ? 'Мужчина' : 'Женщина') . ", {$patient['guest_age']} лет</p>

                    <div class='mt-6 grid grid-cols-3 gap-4'>
                        <div class='p-4 bg-blue-50 rounded-2xl'>
                            <p class='text-[10px] font-bold text-blue-400 uppercase mb-1'>Диагноз</p>
                            <p class='text-sm font-bold text-blue-900'>Общее оздоровление</p>
                        </div>
                        <div class='p-4 bg-emerald-50 rounded-2xl'>
                            <p class='text-[10px] font-bold text-emerald-400 uppercase mb-1'>Аллергии</p>
                            <p class='text-sm font-bold text-emerald-900'>Не выявлено</p>
                        </div>
                        <div class='p-4 bg-rose-50 rounded-2xl'>
                            <p class='text-[10px] font-bold text-rose-400 uppercase mb-1'>Ограничения</p>
                            <p class='text-sm font-bold text-rose-900'>Без нагрузок</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class='grid grid-cols-2 gap-8'>
                <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                    <h3 class='font-bold text-slate-800 mb-6 flex justify-between items-center'>
                        Лист назначений
                        <button class='text-blue-500 text-xs'><i class='fas fa-plus'></i></button>
                    </h3>
                    <div class='space-y-4'>
                        <div class='flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100'>
                            <div class='w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center mr-4'><i class='fas fa-person-swimming'></i></div>
                            <div class='flex-grow'>
                                <p class='text-sm font-bold text-slate-800'>Бассейн</p>
                                <p class='text-[10px] text-slate-400'>Ежедневно, 10:00</p>
                            </div>
                            <span class='text-[10px] font-bold text-blue-500 uppercase'>7 / 10</span>
                        </div>
                         <div class='flex items-center p-4 bg-slate-50 rounded-2xl border border-slate-100'>
                            <div class='w-10 h-10 rounded-xl bg-purple-500 text-white flex items-center justify-center mr-4'><i class='fas fa-spa'></i></div>
                            <div class='flex-grow'>
                                <p class='text-sm font-bold text-slate-800'>Грязевые ванны</p>
                                <p class='text-[10px] text-slate-400'>Пн, Ср, Пт, 14:00</p>
                            </div>
                            <span class='text-[10px] font-bold text-purple-500 uppercase'>3 / 5</span>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                    <h3 class='font-bold text-slate-800 mb-6'>Дневник здоровья</h3>
                    <div class='relative pl-8 border-l-2 border-slate-100 space-y-8'>
                        <div class='relative'>
                            <div class='absolute -left-[41px] top-0 w-4 h-4 rounded-full bg-blue-500 border-4 border-white'></div>
                            <p class='text-[10px] font-bold text-slate-400 uppercase'>Сегодня, 08:30</p>
                            <p class='text-sm font-bold text-slate-700 mt-1'>Осмотр терапевта. Состояние стабильное. Жалоб нет.</p>
                        </div>
                        <div class='relative opacity-50'>
                            <div class='absolute -left-[41px] top-0 w-4 h-4 rounded-full bg-slate-300 border-4 border-white'></div>
                            <p class='text-[10px] font-bold text-slate-400 uppercase'>Вчера, 16:20</p>
                            <p class='text-sm font-bold text-slate-700 mt-1'>Процедура электрофореза пройдена.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
