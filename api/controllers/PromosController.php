<?php
// api/controllers/PromosController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class PromosController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        $promos = $this->storage->all('promos');
        json_out($promos);
    }

    public function create() {
        require_admin();
        $data = json_in();

        if (empty($data['title'])) {
            json_out(['error' => 'Заголовок акции обязателен'], 400);
        }

        $newPromo = [
            'id' => 'promo_' . uniqid(),
            'title' => trim($data['title']),
            'description' => trim($data['description'] ?? ''),
            'image' => trim($data['image'] ?? '🌹'),
            'badge' => trim($data['badge'] ?? 'АКЦИЯ'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $created = $this->storage->insert('promos', $newPromo);
        json_out($created, 201);
    }

    public function delete($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID акции не указан'], 400);
        }

        $deleted = $this->storage->delete('promos', $id);
        if (!$deleted) {
            json_out(['error' => 'Акция не найдена'], 404);
        }

        json_out(['success' => true, 'message' => 'Акция успешно удалена']);
    }
}
