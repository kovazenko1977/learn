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
        return $procedureData['id'];
    }

    public function update($id, $procedureData) {
        return $this->store->updateById($id, $procedureData);
    }

    public function delete($id) {
        return $this->store->deleteById($id);
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
