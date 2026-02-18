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
        (new LogManager())->log('Регистрация пациента', ['name' => $patientData['name']]);
        return $patientData['id'];
    }

    public function update($id, $patientData) {
        $res = $this->store->updateById($id, $patientData);
        if ($res) {
            (new LogManager())->log('Обновление данных пациента', ['id' => $id]);
        }
        return $res;
    }

    public function delete($id) {
        $patient = $this->getById($id);
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удаление пациента', ['id' => $id, 'name' => $patient['name'] ?? 'Unknown']);
        }
        return $res;
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

        $res = $this->update($patientId, ['history' => $patient['history']]);
        if ($res) {
            (new LogManager())->log('Добавлена запись в историю болезни', ['patient_id' => $patientId, 'diagnosis' => $entry['diagnosis_code']]);
        }
        return $res;
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
