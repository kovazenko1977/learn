<?php

declare(strict_types=1);

namespace App\Modules\Accommodation;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);
        $router->addRoute('GET', '/accommodation', [$this, 'index']);
        $router->addRoute('POST', '/accommodation/add', [$this, 'addRoom']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $rooms = $storage->find('rooms');
        return $renderer->render('Accommodation/index', ['rooms' => $rooms]);
    }

    public function addRoom($request, $response): void
    {
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $data = $request->getBody();

        if (!empty($data['number'])) {
            $storage->insert('rooms', [
                'building' => $data['building'] ?? '1',
                'number' => $data['number'],
                'type' => $data['type'] ?? 'Стандарт',
                'floor' => (int)($data['floor'] ?? 1),
                'status' => 'свободен',
                'price' => (float)($data['price'] ?? 3500),
                'places' => (int)($data['places'] ?? 1),
                'amenities' => $data['amenities'] ?? [],
                'image' => $data['image'] ?? '',
                'description' => $data['description'] ?? '',
                'occupied_places' => 0
            ]);
        }

        $renderer = $this->container->get(\App\View\Renderer::class);
        $response->redirect($renderer->url('/accommodation'));
    }
}
