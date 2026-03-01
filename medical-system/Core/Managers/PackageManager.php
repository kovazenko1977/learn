<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class PackageManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('procedure_packages');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function add($data) {
        $data['id'] = uniqid();
        $this->store->add($data);
        (new LogManager())->log('Создан пакет процедур', ['name' => $data['name']]);
        return $data['id'];
    }

    public function update($id, $data) {
        $res = $this->store->updateById($id, $data);
        if ($res) {
            (new LogManager())->log('Обновлен пакет процедур', ['id' => $id, 'name' => $data['name']]);
        }
        return $res;
    }

    public function delete($id) {
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удален пакет процедур', ['id' => $id]);
        }
        return $res;
    }
}
