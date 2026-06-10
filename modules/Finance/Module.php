<?php

declare(strict_types=1);

namespace App\Modules\Finance;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/finance', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);

        $bookings = $storage->find('bookings');
        $totalRevenue = 0;
        foreach($bookings as $b) {
            $room = $storage->findOne('rooms', ['number' => $b['room_number']]);
            if ($room) {
                $from = new \DateTime($b['date_from']);
                $to = new \DateTime($b['date_to']);
                $days = $to->diff($from)->days ?: 1;
                $totalRevenue += $days * (int)$room['price'];
            }
        }

        return "
        <div class='mb-12'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Финансовый отдел и Касса</h2>
            <p class='text-slate-500 mt-3 font-medium'>Анализ выручки, контроль дебиторской задолженности и финансовая аналитика.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12'>
            <div class='bg-slate-900 p-10 rounded-[2.5rem] text-white shadow-2xl shadow-slate-900/30 relative overflow-hidden'>
                <div class='relative z-10'>
                    <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Валовая выручка</p>
                    <p class='text-3xl font-black tabular-nums tracking-tighter'>" . number_format($totalRevenue, 0, '.', ' ') . " ₽</p>
                    <div class='mt-6 inline-flex items-center px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg text-[10px] font-black uppercase tracking-widest'>
                        <i class='fas fa-caret-up mr-2'></i> +12.5%
                    </div>
                </div>
                <i class='fas fa-coins absolute -right-6 -bottom-6 text-[8rem] opacity-5'></i>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Поступило оплат</p>
                <p class='text-3xl font-black text-emerald-600 tabular-nums tracking-tighter'>" . number_format($totalRevenue * 0.88, 0, '.', ' ') . " ₽</p>
                <div class='mt-6 h-1.5 w-full bg-slate-50 rounded-full overflow-hidden'>
                    <div class='h-full bg-emerald-500 w-[88%] shadow-[0_0_8px_#10b981]'></div>
                </div>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Дебиторская задолж.</p>
                <p class='text-3xl font-black text-rose-500 tabular-nums tracking-tighter'>" . number_format($totalRevenue * 0.12, 0, '.', ' ') . " ₽</p>
                <p class='text-[10px] text-slate-400 mt-4 font-bold uppercase tracking-widest'>8 активных счетов</p>
            </div>
            <div class='bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3'>Средний чек</p>
                <p class='text-3xl font-black text-blue-600 tabular-nums tracking-tighter'>42 500 ₽</p>
                <p class='text-[10px] text-slate-400 mt-4 font-bold uppercase tracking-widest'>На 1 путевку</p>
            </div>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-8'>
            <div class='lg:col-span-2 bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden'>
                <div class='p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/20'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Динамика доходов (млн ₽)</h3>
                    <div class='flex space-x-2'>
                        <button class='px-4 py-2 bg-white border border-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm hover:bg-slate-50 transition-all'>Экспорт PDF</button>
                    </div>
                </div>
                <div class='p-8'>
                    <canvas id='financeChart' class='w-full h-64'></canvas>
                </div>
            </div>

            <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest mb-8'>Структура оплат</h3>
                <div class='space-y-8'>
                    <div>
                        <div class='flex justify-between mb-3'>
                            <span class='text-xs font-bold text-slate-600'>Безналичный расчет</span>
                            <span class='text-xs font-black text-slate-900'>74%</span>
                        </div>
                        <div class='h-3 w-full bg-slate-50 rounded-full overflow-hidden p-0.5 border border-slate-100'>
                            <div class='h-full bg-blue-500 rounded-full shadow-lg shadow-blue-200' style='width: 74%'></div>
                        </div>
                    </div>
                    <div>
                        <div class='flex justify-between mb-3'>
                            <span class='text-xs font-bold text-slate-600'>Наличные (Касса)</span>
                            <span class='text-xs font-black text-slate-900'>18%</span>
                        </div>
                        <div class='h-3 w-full bg-slate-50 rounded-full overflow-hidden p-0.5 border border-slate-100'>
                            <div class='h-full bg-emerald-500 rounded-full shadow-lg shadow-emerald-200' style='width: 18%'></div>
                        </div>
                    </div>
                    <div>
                        <div class='flex justify-between mb-3'>
                            <span class='text-xs font-bold text-slate-600'>Оплата на сайте</span>
                            <span class='text-xs font-black text-slate-900'>8%</span>
                        </div>
                        <div class='h-3 w-full bg-slate-50 rounded-full overflow-hidden p-0.5 border border-slate-100'>
                            <div class='h-full bg-purple-500 rounded-full shadow-lg shadow-purple-200' style='width: 8%'></div>
                        </div>
                    </div>
                </div>

                <div class='mt-12 pt-8 border-t border-slate-50 text-center'>
                    <button class='w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-blue-600 transition-all shadow-2xl shadow-slate-900/10 active:scale-95'>Сформировать Z-отчет</button>
                </div>
            </div>
        </div>

        <script>
            if (window.Chart) {
                new Chart(document.getElementById('financeChart'), {
                    type: 'line',
                    data: {
                        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
                        datasets: [{
                            label: 'Выручка',
                            data: [2.1, 2.8, 3.2, 4.8, 4.2, 5.5],
                            borderColor: '#3b82f6',
                            borderWidth: 4,
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#3b82f6',
                            pointBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { display: true, grid: { color: '#f8fafc' }, ticks: { font: { weight: 'bold', size: 10 } } },
                            x: { grid: { display: false }, ticks: { font: { weight: 'bold', size: 10 } } }
                        }
                    }
                });
            }
        </script>
        ";
    }
}
