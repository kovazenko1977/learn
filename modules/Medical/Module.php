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
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Лечебный корпус</h2>
            <p class='text-slate-500 mt-2 font-medium'>Управление электронными медицинскими картами и листами назначений.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8 mb-12'>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Пациентов на лечении</p>
                <p class='text-4xl font-black text-slate-900'>" . count($bookings) . "</p>
                <div class='mt-6 h-2 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-500 w-2/3 shadow-[0_0_10px_rgba(59,130,246,0.5)]'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Процедур сегодня</p>
                <p class='text-4xl font-black text-emerald-500'>142</p>
                <div class='mt-6 h-2 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-emerald-500 w-full'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Критические показатели</p>
                <p class='text-4xl font-black text-rose-500'>0</p>
                <div class='mt-6 h-2 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-rose-500 w-0'></div>
                </div>
            </div>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Реестр активных пациентов</h3>
                <div class='relative w-64'>
                    <i class='fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs'></i>
                    <input type='text' placeholder='Поиск по ФИО...' class='w-full bg-white border border-slate-100 rounded-xl pl-10 pr-4 py-2 text-xs font-bold focus:ring-4 focus:ring-blue-500/10 outline-none'>
                </div>
            </div>
            <div class='overflow-x-auto'>
                <table class='w-full text-left'>
                    <thead>
                        <tr class='text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                            <th class='px-8 py-6'>ФИО Пациента</th>
                            <th class='px-8 py-6'>Размещение</th>
                            <th class='px-8 py-6'>Назначения</th>
                            <th class='px-8 py-6'>Статус</th>
                            <th class='px-8 py-6 text-right'>Действия</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-50'>";

        foreach ($bookings as $b) {
            $content .= "
                    <tr class='hover:bg-blue-50/30 transition-colors group'>
                        <td class='px-8 py-8'>
                            <div class='flex items-center space-x-5'>
                                <div class='w-12 h-12 rounded-[1rem] bg-slate-100 flex items-center justify-center font-black text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-inner'>
                                    " . mb_substr($b['guest_name'], 0, 1) . "
                                </div>
                                <div>
                                    <p class='font-black text-slate-800 text-sm'>{$b['guest_name']}</p>
                                    <p class='text-[10px] text-slate-400 font-bold uppercase mt-0.5'>" . ($b['guest_gender'] == 'male' ? 'Мужчина' : 'Женщина') . ", {$b['guest_age']} лет</p>
                                </div>
                            </div>
                        </td>
                        <td class='px-8 py-8'>
                            <span class='px-4 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase border border-slate-200'>№ {$b['room_number']}</span>
                        </td>
                        <td class='px-8 py-8'>
                            <div class='flex -space-x-3'>
                                <div class='w-9 h-9 rounded-full bg-blue-100 border-4 border-white flex items-center justify-center shadow-sm' title='ЛФК'><i class='fas fa-person-walking text-xs text-blue-600'></i></div>
                                <div class='w-9 h-9 rounded-full bg-emerald-100 border-4 border-white flex items-center justify-center shadow-sm' title='Массаж'><i class='fas fa-hands-holding text-xs text-emerald-600'></i></div>
                                <div class='w-9 h-9 rounded-full bg-purple-100 border-4 border-white flex items-center justify-center shadow-sm' title='Ванны'><i class='fas fa-water text-xs text-purple-600'></i></div>
                            </div>
                        </td>
                        <td class='px-8 py-8'>
                            <span class='flex items-center text-[10px] font-black text-emerald-500 uppercase tracking-widest'>
                                <span class='w-2 h-2 rounded-full bg-emerald-500 mr-2 shadow-[0_0_8px_#10b981] animate-pulse'></span>
                                Проходит курс
                            </span>
                        </td>
                        <td class='px-8 py-8 text-right'>
                            <button onclick=\"wm.createWindow('Мед. карта: {$b['guest_name']}', '{$renderer->url('/medical/patient/'.$b['id'])}', 'fa-file-medical', 'text-rose-500')\" class='px-6 py-2.5 bg-slate-900 hover:bg-rose-600 text-white text-[10px] font-black rounded-xl transition-all uppercase tracking-widest shadow-xl shadow-slate-900/10'>Открыть карту</button>
                        </td>
                    </tr>";
        }

        $content .= "
                </tbody>
            </table>
            </div>
        </div>";

        return $renderer->render('layout', [
            'title' => 'Лечебный корпус - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function patientCard($request, $response, $id): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $patient = $storage->findOne('bookings', ['id' => $id]);

        if (!$patient) return "<div class='p-20 text-center font-bold text-slate-400'>Пациент не найден в базе данных.</div>";

        return "
        <div class='flex flex-col space-y-10'>
            <div class='flex flex-col md:flex-row items-center md:items-start gap-10'>
                <div class='w-40 h-40 rounded-[3rem] bg-slate-100 flex items-center justify-center text-5xl text-slate-300 border-8 border-white shadow-2xl shadow-slate-200'>
                    <i class='fas fa-user-injured'></i>
                </div>
                <div class='flex-grow text-center md:text-left'>
                    <h2 class='text-4xl font-black text-slate-800 tracking-tighter'>{$patient['guest_name']}</h2>
                    <p class='text-slate-400 font-black uppercase text-[10px] tracking-[0.3em] mt-2'>Электронная медицинская карта: $id</p>

                    <div class='mt-8 grid grid-cols-1 sm:grid-cols-3 gap-6'>
                        <div class='p-6 bg-blue-50 rounded-[1.5rem] border border-blue-100'>
                            <p class='text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2'>Основной диагноз</p>
                            <p class='text-sm font-black text-blue-900'>Общее оздоровление организма</p>
                        </div>
                        <div class='p-6 bg-emerald-50 rounded-[1.5rem] border border-emerald-100'>
                            <p class='text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2'>Лекарственная аллергия</p>
                            <p class='text-sm font-black text-emerald-900'>Не выявлено</p>
                        </div>
                        <div class='p-6 bg-rose-50 rounded-[1.5rem] border border-rose-100'>
                            <p class='text-[10px] font-black text-rose-400 uppercase tracking-widest mb-2'>Противопоказания</p>
                            <p class='text-sm font-black text-rose-900'>Ограничение физ. нагрузок</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class='grid grid-cols-1 lg:grid-cols-2 gap-10'>
                <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                    <div class='flex justify-between items-center mb-8'>
                        <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Лист назначений</h3>
                        <button class='w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-inner'><i class='fas fa-plus'></i></button>
                    </div>
                    <div class='space-y-4'>
                        <div class='flex items-center p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 hover:border-blue-200 transition-all'>
                            <div class='w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center mr-5 shadow-lg shadow-blue-200'><i class='fas fa-person-swimming'></i></div>
                            <div class='flex-grow'>
                                <p class='text-sm font-black text-slate-800'>Лечебное плавание</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Ежедневно, 10:00 • Бассейн</p>
                            </div>
                            <div class='text-right'>
                                <span class='text-[10px] font-black text-blue-600 uppercase bg-white px-3 py-1 rounded-full border border-blue-100 shadow-sm'>7 / 10 сеансов</span>
                            </div>
                        </div>
                         <div class='flex items-center p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 hover:border-purple-200 transition-all'>
                            <div class='w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center mr-5 shadow-lg shadow-purple-200'><i class='fas fa-spa'></i></div>
                            <div class='flex-grow'>
                                <p class='text-sm font-black text-slate-800'>Грязевые аппликации</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase'>Пн, Ср, Пт, 14:00 • Каб. 204</p>
                            </div>
                            <div class='text-right'>
                                <span class='text-[10px] font-black text-purple-600 uppercase bg-white px-3 py-1 rounded-full border border-purple-100 shadow-sm'>3 / 5 сеансов</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest mb-8'>Дневник наблюдения</h3>
                    <div class='relative pl-10 border-l-4 border-slate-50 space-y-10'>
                        <div class='relative'>
                            <div class='absolute -left-[50px] top-0 w-6 h-6 rounded-full bg-blue-600 border-4 border-white shadow-lg shadow-blue-200'></div>
                            <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest'>Сегодня, 08:30 • Врач: Соколов А.В.</p>
                            <p class='text-sm font-bold text-slate-700 mt-2 leading-relaxed'>Плановый осмотр. Жалоб нет. Артериальное давление 120/80. Рекомендовано продолжить текущий курс процедур.</p>
                        </div>
                        <div class='relative opacity-60'>
                            <div class='absolute -left-[50px] top-0 w-6 h-6 rounded-full bg-slate-300 border-4 border-white'></div>
                            <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest'>Вчера, 16:20 • Система</p>
                            <p class='text-sm font-bold text-slate-700 mt-2'>Процедура электрофореза успешно пройдена гостем. Побочных эффектов не зафиксировано.</p>
                        </div>
                    </div>
                    <div class='mt-10'>
                        <button class='w-full py-4 border-2 border-dashed border-slate-200 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] hover:bg-slate-50 hover:text-slate-600 transition-all'>Добавить запись в дневник</button>
                    </div>
                </div>
            </div>
        </div>
        ";
    }
}
