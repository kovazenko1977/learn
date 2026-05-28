<?php
namespace App\Models;
class Template extends BaseModel {
    public function getAll() {
        return $this->db->query("SELECT t.*, c.name as category_name FROM templates t LEFT JOIN categories c ON t.category_id = c.id")->fetchAll();
    }
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM templates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function create($data) {
        $sql = "INSERT INTO templates (name, category_id, width, height, unit, json_data)
                VALUES (:name, :category_id, :width, :height, :unit, :json_data)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }
    public function update($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE templates SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->db->prepare($sql)->execute($data);
    }
    public function delete($id) {
        return $this->db->prepare("DELETE FROM templates WHERE id = ?")->execute([$id]);
    }
}
