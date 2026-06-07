<?php

declare(strict_types=1);

namespace App\Modules\Booking;

use App\Module\BaseModule;
use App\Core\Router;

class Module extends BaseModule
{
    public function boot(): void
    {
        $router = $this->container->get(Router::class);

        $router->addRoute('GET', '/booking', [$this, 'index']);
        $router->addRoute('GET', '/booking/create', [$this, 'create']);
        $router->addRoute('POST', '/booking/save', [$this, 'save']);
    }

    public function index($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $rooms = $storage->find('rooms');

        return $renderer->render('Booking/index', [
            'rooms' => $rooms
        ]);
    }

    public function create($request, $response): string
    {
        $renderer = $this->container->get(\App\View\Renderer::class);
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $params = $request->getBody();
        $selectedRoom = $params['room'] ?? '';

        $rooms = $storage->find('rooms');

        return $renderer->render('Booking/create', [
            'rooms' => $rooms,
            'selectedRoom' => $selectedRoom
        ]);
    }

    public function save($request, $response): void
    {
        $data = $request->getBody();
        $storage = $this->container->get(\App\Storage\StorageManager::class);
        $renderer = $this->container->get(\App\View\Renderer::class);

        $room = $storage->findOne('rooms', ['number' => $data['room_number']]);
        if (!$room) {
             $response->redirect($renderer->url('/booking'));
             return;
        }

        // Validate placement rules
        $existingBookings = $storage->find('bookings', ['room_number' => $data['room_number'], 'status' => 'confirmed']);
        $existingGuests = [];
        foreach($existingBookings as $eb) {
            $existingGuests[] = [
                'gender' => $eb['guest_gender'] ?? 'unknown',
                'age' => (int)($eb['guest_age'] ?? 30)
            ];
        }

        $newGuest = [
            'gender' => $data['guest_gender'],
            'age' => (int)$data['guest_age'],
            'is_family' => $data['is_family']
        ];

        $errors = \App\Utils\RulesEngine::validatePlacement($room, $newGuest, $existingGuests);

        if (!empty($errors)) {
            $response->setContent($renderer->render('Booking/error', ['errors' => $errors]));
            $response->send();
            return;
        }

        $storage->insert('bookings', [
            'room_number' => $data['room_number'],
            'guest_name' => $data['guest_name'],
            'guest_gender' => $data['guest_gender'],
            'guest_age' => $data['guest_age'],
            'is_family' => $data['is_family'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'status' => 'confirmed'
        ]);

        // Update room occupancy
        $room['occupied_places'] = ($room['occupied_places'] ?? 0) + 1;
        if ($room['occupied_places'] >= $room['places']) {
            $room['status'] = 'занят';
        }
        $storage->update('rooms', $room['id'], $room);

        $response->redirect($renderer->url('/booking'));
    }
}
