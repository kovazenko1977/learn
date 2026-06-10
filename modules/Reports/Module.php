<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/reports', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $payments = $storage->find('payments');

        $labels = [];
        $amounts = [];
        foreach ($payments as $p) {
            $labels[] = date('d.m', strtotime($p['date']));
            $amounts[] = $p['amount'];
        }

        $labels_js = json_encode($labels);
        $amounts_js = json_encode($amounts);

        return "
        <div class='mb-10'>
            <h2 class='text-3xl font-black text-slate-800 tracking-tighter'>Аналитика и Отчетность</h2>
            <p class='text-slate-500 mt-2 font-medium'>Интеллектуальный анализ показателей деятельности санатория.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-8'>
            <div class='lg:col-span-2 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <div class='flex items-center justify-between mb-10'>
                    <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest'>Динамика выручки (за последние 7 дней)</h3>
                    <div class='flex space-x-2'>
                        <span class='px-3 py-1 bg-emerald-100 text-emerald-600 text-[10px] font-black rounded-lg'>+12.5%</span>
                    </div>
                </div>
                <canvas id='revenueChart' height='150'></canvas>
                <script>
                    (function() {
                        const ctx = document.getElementById('revenueChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: {$labels_js},
                                datasets: [{
                                    label: 'Выручка',
                                    data: {$amounts_js},
                                    borderColor: '#2563eb',
                                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 4,
                                    pointRadius: 6,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#2563eb',
                                    pointBorderWidth: 2
                                }]
                            },
                            options: {
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, grid: { display: false } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    })();
                </script>
            </div>

            <div class='bg-slate-900 p-10 rounded-[3rem] text-white flex flex-col shadow-3xl shadow-slate-900/30'>
                <h3 class='font-black text-white/50 uppercase text-[10px] tracking-widest mb-8'>Ключевые показатели</h3>
                <div class='space-y-6'>
                    <div class='p-6 bg-white/5 rounded-2xl border border-white/5'>
                        <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2'>Средний чек</p>
                        <p class='text-2xl font-black'>14 500 ₽</p>
                    </div>
                    <div class='p-6 bg-white/5 rounded-2xl border border-white/5'>
                        <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2'>Загрузка фонда</p>
                        <p class='text-2xl font-black'>78%</p>
                    </div>
                    <div class='p-6 bg-white/5 rounded-2xl border border-white/5'>
                        <p class='text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2'>LTV Гостя</p>
                        <p class='text-2xl font-black'>42 000 ₽</p>
                    </div>
                </div>
                <button class='mt-10 w-full py-4 bg-blue-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-500 transition-all'>Экспорт PDF</button>
            </div>
        </div>

        <div class='mt-8 grid grid-cols-1 md:grid-cols-2 gap-8'>
             <div class='bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm'>
                <h3 class='font-black text-slate-800 uppercase text-xs tracking-widest mb-8'>Шаблоны отчетов</h3>
                <div class='space-y-4'>
                    <button class='w-full p-5 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between hover:bg-blue-50 hover:border-blue-200 transition-all group'>
                        <div class='flex items-center'>
                            <i class='fas fa-file-invoice-dollar text-slate-400 group-hover:text-blue-600 mr-4'></i>
                            <span class='text-sm font-black text-slate-800'>Финансовая ведомость</span>
                        </div>
                        <i class='fas fa-download text-slate-300 group-hover:text-blue-600'></i>
                    </button>
                    <button class='w-full p-5 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between hover:bg-emerald-50 hover:border-emerald-200 transition-all group'>
                        <div class='flex items-center'>
                            <i class='fas fa-user-check text-slate-400 group-hover:text-emerald-600 mr-4'></i>
                            <span class='text-sm font-black text-slate-800'>Загрузка номеров</span>
                        </div>
                        <i class='fas fa-download text-slate-300 group-hover:text-emerald-600'></i>
                    </button>
                </div>
            </div>

            <div class='bg-blue-50 p-10 rounded-[3rem] border border-blue-100 flex items-center justify-between'>
                <div>
                    <h3 class='text-xl font-black text-blue-900'>AI-Прогноз</h3>
                    <p class='text-blue-700/60 text-sm mt-2 max-w-xs'>На основе текущих данных, ожидаемая загрузка в следующем месяце составит 85%.</p>
                </div>
                <div class='w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white text-3xl shadow-xl shadow-blue-600/30 animate-pulse'>
                    <i class='fas fa-wand-magic-sparkles'></i>
                </div>
            </div>
        </div>
        ";
    }
}
