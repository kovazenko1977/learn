<?php

declare(strict_types=1);

namespace App\Modules\Help;

use App\Module\BaseModule;
use App\Core\Router;
use App\View\Renderer;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/help', [$this, 'index']);
        $router->addRoute('GET', '/help/about', [$this, 'about']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(Renderer::class);

        $content = "
        <div class='max-w-4xl mx-auto bg-white p-8 rounded-lg shadow'>
            <h1 class='text-3xl font-bold mb-6 border-b pb-4'>Центр поддержки Sanatorium 2.0</h1>

            <div class='space-y-8'>
                <section>
                    <h2 class='text-xl font-semibold mb-3'>1. Введение</h2>
                    <p class='text-gray-700 leading-relaxed'>Sanatorium 2.0 — это современная ERP/PMS система для управления санаториями, отелями и медицинскими центрами. Система построена на модульной архитектуре, что позволяет легко расширять функционал без изменения ядра.</p>
                </section>

                <section>
                    <h2 class='text-xl font-semibold mb-3'>2. Основные модули</h2>
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                        <div class='border p-4 rounded'>
                            <h3 class='font-bold text-blue-600'>Размещение (Accommodation)</h3>
                            <p class='text-sm text-gray-600'>Управление номерным фондом: корпуса, этажи, категории номеров и места. Контроль статусов (свободен, занят, ремонт, уборка).</p>
                        </div>
                        <div class='border p-4 rounded'>
                            <h3 class='font-bold text-blue-600'>Бронирование (Booking)</h3>
                            <p class='text-sm text-gray-600'>Визуальная 'шахматка' для быстрого поиска свободных мест и создания броней. Поддержка групповых и семейных заездов.</p>
                        </div>
                        <div class='border p-4 rounded'>
                            <h3 class='font-bold text-blue-600'>Гости (Guests)</h3>
                            <p class='text-sm text-gray-600'>Электронные карты гостей, история посещений, предпочтения и контактные данные.</p>
                        </div>
                        <div class='border p-4 rounded'>
                            <h3 class='font-bold text-blue-600'>Конструктор (Builders)</h3>
                            <p class='text-sm text-gray-600'>Возможность создавать новые типы данных (сущности) и экранные формы прямо из интерфейса без программирования.</p>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class='text-xl font-semibold mb-3'>3. Безопасность и Хранение</h2>
                    <p class='text-gray-700 leading-relaxed text-sm'>Данные хранятся в формате JSON с поддержкой транзакционного доступа через блокировку файлов (flock). Система поддерживает RBAC (права доступа на уровне ролей) и CSRF защиту для всех POST запросов.</p>
                </section>

                <section>
                    <h2 class='text-xl font-semibold mb-3 font-bold'>4. Подробная инструкция по эксплуатации</h2>

                    <div class='space-y-6'>
                        <div class='bg-gray-50 p-4 rounded'>
                            <h4 class='font-bold text-gray-800 border-b mb-2'>Заселение и правила</h4>
                            <p class='text-sm text-gray-700'>
                                Система автоматически проверяет правила при заселении:
                                <ul class='list-disc ml-5 mt-1'>
                                    <li>Запрет подселения мужчин к женщинам в один номер (кроме семейных пар).</li>
                                    <li>Учет возрастных ограничений (детские места, льготы).</li>
                                    <li>Автоматический расчет стоимости на основе типа питания и медицинского пакета.</li>
                                </ul>
                            </p>
                        </div>

                        <div class='bg-gray-50 p-4 rounded'>
                            <h4 class='font-bold text-gray-800 border-b mb-2'>Финансы и услуги</h4>
                            <p class='text-sm text-gray-700'>
                                Модуль 'Финансы' поддерживает:
                                <ul class='list-disc ml-5 mt-1'>
                                    <li>Разделение оплат: проживание, медицинские процедуры, SPA, питание.</li>
                                    <li>Работу с депозитами и бонусными баллами.</li>
                                    <li>Генерацию актов сверки и чеков.</li>
                                </ul>
                            </p>
                        </div>

                        <div class='bg-gray-50 p-4 rounded'>
                            <h4 class='font-bold text-gray-800 border-b mb-2'>Медицинский блок (Опционально)</h4>
                            <p class='text-sm text-gray-700'>
                                Позволяет вести электронную медицинскую карту пациента, назначать процедуры и формировать расписание работы кабинетов с учетом загрузки оборудования.
                            </p>
                        </div>

                        <div class='bg-gray-50 p-4 rounded'>
                            <h4 class='font-bold text-gray-800 border-b mb-2'>AI-Ассистент</h4>
                            <p class='text-sm text-gray-700'>
                                Встроенный помощник позволяет выполнять команды голосом или текстом: 'найди свободный люкс на двоих', 'сформируй отчет по загрузке за неделю'. Все действия требуют подтверждения оператора.
                            </p>
                        </div>
                    </div>
                </section>

                <div class='mt-12 p-6 bg-blue-50 border-l-4 border-blue-500 rounded'>
                    <h3 class='font-bold'>Нужна помощь?</h3>
                    <p>Перейдите в раздел <a href='/help/about' class='text-blue-700 underline font-bold'>О программе</a> для связи с разработчиком.</p>
                </div>
            </div>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'Справка - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }

    public function about($request, $response): string
    {
        $renderer = $this->container->get(Renderer::class);

        $content = "
        <div class='max-w-2xl mx-auto bg-white p-8 rounded-lg shadow text-center'>
            <div class='mb-6'>
                <span class='text-5xl font-bold text-blue-600'>Sanatorium 2.0</span>
                <p class='text-gray-400 mt-2'>Версия 2.0.0 (Core Engine v1.0)</p>
            </div>

            <div class='border-t border-b py-8 my-8 space-y-4'>
                <p class='text-lg font-medium text-gray-800'>Разработчик:</p>
                <p class='text-2xl font-bold'>Коваженко С.Б.</p>

                <div class='flex flex-col items-center space-y-2 mt-6'>
                    <a href='tel:+375333533971' class='text-blue-600 text-xl font-semibold hover:underline'>+375 (33) 353-39-71</a>
                    <a href='https://wes.by' target='_blank' class='text-blue-600 text-xl font-semibold hover:underline'>wes.by</a>
                </div>
            </div>

            <p class='text-sm text-gray-500'>Разработано для профессионального управления санаторно-курортными комплексами.</p>
        </div>
        ";

        return $renderer->render('layout', [
            'title' => 'О программе - Sanatorium 2.0',
            'content' => $content,
            'user' => ['username' => 'Admin']
        ]);
    }
}
