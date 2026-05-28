<?php
namespace App\Models;
class Category extends BaseModel {
    public function getAll() {
        return $this->db->query("SELECT * FROM categories")->fetchAll();
    }
}
