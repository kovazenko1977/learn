<?php
declare(strict_types=1);
namespace App\Modules\Legal_Dept;
use App\Module\BaseModule;
use App\Core\Router;
class Module extends BaseModule {
    public function boot(): void {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/' . strtolower('Legal_Dept'), [$this, 'index']);
    }
    public function index($request, $response): string {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $content = "<div class='mb-8'>
            <h2 class='text-4xl font-extrabold text-gray-900'>Legal_Dept</h2>
            <p class='text-gray-500 mt-2'>Юридический отдел. Модуль готов к работе.</p>
        </div>
        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
            <div class='bg-white p-8 rounded-[40px] border border-gray-100 shadow-xl shadow-blue-50/20 text-center'>
                <div class='w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-6 text-3xl shadow-lg shadow-blue-100'>
                    <i class='fas fa-chart-line'></i>
                </div>
                <h3 class='font-bold text-xl mb-2 text-gray-800'>Статистика</h3>
                <p class='text-sm text-gray-400'>Оперативные данные модуля в реальном времени.</p>
            </div>
            <div class='bg-white p-8 rounded-[40px] border border-gray-100 shadow-xl shadow-purple-50/20 text-center'>
                <div class='w-20 h-20 bg-purple-50 text-purple-600 rounded-3xl flex items-center justify-center mx-auto mb-6 text-3xl shadow-lg shadow-purple-100'>
                    <i class='fas fa-tasks'></i>
                </div>
                <h3 class='font-bold text-xl mb-2 text-gray-800'>Задачи</h3>
                <p class='text-sm text-gray-400'>Управление текущими процессами и планами.</p>
            </div>
            <div class='bg-white p-8 rounded-[40px] border border-gray-100 shadow-xl shadow-orange-50/20 text-center'>
                <div class='w-20 h-20 bg-orange-50 text-orange-600 rounded-3xl flex items-center justify-center mx-auto mb-6 text-3xl shadow-lg shadow-orange-100'>
                    <i class='fas fa-file-alt'></i>
                </div>
                <h3 class='font-bold text-xl mb-2 text-gray-800'>Отчеты</h3>
                <p class='text-sm text-gray-400'>Генерация аналитики и выгрузка документов.</p>
            </div>
        </div>";
        return $renderer->render('layout', [
            'title' => 'Legal_Dept - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}