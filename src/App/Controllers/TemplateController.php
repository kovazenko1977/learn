<?php
namespace App\Controllers;

use App\Models\Database;

class TemplateController {
    public function index() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM templates ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function store($data) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO templates (name, category, width, height, data) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'], $data['category'], $data['width'], $data['height'], json_encode($data['data'])
        ]);
        return ['status' => 'success', 'id' => $db->lastInsertId()];
    }
}
