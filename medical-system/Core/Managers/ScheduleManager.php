<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class ScheduleManager {
    private $store;

    public function __construct() {
        $this->store = new JsonStore('appointments');
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function assign($data) {
        // Collision detection
        $existing = $this->getByCabinet($data['cabinet_id'], $data['date']);
        foreach ($existing as $app) {
            if ($app['time'] == $data['time']) {
                return ['error' => 'Это время в данном кабинете уже занято!'];
            }
        }

        $data['id'] = uniqid();
        $data['status'] = $data['is_paid'] ? 'paid' : ($data['type'] === 'free' ? 'free' : 'unpaid');
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->store->add($data);
        return ['id' => $data['id']];
    }

    public function updateStatus($id, $status) {
        return $this->store->updateById($id, ['status' => $status]);
    }

    public function getByPatient($patientId) {
        $all = $this->getAll();
        return array_filter($all, function($item) use ($patientId) {
            return $item['patient_id'] == $patientId;
        });
    }

    public function getByDate($date) {
        $all = $this->getAll();
        return array_filter($all, function($item) use ($date) {
            return $item['date'] == $date;
        });
    }

    public function getByCabinet($cabinetId, $date = null) {
        $all = $this->getAll();
        return array_filter($all, function($item) use ($cabinetId, $date) {
            $match = $item['cabinet_id'] == $cabinetId;
            if ($date) {
                $match = $match && $item['date'] == $date;
            }
            return $match;
        });
    }

    public function markAttended($id, $nurseName) {
        return $this->store->updateById($id, [
            'attended' => true,
            'attended_at' => date('Y-m-d H:i:s'),
            'performed_by' => $nurseName
        ]);
    }

    public function markPaid($id) {
        return $this->store->updateById($id, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
    }
}
