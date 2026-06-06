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
        }

        $response->json([
            'answer' => $answer,
            'action' => $action
        ]);
    }
}
