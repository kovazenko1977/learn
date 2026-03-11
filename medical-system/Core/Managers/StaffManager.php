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
            'access_code' => $data['access_code'] ?? '000000',
            'permissions' => $data['permissions'] ?? [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->store->add($newStaff);
        (new LogManager())->log('Добавление сотрудника', ['name' => $data['name'], 'role' => $data['role']]);
        return $id;
    }

    public function update($id, $data) {
        $res = $this->store->updateById($id, $data);
        if ($res) {
            (new LogManager())->log('Обновление данных сотрудника', ['id' => $id]);
        }
        return $res;
    }

    public function delete($id) {
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удаление сотрудника', ['id' => $id]);
        }
        return $res;
    }
}
