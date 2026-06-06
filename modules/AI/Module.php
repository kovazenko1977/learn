<?php

declare(strict_types=1);

namespace App\Modules\AI;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('POST', '/api/ai/command', [$this, 'processCommand']);
    }

    public function processCommand($request, $response): void
    {
        $data = $request->getBody();
        $command = mb_strtolower($data['command'] ?? '');

        $answer = "Извините, я не понял команду. Попробуйте 'найди свободный номер' или 'кто сейчас в санатории?'.";
        $action = null;

        if (str_contains($command, 'свободн') && str_contains($command, 'номер')) {
            $storage = $this->container->get(\App\Storage\StorageManager::class);
            $rooms = $storage->find('rooms', ['status' => 'свободен']);
            $count = count($rooms);
            $answer = "Я нашел $count свободных номеров. Рекомендую №" . ($rooms[0]['number'] ?? '...') . " ({$rooms[0]['type']}).";
            $action = 'navigate_to_accommodation';
        } elseif (str_contains($command, 'гост') || str_contains($command, 'пациент')) {
            $storage = $this->container->get(\App\Storage\StorageManager::class);
            $guests = $storage->find('guests');
            $count = count($guests);
            $answer = "В базе данных зарегистрировано $count гостей. Открыть список?";
            $action = 'navigate_to_guests';
        } elseif (str_contains($command, 'выручк') || str_contains($command, 'денег')) {
            $answer = "Выручка за текущий месяц составила 12.8М ₽. Это на 12% больше, чем в прошлом месяце.";
            $action = 'navigate_to_finance';
        } elseif (str_contains($command, 'отчет') || str_contains($command, 'аналитик')) {
            $answer = "Открываю модуль аналитики. Какой отчет сформировать?";
            $action = 'navigate_to_reports';
        } elseif (str_contains($command, 'настройк') || str_contains($command, 'безопасност')) {
            $answer = "Перехожу в системные настройки...";
            $action = 'navigate_to_settings';
        } elseif (str_contains($command, 'склад') || str_contains($command, 'товары')) {
            $answer = "Проверяю остатки на складе... У нас 12 позиций с критическим остатком.";
            $action = 'navigate_to_inventory';
        } elseif (str_contains($command, 'врач') || str_contains($command, 'медицин')) {
            $answer = "Открываю медицинский модуль. Показать расписание врачей?";
            $action = 'navigate_to_medical';
        } elseif (str_contains($command, 'бронировани') || str_contains($command, 'шахматк')) {
            $answer = "Открываю шахматку бронирований. На какую дату смотрим?";
            $action = 'navigate_to_booking';
        } elseif (str_contains($command, 'помощь') || str_contains($command, 'инструкц')) {
            $answer = "Открываю центр поддержки Sanatorium 2.0. Чем могу помочь?";
            $action = 'navigate_to_help';
        } elseif (str_contains($command, 'уборк')) {
            $answer = "Список номеров, требующих уборки: №102, №205, №310. Направить персонал?";
            $action = 'navigate_to_accommodation';
        } elseif (str_contains($command, 'ремонт')) {
            $answer = "В ремонте сейчас 2 номера (№404, №501). Открыть технический журнал?";
            $action = 'navigate_to_accommodation';
        } elseif (str_contains($command, 'семей')) {
            $answer = "Подобрал 3 варианта для семейного размещения в Корпусе Б. Показать?";
            $action = 'navigate_to_booking';
        } elseif (str_contains($command, 'прогноз')) {
            $answer = "Ожидаемая загрузка на следующую неделю: 87%. Это на 5% выше нормы.";
            $action = 'navigate_to_reports';
        } elseif (str_contains($command, 'неоплач')) {
            $answer = "Найдено 5 неоплаченных счетов на общую сумму 145,000 ₽. Показать список?";
            $action = 'navigate_to_finance';
        } elseif (str_contains($command, 'услуг')) {
            $answer = "Самые популярные услуги на сегодня: Гидромассаж, Кедровая бочка. Открыть каталог?";
            $action = 'navigate_to_medical';
        } elseif (str_contains($command, 'транспорт')) {
            $answer = "На сегодня запланировано 4 трансфера. Открыть расписание?";
            $action = 'navigate_to_inventory';
        } elseif (str_contains($command, 'событи') || str_contains($command, 'лог')) {
            $answer = "Открываю системный журнал событий. Последнее действие: Вход администратора.";
            $action = 'navigate_to_settings';
        } elseif (str_contains($command, 'бэкап') || str_contains($command, 'резерв')) {
            $answer = "Последняя резервная копия создана сегодня в 03:00. Создать новую сейчас?";
            $action = 'navigate_to_settings';
        } elseif (str_contains($command, 'здоровье') || str_contains($command, 'статус')) {
            $answer = "Все системы работают в штатном режиме. Использование диска: 12%.";
            $action = 'navigate_to_settings';
        } elseif (str_contains($command, 'сотрудник') || str_contains($command, 'персонал')) {
            $answer = "В смене сегодня 24 сотрудника. Показать график дежурств?";
            $action = 'navigate_to_settings';
        } elseif (str_contains($command, 'питание') || str_contains($command, 'меню')) {
            $answer = "Меню на сегодня (Диета №5) сформировано. Печатать листы заказа?";
            $action = 'navigate_to_inventory';
        } elseif (str_contains($command, 'отзыв') || str_contains($command, 'жалоб')) {
            $answer = "Средняя оценка за неделю: 4.8. Есть 1 новый отзыв. Прочитать?";
            $action = 'navigate_to_help';
        } elseif (str_contains($command, 'задач') || str_contains($command, 'план')) {
            $answer = "На сегодня запланировано 15 технических задач. Открыть список?";
            $action = 'navigate_to_accommodation';
        }

        $response->json([
            'answer' => $answer,
            'action' => $action
        ]);
    }
}
