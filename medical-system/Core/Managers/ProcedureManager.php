<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class ProcedureManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('procedures_directory');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function add($procedureData) {
        $procedureData['id'] = uniqid();
        $this->store->add($procedureData);
        (new LogManager())->log('Добавление процедуры в справочник', ['name' => $procedureData['name']]);
        return $procedureData['id'];
    }

    public function update($id, $procedureData) {
        $res = $this->store->updateById($id, $procedureData);
        if ($res) {
            (new LogManager())->log('Обновление процедуры в справочнике', ['id' => $id]);
        }
        return $res;
    }

    public function delete($id) {
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удаление процедуры из справочника', ['id' => $id]);
        }
        return $res;
    }

    public function getStaffForProcedure($procedureId) {
        $proc = $this->getById($procedureId);
        return $proc['assigned_staff'] ?? [];
    }

    public function isStaffAssigned($procedureId, $staffId) {
        $staff = $this->getStaffForProcedure($procedureId);
        return in_array($staffId, $staff);
    }
}
