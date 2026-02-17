<?php
namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class StaffManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('staff');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function create($data) {
        $id = uniqid();
        $newStaff = [
            'id' => $id,
            'name' => $data['name'],
            'role' => $data['role'] ?? 'specialist',
            'specialization' => $data['specialization'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->store->add($newStaff);
        return $id;
    }

    public function update($id, $data) {
        return $this->store->updateById($id, $data);
    }

    public function delete($id) {
        return $this->store->deleteById($id);
    }
}
