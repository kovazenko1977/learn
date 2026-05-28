<?php
namespace App\Controllers;

use App\Models\Database;

class ProductController {
    public function index() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function store($data) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO products (name, category_id, composition, gost, manufacturer, volume, alcohol, sugar, expiration, barcode, logo, warnings) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'], $data['category_id'] ?? null, $data['composition'] ?? null,
            $data['gost'] ?? null, $data['manufacturer'] ?? null, $data['volume'] ?? null,
            $data['alcohol'] ?? null, $data['sugar'] ?? null, $data['expiration'] ?? null,
            $data['barcode'] ?? null, $data['logo'] ?? null, $data['warnings'] ?? null
        ]);
        return ['status' => 'success', 'id' => $db->lastInsertId()];
    }
}
