<?php

declare(strict_types=1);

namespace App\Modules\Loyalty;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/loyalty', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Программа лояльности</h2>
            <p class='text-gray-500 mt-1'>Управление баллами, уровнями гостей и сертификатами.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-3 gap-8'>
             <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <h4 class='text-gray-400 text-xs font-bold uppercase mb-4'>Активные уровни</h4>
                <div class='space-y-4'>
                    <div class='flex items-center justify-between p-3 bg-blue-50 rounded-xl'>
                        <span class='font-bold text-blue-700'>Silver</span>
                        <span class='text-xs font-bold px-2 py-1 bg-white rounded-lg'>5% скидка</span>
                    </div>
                    <div class='flex items-center justify-between p-3 bg-yellow-50 rounded-xl'>
                        <span class='font-bold text-yellow-700'>Gold</span>
                        <span class='text-xs font-bold px-2 py-1 bg-white rounded-lg'>10% скидка</span>
                    </div>
                    <div class='flex items-center justify-between p-3 bg-purple-50 rounded-xl'>
                        <span class='font-bold text-purple-700'>Platinum</span>
                        <span class='text-xs font-bold px-2 py-1 bg-white rounded-lg'>15% скидка</span>
                    </div>
                </div>
             </div>

             <div class='md:col-span-2 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-8 text-white shadow-xl shadow-blue-100 relative overflow-hidden'>
                <div class='relative z-10'>
                    <h3 class='text-2xl font-bold mb-4'>Статистика бонусов</h3>
                    <p class='text-blue-100 mb-8 max-w-md'>Всего начислено баллов за месяц: <span class='font-bold text-white'>1,240,500</span>. Гости активно используют баллы для оплаты дополнительных услуг (SPA, Экскурсии).</p>
                    <button class='px-6 py-2 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-all'>
                        Настроить правила начисления
                    </button>
                </div>
                <i class='fas fa-gem absolute -bottom-10 -right-10 text-[160px] opacity-10 rotate-12'></i>
             </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Лояльность - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
