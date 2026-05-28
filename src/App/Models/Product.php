<?php
namespace App\Models;
class Product extends BaseModel {
    public function getAll() {
        return $this->db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id")->fetchAll();
    }
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function create($data) {
        $sql = "INSERT INTO products (name, composition, gost, manufacturer, volume, alcohol, sugar, expiration, barcode, category_id, extra_data)
                VALUES (:name, :composition, :gost, :manufacturer, :volume, :alcohol, :sugar, :expiration, :barcode, :category_id, :extra_data)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }
    public function update($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->db->prepare($sql)->execute($data);
    }
    public function delete($id) {
        return $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
}
