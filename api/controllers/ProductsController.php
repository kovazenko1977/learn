<?php
// api/controllers/ProductsController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class ProductsController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function index() {
        $products = $this->storage->all('products');
        // Filter soft-deleted unless admin requests or explicitly asked
        $includeDeleted = isset($_GET['include_deleted']) && $_GET['include_deleted'] == '1';
        $category = $_GET['category'] ?? null;

        $filtered = array_filter($products, function($p) use ($includeDeleted, $category) {
            if (!$includeDeleted && !empty($p['deleted'])) {
                return false;
            }
            if ($category && $category !== 'Все' && ($p['category'] ?? '') !== $category) {
                return false;
            }
            return true;
        });

        json_out(array_values($filtered));
    }

    public function show($id = null) {
        if (!$id) {
            json_out(['error' => 'ID товара не указан'], 400);
        }
        $product = $this->storage->getById('products', $id);
        if (!$product || (!empty($product['deleted']) && !isset($_GET['include_deleted']))) {
            json_out(['error' => 'Товар не найден'], 404);
        }
        json_out($product);
    }

    public function create() {
        require_admin();
        $data = json_in();

        if (empty($data['title']) || !isset($data['price'])) {
            json_out(['error' => 'Название и цена товара обязательны'], 400);
        }

        $newProduct = [
            'id' => 'prod_' . uniqid(),
            'title' => trim($data['title']),
            'category' => trim($data['category'] ?? 'Букеты'),
            'price' => (float)$data['price'],
            'old_price' => isset($data['old_price']) && $data['old_price'] !== '' ? (float)$data['old_price'] : null,
            'description' => trim($data['description'] ?? ''),
            'image' => trim($data['image'] ?? '🌹'),
            'is_sale' => !empty($data['is_sale']) ? 1 : 0,
            'stock' => isset($data['stock']) ? (int)$data['stock'] : 10,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $created = $this->storage->insert('products', $newProduct);
        json_out($created, 201);
    }

    public function update($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID товара не указан'], 400);
        }
        $existing = $this->storage->getById('products', $id);
        if (!$existing) {
            json_out(['error' => 'Товар не найден'], 404);
        }

        $data = json_in();
        $fields = [];
        if (isset($data['title'])) $fields['title'] = trim($data['title']);
        if (isset($data['category'])) $fields['category'] = trim($data['category']);
        if (isset($data['price'])) $fields['price'] = (float)$data['price'];
        if (array_key_exists('old_price', $data)) {
            $fields['old_price'] = ($data['old_price'] !== null && $data['old_price'] !== '') ? (float)$data['old_price'] : null;
        }
        if (isset($data['description'])) $fields['description'] = trim($data['description']);
        if (isset($data['image'])) $fields['image'] = trim($data['image']);
        if (isset($data['is_sale'])) $fields['is_sale'] = !empty($data['is_sale']) ? 1 : 0;
        if (isset($data['stock'])) $fields['stock'] = (int)$data['stock'];

        $updated = $this->storage->update('products', $id, $fields);
        json_out($updated);
    }

    public function toggleSale($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID товара не указан'], 400);
        }
        $product = $this->storage->getById('products', $id);
        if (!$product) {
            json_out(['error' => 'Товар не найден'], 404);
        }

        $newSale = empty($product['is_sale']) ? 1 : 0;
        $updated = $this->storage->update('products', $id, ['is_sale' => $newSale]);
        json_out($updated);
    }

    public function delete($id = null) {
        require_admin();
        if (!$id) {
            json_out(['error' => 'ID товара не указан'], 400);
        }
        $product = $this->storage->getById('products', $id);
        if (!$product) {
            json_out(['error' => 'Товар не найден'], 404);
        }

        // Perform soft delete
        $updated = $this->storage->update('products', $id, ['deleted' => 1]);
        json_out(['success' => true, 'message' => 'Товар помечен как удаленный', 'product' => $updated]);
    }
}
