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
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Отчетность и Аналитика</h2>
            <p class='text-gray-500 mt-1'>Генерация документов, графики эффективности и экспорт данных.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
            <div class='bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group'>
                <div class='w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all'>
                    <i class='fas fa-file-invoice-dollar text-2xl'></i>
                </div>
                <h3 class='text-xl font-bold text-gray-800 mb-2'>Финансовый отчет</h3>
                <p class='text-sm text-gray-500 mb-6'>Доходы, расходы, средний чек и дебиторская задолженность за выбранный период.</p>
                <button class='text-blue-600 font-bold text-sm flex items-center'>
                    Сформировать <i class='fas fa-chevron-right ml-2'></i>
                </button>
            </div>

            <div class='bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group'>
                <div class='w-14 h-14 bg-green-100 text-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-green-600 group-hover:text-white transition-all'>
                    <i class='fas fa-bed text-2xl'></i>
                </div>
                <h3 class='text-xl font-bold text-gray-800 mb-2'>Загрузка фонда</h3>
                <p class='text-sm text-gray-500 mb-6'>Анализ занятости номеров, прогноз на месяц и статистика по категориям размещения.</p>
                <button class='text-green-600 font-bold text-sm flex items-center'>
                    Сформировать <i class='fas fa-chevron-right ml-2'></i>
                </button>
            </div>

            <div class='bg-white p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group'>
                <div class='w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition-all'>
                    <i class='fas fa-notes-medical text-2xl'></i>
                </div>
                <h3 class='text-xl font-bold text-gray-800 mb-2'>Медицинская активность</h3>
                <p class='text-sm text-gray-500 mb-6'>Популярность процедур, загрузка кабинетов и статистика по заболеваниям.</p>
                <button class='text-purple-600 font-bold text-sm flex items-center'>
                    Сформировать <i class='fas fa-chevron-right ml-2'></i>
                </button>
            </div>
        </div>

        <div class='mt-12 bg-white p-8 rounded-2xl border border-gray-100'>
            <h3 class='text-lg font-bold text-gray-800 mb-6'>Настройка параметров экспорта</h3>
            <div class='grid grid-cols-1 md:grid-cols-4 gap-4'>
                <select class='border-gray-200 rounded-xl'><option>За неделю</option><option>За месяц</option></select>
                <select class='border-gray-200 rounded-xl'><option>PDF Document</option><option>Excel (XLSX)</option><option>CSV</option></select>
                <button class='md:col-span-2 bg-gray-800 text-white font-bold py-3 rounded-xl hover:bg-black transition-all'>
                    Скачать пакетный архив отчетов
                </button>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Отчеты - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
