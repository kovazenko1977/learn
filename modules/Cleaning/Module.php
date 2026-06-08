<?php

declare(strict_types=1);

namespace App\Modules\Cleaning;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/cleaning', [$this, 'index']);
        $router->addRoute('POST', '/cleaning/complete', [$this, 'markClean']);
    }

    public function index($request, $response): string
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $rooms = $storage->find('rooms');

        $content = "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter uppercase'>Диспетчерская службы Housekeeping</h2>
            <p class='text-slate-500 mt-2 font-medium'>Контроль санитарного состояния номерного фонда и координация персонала.</p>
        </div>

        <div class='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12'>
            <div class='bg-rose-50 p-8 rounded-[2.5rem] border border-rose-100 shadow-sm relative overflow-hidden'>
                <p class='text-[10px] font-black text-rose-400 uppercase tracking-[0.2em] mb-3 relative z-10'>Ожидают уборки</p>
                <p class='text-4xl font-black text-rose-600 tabular-nums relative z-10'>" . count($storage->find('rooms', ['needs_cleaning' => true])) . "</p>
                <i class='fas fa-broom absolute -right-4 -bottom-4 text-7xl opacity-5'></i>
            </div>
            <div class='bg-blue-50 p-8 rounded-[2.5rem] border border-blue-100 shadow-sm'>
                <p class='text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] mb-3'>В работе у горничных</p>
                <p class='text-4xl font-black text-blue-600 tabular-nums'>2</p>
            </div>
            <div class='bg-emerald-50 p-8 rounded-[2.5rem] border border-emerald-100 shadow-sm'>
                <p class='text-[10px] font-black text-emerald-400 uppercase tracking-[0.2em] mb-3'>Проверено мастером</p>
                <p class='text-4xl font-black text-emerald-600 tabular-nums'>" . count($storage->find('rooms', ['needs_cleaning' => false])) . "</p>
            </div>
            <div class='bg-amber-50 p-8 rounded-[2.5rem] border border-amber-100 shadow-sm'>
                <p class='text-[10px] font-black text-amber-400 uppercase tracking-[0.2em] mb-3'>Технические работы</p>
                <p class='text-4xl font-black text-amber-600 tabular-nums'>" . count($storage->find('rooms', ['status' => 'ремонт'])) . "</p>
            </div>
        </div>

        <div class='bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-8 bg-slate-50 border-b border-slate-100 flex justify-between items-center'>
                 <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Сводный план регламентных работ</h3>
                 <div class='flex gap-2'>
                    <button class='px-6 py-2 bg-white border border-slate-200 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all'>Экспорт графика</button>
                    <button class='px-6 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg'>Сформировать наряды</button>
                 </div>
            </div>
            <div class='overflow-x-auto'>
            <table class='w-full text-left'>
                <thead>
                    <tr class='text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white border-b border-slate-50'>
                        <th class='px-10 py-6'>Жилой блок / Категория</th>
                        <th class='px-10 py-6'>Вид обслуживания</th>
                        <th class='px-10 py-6'>Санитарный статус</th>
                        <th class='px-10 py-6 text-right'>Управление</th>
                    </tr>
                </thead>
                <tbody class='divide-y divide-slate-50'>";

        foreach ($rooms as $r) {
            $needsCleaning = $r['needs_cleaning'] ?? false;
            $statusText = $needsCleaning ? 'Требуется обработка' : 'Помещение готово';
            $statusColor = $needsCleaning ? 'rose' : 'emerald';

            $content .= "
                    <tr class='hover:bg-slate-50 transition-all duration-300'>
                        <td class='px-10 py-8'>
                            <p class='font-black text-slate-800 text-base'>№ {$r['number']}</p>
                            <p class='text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1'>Корпус " . ($r['building'] ?? '1') . " • {$r['type']}</p>
                        </td>
                        <td class='px-10 py-8'>
                            <p class='text-sm font-bold text-slate-700'>" . ($needsCleaning ? "Генеральная уборка (выезд)" : "Текущая влажная уборка") . "</p>
                            <p class='text-[10px] text-slate-400 font-medium uppercase mt-1'>Норматив: " . ($needsCleaning ? "45" : "20") . " мин.</p>
                        </td>
                        <td class='px-10 py-8'>
                            <span class='px-4 py-2 bg-{$statusColor}-50 text-{$statusColor}-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-{$statusColor}-100 shadow-sm'>
                                <i class='fas " . ($needsCleaning ? 'fa-triangle-exclamation' : 'fa-check-double') . " mr-2'></i>
                                $statusText
                            </span>
                        </td>
                        <td class='px-10 py-8 text-right'>";

            if ($needsCleaning) {
                $content .= "
                            <form action='" . $this->container->get(\App\View\Renderer::class)->url('/cleaning/complete') . "' method='POST' class='inline'>
                                <input type='hidden' name='id' value='{$r['id']}'>
                                <button type='submit' class='px-6 py-3 bg-slate-900 text-white hover:bg-emerald-600 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-slate-900/10 active:scale-95'>Подтвердить чистоту</button>
                            </form>";
            } else {
                $content .= "
                            <div class='flex justify-end space-x-2'>
                                <button class='w-10 h-10 rounded-xl bg-slate-100 text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center border border-transparent hover:border-blue-100'><i class='fas fa-clock-rotate-left'></i></button>
                                <button class='px-6 py-3 bg-slate-50 text-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-100 cursor-not-allowed'>В графике</button>
                            </div>";
            }

            $content .= "</td>
                    </tr>";
        }

        if (empty($rooms)) {
            $content .= "<tr><td colspan='4' class='px-10 py-32 text-center font-black text-slate-300'>Реестр номеров пуст</td></tr>";
        }

        $content .= "
                </tbody>
            </table>
            </div>
        </div>";

        return $content;
    }

    public function markClean($request, $response): void
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $data = $request->getBody();
        if (!empty($data['id'])) {
            $room = $storage->findOne('rooms', ['id' => $data['id']]);
            if ($room) {
                $room['needs_cleaning'] = false;
                $storage->update('rooms', $room['id'], $room);
            }
        }
        $renderer = $this->container->get(\App\View\Renderer::class);
        $response->redirect($renderer->url('/cleaning'));
    }
}
