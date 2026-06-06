<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/settings', [$this, 'index']);
        $router->addRoute('GET', '/settings/audit', [$this, 'audit']);
        $router->addRoute('GET', '/settings/backup', [$this, 'backup']);
        $router->addRoute('POST', '/api/settings/toggle-module', [$this, 'toggleModule']);
    }

    public function audit($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Журнал аудита</h2>
            <p class='text-gray-500 mt-1'>Полный контроль всех действий пользователей в системе.</p>
        </div>

        <div class='bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden'>
            <table class='w-full text-left'>
                <thead class='bg-gray-50 border-b'>
                    <tr class='text-[10px] font-bold text-gray-400 uppercase tracking-widest'>
                        <th class='px-8 py-4'>Кто</th>
                        <th class='px-8 py-4'>Действие</th>
                        <th class='px-8 py-4'>Модуль</th>
                        <th class='px-8 py-4'>Когда</th>
                        <th class='px-8 py-4'>IP</th>
                    </tr>
                </thead>
                <tbody class='divide-y'>
                    <tr class='hover:bg-gray-50 transition-colors'>
                        <td class='px-8 py-4 font-bold'>Admin</td>
                        <td class='px-8 py-4 text-sm'>Создание брони #BK_64a2b</td>
                        <td class='px-8 py-4'><span class='text-blue-600 font-bold text-xs'>Booking</span></td>
                        <td class='px-8 py-4 text-gray-400 text-xs'>10.06.2024 14:02:12</td>
                        <td class='px-8 py-4 text-gray-400 text-xs'>192.168.1.15</td>
                    </tr>
                </tbody>
            </table>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Аудит - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function backup($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Резервное копирование</h2>
            <p class='text-gray-500 mt-1'>Управление архивами данных и восстановление системы.</p>
        </div>

        <div class='grid grid-cols-1 md:grid-cols-2 gap-8'>
            <div class='bg-white p-8 rounded-3xl border border-gray-100 shadow-sm'>
                <h3 class='font-bold text-lg mb-4'>Создать копию</h3>
                <p class='text-sm text-gray-500 mb-6'>Система упакует папку storage/ в ZIP-архив и сохранит в backup/.</p>
                <button class='bg-blue-600 text-white px-8 py-3 rounded-xl font-bold w-full'>Запустить бэкап сейчас</button>
            </div>

            <div class='bg-white p-8 rounded-3xl border border-gray-100 shadow-sm'>
                <h3 class='font-bold text-lg mb-4'>Последние копии</h3>
                <div class='space-y-3'>
                    <div class='flex items-center justify-between p-4 bg-gray-50 rounded-2xl'>
                        <span class='text-sm font-medium'>backup_2024-06-10.zip</span>
                        <span class='text-xs text-gray-400 font-bold'>1.2 MB</span>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Бэкапы - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function toggleModule($request, $response): void
    {
        $data = $request->getBody();
        $config = $this->container->get(\App\Core\Config::class);

        $modules = $config->get('modules', []);
        $modules[$data['module']] = (bool)$data['enabled'];

        $config->set('modules', $modules);
        $response->json(['success' => true]);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $config = $this->container->get(\App\Core\Config::class);
        $modulesConfig = $config->get('modules', []);

        $moduleManager = $this->container->get(\App\Module\ModuleManager::class);
        // We need to list all physical module directories
        $modulesPath = __DIR__ . '/../../modules';
        $allModules = array_diff(scandir($modulesPath), ['.', '..']);

        $modulesHtml = "";
        foreach ($allModules as $mod) {
            $isEnabled = !isset($modulesConfig[$mod]) || $modulesConfig[$mod] === true;
            $checked = $isEnabled ? 'checked' : '';
            $modulesHtml .= "
            <div class='flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100'>
                <div class='flex items-center space-x-3'>
                    <div class='w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm'>
                        <i class='fas fa-cube text-blue-500'></i>
                    </div>
                    <div>
                        <p class='font-bold text-gray-800'>$mod</p>
                        <p class='text-[10px] text-gray-400 font-bold uppercase tracking-widest'>System Module</p>
                    </div>
                </div>
                <label class='relative inline-flex items-center cursor-pointer'>
                    <input type='checkbox' value='' class='sr-only peer' $checked onchange='toggleModule(\"$mod\", this.checked)'>
                    <div class=\"w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600\"></div>
                </label>
            </div>";
        }

        $content = "
        <div class='mb-8'>
            <h2 class='text-3xl font-bold text-gray-800'>Системные настройки</h2>
            <p class='text-gray-500 mt-1'>Конфигурация ядра, безопасность и управление доступом.</p>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-4 gap-10'>
            <aside class='lg:col-span-1 space-y-3'>
                <button class='w-full text-left px-6 py-4 bg-white border border-blue-100 rounded-2xl text-blue-600 font-bold shadow-sm shadow-blue-50 flex items-center transition-all hover:translate-x-1'>
                    <i class='fas fa-sliders-h mr-4 text-xl'></i> Общие
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all'>
                    <i class='fas fa-user-lock mr-3'></i> Пользователи
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all'>
                    <i class='fas fa-shield-alt mr-3'></i> Безопасность
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all'>
                    <i class='fas fa-database mr-3'></i> Резервные копии
                </button>
                <button class='w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-all text-red-500'>
                    <i class='fas fa-history mr-3'></i> Журнал аудита
                </button>
            </aside>

            <div class='lg:col-span-3 space-y-10'>
                <div class='bg-white p-10 rounded-[40px] border border-gray-100 shadow-xl shadow-blue-50/20'>
                    <h3 class='text-2xl font-extrabold text-gray-900 mb-8 border-b border-gray-50 pb-6'>Глобальные параметры</h3>

                    <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Название учреждения</label>
                            <input type='text' value='Санаторий Солнечный' class='w-full border-gray-200 rounded-xl focus:ring-blue-500'>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Часовой пояс</label>
                            <select class='w-full border-gray-200 rounded-xl'>
                                <option>Europe/Moscow (GMT+3)</option>
                                <option>Europe/Minsk (GMT+3)</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Валюта учета</label>
                            <select class='w-full border-gray-200 rounded-xl'>
                                <option>Российский рубль (₽)</option>
                                <option>Белорусский рубль (BYN)</option>
                            </select>
                        </div>
                        <div>
                            <label class='block text-xs font-bold text-gray-400 uppercase mb-2'>Режим хранения</label>
                            <div class='flex items-center mt-2 space-x-4'>
                                <span class='px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-[10px] font-bold uppercase'>JSON (Active)</span>
                                <span class='text-gray-300 text-[10px] font-bold uppercase cursor-pointer hover:text-blue-400'>Switch to SQLite</span>
                            </div>
                        </div>
                    </div>

                    <div class='mt-10 flex justify-end space-x-3'>
                        <button class='px-6 py-2 bg-gray-100 text-gray-500 font-bold rounded-xl'>Сбросить</button>
                        <button class='px-8 py-2 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-100'>Сохранить изменения</button>
                    </div>
                </div>

                <div class='bg-white p-10 rounded-[40px] border border-gray-100 shadow-xl shadow-purple-50/20'>
                    <div class='flex items-center justify-between mb-8'>
                        <h3 class='text-2xl font-extrabold text-gray-900'>Управление модулями</h3>
                        <span class='px-4 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold'>Dynamic Core</span>
                    </div>
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                        $modulesHtml
                    </div>
                </div>

                <div class='bg-gradient-to-br from-orange-50 to-white border border-orange-100 p-8 rounded-[40px] flex items-start space-x-6'>
                    <div class='w-16 h-16 bg-orange-100 text-orange-600 rounded-3xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-orange-100'>
                        <i class='fas fa-exclamation-triangle text-2xl'></i>
                    </div>
                    <div>
                        <h4 class='text-xl font-extrabold text-orange-900 mb-2'>Внимание: Режим отладки</h4>
                        <p class='text-sm text-orange-800 opacity-70 leading-relaxed'>В данный момент включен расширенный режим логирования. Это может замедлить работу системы при большом количестве одновременных пользователей. Рекомендуется отключать в промышленной эксплуатации.</p>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Настройки - VSPRINT 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
