<?php

declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/inventory', [$this, 'index']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8 flex justify-between items-center'>
            <div>
                <h2 class='text-3xl font-bold text-gray-800'>Склад и ТМЦ</h2>
                <p class='text-gray-500 mt-1'>Учет оборудования, медикаментов и продуктов питания.</p>
            </div>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8'>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Товаров в наличии</p>
                <p class='text-3xl font-bold'>842</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Критический остаток</p>
                <p class='text-3xl font-bold text-red-500'>12</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Ожидает приемки</p>
                <p class='text-3xl font-bold text-blue-500'>3</p>
            </div>
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <p class='text-xs font-bold text-gray-400 uppercase mb-2'>Стоимость склада</p>
                <p class='text-3xl font-bold tracking-tight'>4.2М ₽</p>
            </div>
        </div>

        <div class='bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden'>
            <table class='w-full text-left text-sm'>
                <thead class='bg-gray-50 border-b'>
                    <tr class='text-gray-400 text-xs uppercase tracking-wider'>
                        <th class='px-6 py-4'>Наименование</th>
                        <th class='px-6 py-4'>Категория</th>
                        <th class='px-6 py-4'>Остаток</th>
                        <th class='px-6 py-4 text-right'>Статус</th>
                    </tr>
                </thead>
                <tbody class='divide-y'>
                    <tr>
                        <td class='px-6 py-4 font-bold'>Озокерит медицинский</td>
                        <td class='px-6 py-4'>Медикаменты</td>
                        <td class='px-6 py-4'>150 кг</td>
                        <td class='px-6 py-4 text-right'><span class='px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold'>НОРМА</span></td>
                    </tr>
                    <tr>
                        <td class='px-6 py-4 font-bold'>Маски одноразовые</td>
                        <td class='px-6 py-4'>Расходники</td>
                        <td class='px-6 py-4'>20 шт</td>
                        <td class='px-6 py-4 text-right'><span class='px-2 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase'>Критично</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Склад - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
