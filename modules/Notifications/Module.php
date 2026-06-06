<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/api/notifications', [$this, 'getNotifications']);
    }

    public function getNotifications($request, $response): void
    {
        $response->json([
            [
                'id' => 1,
                'title' => 'Новое бронирование',
                'text' => 'Петров С.В. забронировал Люкс №302',
                'time' => '5 мин назад',
                'type' => 'info'
            ],
            [
                'id' => 2,
                'title' => 'Критический остаток',
                'text' => 'Маски одноразовые заканчиваются на складе',
                'time' => '1 час назад',
                'type' => 'warning'
            ]
        ]);
    }
}
