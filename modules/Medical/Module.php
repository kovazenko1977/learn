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
        $router->addRoute('POST', '/medical/appointment/add', [$this, 'addAppointment']);
        $router->addRoute('POST', '/medical/procedure/complete', [$this, 'completeProcedure']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $bookings = $storage->find('bookings', ['status' => 'confirmed']);

        $content = "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter uppercase'>Лечебный корпус и Санаторный учет</h2>
            <p class='text-slate-500 mt-2 font-medium'>Централизованное управление лечебным процессом, процедурными кабинетами и мед. картами.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8 mb-12'>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Пациентов на курсе</p>
                <p class='text-4xl font-black text-slate-900 tabular-nums'>" . count($bookings) . "</p>
                <div class='mt-6 h-2.5 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-blue-600 w-2/3 shadow-[0_0_15px_rgba(37,99,235,0.4)]'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Процедур в плане (Сегодня)</p>
                <p class='text-4xl font-black text-emerald-600 tabular-nums'>142</p>
                <div class='mt-6 h-2.5 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-emerald-500 w-full'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Экстренные вызовы</p>
                <p class='text-4xl font-black text-slate-200 tabular-nums'>0</p>
                <p class='text-[10px] text-emerald-500 mt-6 font-black uppercase tracking-widest'>Ситуация стабильна</p>
            </div>
        </div>

        <div class='bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-10 border-b border-slate-50 flex justify-between items-center bg-slate-50/50'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Реестр активных санаторных карт</h3>
                <div class='relative w-80'>
                    <i class='fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300'></i>
                    <input type='text' placeholder='Поиск по ФИО пациента...' class='w-full bg-white border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-xs font-black focus:ring-4 focus:ring-blue-500/10 outline-none shadow-inner'>
                </div>
            </div>
            <div class='overflow-x-auto'>
                <table class='w-full text-left'>
                    <thead>
                        <tr class='text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                            <th class='px-10 py-8'>ФИО Пациента / Мед. данные</th>
                            <th class='px-10 py-8'>Размещение</th>
                            <th class='px-10 py-8 text-center'>Назначения</th>
                            <th class='px-10 py-8'>Статус курса</th>
                            <th class='px-10 py-8 text-right'>Управление</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-50'>";

        foreach ($bookings as $b) {
            $content .= "
                    <tr class='hover:bg-blue-50/40 transition-all group'>
                        <td class='px-10 py-8'>
                            <div class='flex items-center space-x-6'>
                                <div class='w-14 h-14 rounded-2xl bg-white border border-slate-100 flex items-center justify-center font-black text-slate-300 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition-all shadow-sm'>
                                    " . mb_substr($b['guest_name'], 0, 1) . "
                                </div>
                                <div>
                                    <p class='font-black text-slate-800 text-base tracking-tight'>{$b['guest_name']}</p>
                                    <p class='text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-wider'>" . ($b['guest_gender'] == 'male' ? 'Мужчина' : 'Женщина') . " • {$b['guest_age']} лет</p>
                                </div>
                            </div>
                        </td>
                        <td class='px-10 py-8'>
                            <div class='inline-flex flex-col'>
                                <span class='px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-[10px] font-black uppercase border border-slate-200 shadow-inner'>№ {$b['room_number']}</span>
                            </div>
                        </td>
                        <td class='px-10 py-8'>
                            <div class='flex justify-center -space-x-3'>
                                <div class='w-10 h-10 rounded-full bg-blue-100 border-4 border-white flex items-center justify-center shadow-lg' title='Бальнеотерапия'><i class='fas fa-water text-xs text-blue-600'></i></div>
                                <div class='w-10 h-10 rounded-full bg-emerald-100 border-4 border-white flex items-center justify-center shadow-lg' title='Массаж'><i class='fas fa-hands-holding text-xs text-emerald-600'></i></div>
                                <div class='w-10 h-10 rounded-full bg-purple-100 border-4 border-white flex items-center justify-center shadow-lg' title='Физиотерапия'><i class='fas fa-bolt-lightning text-xs text-purple-600'></i></div>
                                <div class='w-10 h-10 rounded-full bg-slate-50 border-4 border-white flex items-center justify-center shadow-lg text-[10px] font-black text-slate-400'>+2</div>
                            </div>
                        </td>
                        <td class='px-10 py-8'>
                            <span class='flex items-center text-[10px] font-black text-emerald-500 uppercase tracking-widest bg-emerald-50 px-4 py-2 rounded-full border border-emerald-100'>
                                <span class='w-2 h-2 rounded-full bg-emerald-500 mr-3 shadow-[0_0_10px_#10b981] animate-pulse'></span>
                                На лечении
                            </span>
                        </td>
                        <td class='px-10 py-8 text-right'>
                            <button onclick=\"wm.createWindow('Мед. карта: {$b['guest_name']}', '{$renderer->url('/medical/patient/'.$b['id'])}', 'fa-file-medical', 'text-rose-500')\" class='px-8 py-3 bg-slate-900 hover:bg-rose-600 text-white text-[10px] font-black rounded-2xl transition-all uppercase tracking-widest shadow-xl shadow-slate-900/20 active:scale-95'>Открыть карту</button>
                        </td>
                    </tr>";
        }

        if (empty($bookings)) {
            $content .= "<tr><td colspan='5' class='px-10 py-32 text-center'><div class='text-slate-200 text-6xl mb-6'><i class='fas fa-user-slash'></i></div><p class='text-slate-400 font-black text-xl'>Пациенты на лечении отсутствуют</p></td></tr>";
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
        <div class='flex flex-col space-y-12'>
            <!-- Шапка карты -->
            <div class='flex flex-col lg:flex-row items-center lg:items-start gap-12 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <div class='w-48 h-48 rounded-[3.5rem] bg-slate-50 flex items-center justify-center text-6xl text-slate-200 border-[10px] border-white shadow-2xl relative overflow-hidden'>
                    <i class='fas fa-user-injured relative z-10'></i>
                    <div class='absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent'></div>
                </div>
                <div class='flex-grow text-center lg:text-left'>
                    <div class='flex flex-col lg:flex-row lg:items-center justify-between gap-6'>
                        <div>
                            <h2 class='text-4xl font-black text-slate-800 tracking-tighter'>{$patient['guest_name']}</h2>
                            <p class='text-slate-400 font-black uppercase text-[11px] tracking-[0.4em] mt-3 flex items-center justify-center lg:justify-start'>
                                <i class='fas fa-fingerprint mr-2 text-blue-500'></i> Карта №$id • Размещение: №{$patient['room_number']}
                            </p>
                        </div>
                        <div class='flex gap-3 justify-center'>
                            <button class='px-8 py-3 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-slate-900/20'>Печать карты</button>
                            <button class='px-8 py-3 bg-white border border-slate-200 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all'>Архивировать</button>
                        </div>
                    </div>

                    <div class='mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6'>
                        <div class='p-6 bg-blue-50 rounded-[2rem] border border-blue-100 shadow-inner'>
                            <p class='text-[9px] font-black text-blue-400 uppercase tracking-widest mb-2'>Диагноз при поступлении</p>
                            <p class='text-xs font-black text-blue-900'>Остеохондроз поясничного отдела, стадия ремиссии.</p>
                        </div>
                        <div class='p-6 bg-emerald-50 rounded-[2rem] border border-emerald-100 shadow-inner'>
                            <p class='text-[9px] font-black text-emerald-400 uppercase tracking-widest mb-2'>Аллергоанамнез</p>
                            <p class='text-xs font-black text-emerald-900'>Аллергия на пенициллин, цитрусовые.</p>
                        </div>
                        <div class='p-6 bg-rose-50 rounded-[2rem] border border-rose-100 shadow-inner'>
                            <p class='text-[9px] font-black text-rose-400 uppercase tracking-widest mb-2'>Противопоказания</p>
                            <p class='text-xs font-black text-rose-900'>Высокие кардионагрузки, сауна запрещена.</p>
                        </div>
                        <div class='p-6 bg-amber-50 rounded-[2rem] border border-amber-100 shadow-inner'>
                            <p class='text-[9px] font-black text-amber-500 uppercase tracking-widest mb-2'>Диагноз при выписке</p>
                            <p class='text-xs font-black text-slate-400 italic'>Будет заполнено лечащим врачом</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основной контент -->
            <div class='grid grid-cols-1 xl:grid-cols-2 gap-12'>
                <!-- Назначения -->
                <div class='bg-white p-12 rounded-[3.5rem] border border-slate-100 shadow-sm'>
                    <div class='flex justify-between items-center mb-10'>
                        <div>
                            <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Лист врачебных назначений</h3>
                            <p class='text-[10px] text-slate-400 font-bold mt-1'>Курс лечения на 14 дней</p>
                        </div>
                        <button onclick=\"document.getElementById('addAppointmentModal').classList.remove('hidden')\" class='w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition-all shadow-xl shadow-blue-600/20'><i class='fas fa-plus'></i></button>
                    </div>
                    <div class='space-y-5'>
                        <div class='flex items-center p-6 bg-slate-50 rounded-[2rem] border border-slate-100 group hover:border-blue-200 transition-all'>
                            <div class='w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center mr-6 shadow-xl shadow-blue-200 group-hover:scale-110 transition-transform'><i class='fas fa-person-swimming text-lg'></i></div>
                            <div class='flex-grow'>
                                <p class='text-base font-black text-slate-800'>Лечебное плавание (30 мин)</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1'>Ежедневно, 09:00 • Аква-зона</p>
                            </div>
                            <div class='text-right'>
                                <p class='text-[10px] font-black text-blue-600 uppercase bg-white px-4 py-2 rounded-full border border-blue-100 shadow-sm inline-block'>Выполнено 8 / 12</p>
                            </div>
                        </div>
                        <div class='flex items-center p-6 bg-slate-50 rounded-[2rem] border border-slate-100 group hover:border-purple-200 transition-all'>
                            <div class='w-14 h-14 rounded-2xl bg-purple-600 text-white flex items-center justify-center mr-6 shadow-xl shadow-purple-200 group-hover:scale-110 transition-transform'><i class='fas fa-spa text-lg'></i></div>
                            <div class='flex-grow'>
                                <p class='text-base font-black text-slate-800'>Подводный душ-массаж</p>
                                <p class='text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1'>Вт, Чт, Сб • 11:30 • Каб. 102</p>
                            </div>
                            <div class='text-right'>
                                <p class='text-[10px] font-black text-purple-600 uppercase bg-white px-4 py-2 rounded-full border border-purple-100 shadow-sm inline-block'>Выполнено 3 / 6</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Дневник -->
                <div class='bg-white p-12 rounded-[3.5rem] border border-slate-100 shadow-sm'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest mb-10'>Дневник динамического наблюдения</h3>
                    <div class='relative pl-12 border-l-4 border-slate-50 space-y-12'>
                        <div class='relative'>
                            <div class='absolute -left-[62px] top-0 w-8 h-8 rounded-full bg-blue-600 border-4 border-white shadow-xl shadow-blue-200 flex items-center justify-center text-[10px] text-white font-black'>1</div>
                            <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'>14 Янв 2024, 08:30 • Терапевт: Соколов А.В.</p>
                            <p class='text-sm font-bold text-slate-700 mt-3 leading-relaxed'>Пациент отмечает значительное уменьшение болей в пояснице после курса грязевых аппликаций. Сон нормализовался. АД 115/75, пульс 68 уд/мин. Продолжаем физиотерапию.</p>
                        </div>
                        <div class='relative opacity-60'>
                            <div class='absolute -left-[62px] top-0 w-8 h-8 rounded-full bg-slate-300 border-4 border-white shadow-lg flex items-center justify-center text-[10px] text-white font-black'>2</div>
                            <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'>12 Янв 2024, 16:20 • Система (Процедурный кабинет)</p>
                            <p class='text-sm font-bold text-slate-700 mt-3'>Сеанс амплипульстерапии пройден без особенностей. Жалоб во время процедуры нет.</p>
                        </div>
                    </div>
                    <div class='mt-12'>
                        <button class='w-full py-5 border-4 border-dashed border-slate-100 rounded-3xl text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] hover:bg-slate-50 hover:text-slate-600 hover:border-slate-200 transition-all'>Добавить запись в протокол</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модалка добавления назначения -->
        <div id='addAppointmentModal' class='fixed inset-0 bg-slate-900/80 backdrop-blur-xl z-[8000] hidden flex items-center justify-center p-6'>
            <div class='bg-white rounded-[3rem] shadow-2xl w-full max-w-xl overflow-hidden'>
                <div class='bg-slate-900 p-10 text-white flex justify-between items-center'>
                    <div>
                        <h4 class='text-2xl font-black tracking-tight'>Новое назначение</h4>
                        <p class='text-slate-400 text-xs font-bold uppercase tracking-widest mt-1'>Внесение в протокол лечения</p>
                    </div>
                    <button onclick=\"document.getElementById('addAppointmentModal').classList.add('hidden')\" class='w-12 h-12 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors'><i class='fas fa-times'></i></button>
                </div>
                <form action='{$renderer->url('/medical/appointment/add')}' method='POST' class='p-10 space-y-8'>
                    <input type='hidden' name='patient_id' value='$id'>
                    <div>
                        <label class='block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4'>Наименование процедуры</label>
                        <select name='procedure' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-black outline-none focus:ring-4 focus:ring-blue-500/10'>
                            <option>Ароматерапия</option>
                            <option>Ванна хвойная</option>
                            <option>Ингаляция</option>
                            <option>Массаж воротниковой зоны</option>
                            <option>Электрофорез</option>
                        </select>
                    </div>
                    <div class='grid grid-cols-2 gap-6'>
                        <div>
                            <label class='block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4'>Кол-во сеансов</label>
                            <input type='number' name='count' value='10' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-black outline-none'>
                        </div>
                        <div>
                            <label class='block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4'>Периодичность</label>
                            <select name='freq' class='w-full border-slate-100 bg-slate-50 rounded-2xl p-5 text-sm font-black outline-none'>
                                <option>Ежедневно</option>
                                <option>Через день</option>
                                <option>По графику</option>
                            </select>
                        </div>
                    </div>
                    <div class='pt-6'>
                        <button type='submit' class='w-full py-5 bg-blue-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-blue-600/30 hover:bg-blue-500 active:scale-95 transition-all'>Утвердить назначение</button>
                    </div>
                </form>
            </div>
        </div>
        ";
    }

    public function addAppointment($request, $response): void
    {
        // В реальной системе здесь запись в JSON базу
        $renderer = $this->container->get(\App\View\Renderer::class);
        $data = $request->getBody();
        $response->redirect($renderer->url('/medical/patient/' . ($data['patient_id'] ?? '')));
    }
}
