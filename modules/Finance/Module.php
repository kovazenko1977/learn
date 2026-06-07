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
                // Simple calculation: days * price
                $from = new \DateTime($b['date_from']);
                $to = new \DateTime($b['date_to']);
                $days = $to->diff($from)->days ?: 1;
                $totalRevenue += $days * (int)$room['price'];
            }
        }

        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-bold text-slate-800 tracking-tight'>Финансовый контроль</h2>
            <p class='text-slate-500 mt-2'>Мониторинг выручки, оплат и задолженностей.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-4 gap-6 mb-12'>
            <div class='bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl shadow-slate-900/20'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Общая выручка</p>
                <p class='text-3xl font-bold'>" . number_format($totalRevenue, 0, '.', ' ') . " ₽</p>
                <div class='mt-6 text-[10px] text-emerald-400 font-bold uppercase'>+12.5% к прошлому месяцу</div>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Оплачено</p>
                <p class='text-3xl font-bold text-emerald-500'>" . number_format($totalRevenue * 0.85, 0, '.', ' ') . " ₽</p>
                <p class='text-[10px] text-slate-400 mt-2 font-bold'>85% от плана</p>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Депозиты</p>
                <p class='text-3xl font-bold text-blue-500'>240 000 ₽</p>
                <p class='text-[10px] text-slate-400 mt-2 font-bold'>12 активных</p>
            </div>
            <div class='bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm'>
                <p class='text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2'>Задолженность</p>
                <p class='text-3xl font-bold text-rose-500'>" . number_format($totalRevenue * 0.15, 0, '.', ' ') . " ₽</p>
                <p class='text-[10px] text-slate-400 mt-2 font-bold'>4 счета</p>
            </div>
        </div>

        <div class='bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden'>
            <div class='p-8 border-b border-slate-50 flex justify-between items-center'>
                <h3 class='font-bold text-slate-800 uppercase text-xs tracking-widest'>Последние транзакции</h3>
                <button class='text-blue-500 font-bold text-[10px] uppercase tracking-widest'>Экспорт в Excel</button>
            </div>
            <div class='p-4'>
                <canvas id='financeChart' class='w-full h-48'></canvas>
            </div>
            <script>
                new Chart(document.getElementById('financeChart'), {
                    type: 'line',
                    data: {
                        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
                        datasets: [{
                            label: 'Выручка (млн ₽)',
                            data: [1.2, 1.9, 3.2, 5.1, 2.5, 4.8],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { display: false }, x: { grid: { display: false } } }
                    }
                });
            </script>
        </div>
        ";
    }
}
