<?php
// api/controllers/PromocodesController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class PromocodesController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        require_admin();
        $promocodes = $this->storage->all('promocodes');
        json_out($promocodes);
    }

    public function create() {
        require_admin();
        $data = json_in();

        $code = strtoupper(trim($data['code'] ?? ''));
        $discount = (int)($data['discount_percent'] ?? 0);
        $usesLeft = isset($data['uses_left']) ? (int)$data['uses_left'] : 100;

        if (empty($code) || $discount <= 0 || $discount > 100) {
            json_out(['error' => 'Укажите корректный промокод и процент скидки (1-100)'], 400);
        }

        $existing = $this->storage->get('promocodes', ['code' => $code]);
        if (!empty($existing)) {
            json_out(['error' => 'Промокод с таким именем уже существует'], 400);
        }

        $newPromo = [
            'id' => 'promo_code_' . uniqid(),
            'code' => $code,
            'discount_percent' => $discount,
            'uses_left' => $usesLeft,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $created = $this->storage->insert('promocodes', $newPromo);
        json_out($created, 201);
    }

    public function check() {
        $data = json_in();
        $code = strtoupper(trim($data['code'] ?? $_GET['code'] ?? ''));

        if (empty($code)) {
            json_out(['valid' => false, 'error' => 'Промокод не введен'], 400);
        }

        $matches = $this->storage->get('promocodes', ['code' => $code]);
        if (empty($matches)) {
            json_out(['valid' => false, 'error' => 'Промокод не найден']);
        }

        $p = $matches[0];
        if (empty($p['active']) || (isset($p['uses_left']) && $p['uses_left'] <= 0)) {
            json_out(['valid' => false, 'error' => 'Промокод недействителен или исчерпан']);
        }

        json_out([
            'valid' => true,
            'code' => $p['code'],
            'discount_percent' => (int)$p['discount_percent'],
            'message' => "Промокод применен! Скидка {$p['discount_percent']}%"
        ]);
    }

    public function delete($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID промокода не указан'], 400);
        }

        $deleted = $this->storage->delete('promocodes', $id);
        if (!$deleted) {
            json_out(['error' => 'Промокод не найден'], 404);
        }

        json_out(['success' => true, 'message' => 'Промокод удален']);
    }
}
