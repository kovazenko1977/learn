<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class PatientManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('patients');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function add($patientData) {
        $patientData['id'] = uniqid();
        $patientData['created_at'] = date('Y-m-d H:i:s');
        $this->store->add($patientData);
        return $patientData['id'];
    }

    public function update($id, $patientData) {
        return $this->store->updateById($id, $patientData);
    }

    public function delete($id) {
        return $this->store->deleteById($id);
    }

    public function search($query) {
        $patients = $this->getAll();
        $query = mb_strtolower($query);
        return array_filter($patients, function($patient) use ($query) {
            return mb_strpos(mb_strtolower($patient['name'] ?? ''), $query) !== false ||
                   mb_strpos(mb_strtolower($patient['phone'] ?? ''), $query) !== false ||
                   mb_strpos(mb_strtolower($patient['card_number'] ?? ''), $query) !== false;
        });
    }

    public function addHistoryEntry($patientId, $entry) {
        $patient = $this->getById($patientId);
        if (!$patient) return false;

        if (!isset($patient['history'])) {
            $patient['history'] = [];
        }

        $entry['id'] = uniqid();
        $entry['date'] = date('Y-m-d H:i:s');
        $patient['history'][] = $entry;

        return $this->update($patientId, ['history' => $patient['history']]);
    }

    public function addComment($patientId, $commentData) {
        $patient = $this->getById($patientId);
        if (!$patient) return false;

        if (!isset($patient['comments'])) {
            $patient['comments'] = [];
        }

        $commentData['id'] = uniqid();
        $commentData['date'] = date('Y-m-d H:i:s');
        $patient['comments'][] = $commentData;

        return $this->update($patientId, ['comments' => $patient['comments']]);
    }
}
