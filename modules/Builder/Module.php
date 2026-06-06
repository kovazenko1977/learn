<?php

declare(strict_types=1);

namespace App\Modules\Builder;

use App\Module\BaseModule;
use App\Core\Router;
use App\Builder\EntityBuilder;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/builder', [$this, 'index']);
        $router->addRoute('POST', '/builder/create', [$this, 'create']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $builder = new EntityBuilder($storage);

        $entities = $builder->getEntities();

        $entitiesHtml = "";
        foreach($entities as $entity) {
            $entitiesHtml .= "
            <div class='bg-white p-6 rounded-2xl border border-gray-100 shadow-sm'>
                <h4 class='font-bold text-gray-800 text-lg'>{$entity['name']}</h4>
                <p class='text-xs text-gray-400 mb-4'>Полей: " . count($entity['fields']) . "</p>
                <div class='flex space-x-2'>
                    <button class='text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-lg'>Редактировать</button>
                    <button class='text-xs font-bold text-gray-400 px-3 py-1'>Данные</button>
                </div>
            </div>";
        }

        $content = "
        <div class='mb-8 flex justify-between items-center'>
            <div>
                <h2 class='text-3xl font-bold text-gray-800'>Конструктор сущностей</h2>
                <p class='text-gray-500'>Создавайте новые таблицы и поля без программирования.</p>
            </div>
            <button onclick=\"document.getElementById('addEntityModal').classList.remove('hidden')\" class='bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg'>+ Новая сущность</button>
        </div>

        <div id='addEntityModal' class='fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4'>
            <div class='bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden'>
                <form action='{$renderer->url('/builder/create')}' method='POST' class='p-8'>
                    <h3 class='text-2xl font-bold mb-6'>Создание сущности</h3>
                    <div class='space-y-4'>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Название (лат.)</label>
                            <input type='text' name='name' placeholder='Inventory_Items' required class='w-full border-gray-200 rounded-xl'>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-1'>Описание</label>
                            <textarea name='description' class='w-full border-gray-200 rounded-xl'></textarea>
                        </div>
                    </div>
                    <div class='flex justify-end space-x-3 mt-8'>
                        <button type='button' onclick=\"document.getElementById('addEntityModal').classList.add('hidden')\" class='px-6 py-2 text-gray-400 font-bold'>Отмена</button>
                        <button type='submit' class='px-8 py-2 bg-indigo-600 text-white rounded-xl font-bold'>Создать</button>
                    </div>
                </form>
            </div>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>
            $entitiesHtml
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Конструктор - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function create($request, $response): void
    {
        $data = $request->getBody();
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $builder = new EntityBuilder($storage);

        $builder->createEntity($data['name'], []);

        $response->redirect($this->container->get(\App\View\Renderer::class)->url('/builder'));
    }
}
