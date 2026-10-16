<?php
/**
 * ProductsController
 */

class ProductsController {
    public function handle($method) {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($method === 'GET') {
            if ($id) {
                $this->getOne($id);
            } else {
                $this->getAll();
            }
        } elseif ($method === 'POST') {
            require_auth(true);
            $this->create();
        } elseif ($method === 'PUT') {
            require_auth(true);
            $this->update($id);
        } elseif ($method === 'DELETE') {
            require_auth(true);
            $this->delete($id);
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function getAll() {
        $db = get_storage();
        $products = $db->get('products');
        $activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';

        if ($activeOnly) {
            $products = array_values(array_filter($products, function($p) {
                return !isset($p['active']) || $p['active'] == true || $p['active'] === 1 || $p['active'] === '1';
            }));
        }

        json_out(['products' => $products]);
    }

    private function getOne($id) {
        $db = get_storage();
        $product = $db->find('products', $id);
        if (!$product) {
            json_out(['error' => 'Товар не найден'], 404);
        }
        json_out(['product' => $product, 'title' => $product['title'], 'price' => $product['price']]);
    }

    private function create() {
        $data = json_in();
        if (empty($data['title']) || empty($data['price'])) {
            json_out(['error' => 'Укажите название и цену товара'], 400);
        }

        $db = get_storage();
        $newProduct = [
            'title' => clean($data['title']),
            'description' => clean($data['description'] ?? ''),
            'price' => (float)$data['price'],
            'old_price' => !empty($data['old_price']) ? (float)$data['old_price'] : null,
            'category' => clean($data['category'] ?? 'Букеты'),
            'stock' => isset($data['stock']) ? (int)$data['stock'] : 10,
            'emoji' => clean($data['emoji'] ?? '💐'),
            'image' => clean($data['image'] ?? ''),
            'is_sale' => !empty($data['is_sale']),
            'active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $db->insert('products', $newProduct);
        $newProduct['id'] = $id;

        json_out(['success' => true, 'product' => $newProduct]);
    }

    private function update($id) {
        if (!$id) {
            $data = json_in();
            $id = $data['id'] ?? null;
        }

        if (!$id) {
            json_out(['error' => 'ID товара не указан'], 400);
        }

        $data = json_in();
        $db = get_storage();
        $product = $db->find('products', $id);
        if (!$product) {
            json_out(['error' => 'Товар не найден'], 404);
        }

        $updateData = [
            'title' => clean($data['title'] ?? $product['title']),
            'description' => clean($data['description'] ?? $product['description']),
            'price' => isset($data['price']) ? (float)$data['price'] : $product['price'],
            'old_price' => isset($data['old_price']) ? ($data['old_price'] ? (float)$data['old_price'] : null) : ($product['old_price'] ?? null),
            'category' => clean($data['category'] ?? $product['category']),
            'stock' => isset($data['stock']) ? (int)$data['stock'] : $product['stock'],
            'emoji' => clean($data['emoji'] ?? $product['emoji']),
            'image' => clean($data['image'] ?? $product['image']),
            'is_sale' => isset($data['is_sale']) ? (bool)$data['is_sale'] : ($product['is_sale'] ?? false),
            'active' => isset($data['active']) ? (bool)$data['active'] : ($product['active'] ?? true)
        ];

        $db->update('products', $id, $updateData);
        json_out(['success' => true]);
    }

    private function delete($id) {
        if (!$id) {
            $data = json_in();
            $id = $data['id'] ?? null;
        }

        if (!$id) {
            json_out(['error' => 'ID товара не указан'], 400);
        }

        $db = get_storage();
        $db->update('products', $id, ['active' => false]);
        json_out(['success' => true]);
    }
}
