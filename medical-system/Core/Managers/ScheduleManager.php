<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class ScheduleManager {
    private $store;
    private $procedureManager;

    public function __construct() {
        $this->store = new JsonStore('appointments');
        $this->procedureManager = new ProcedureManager();
    }

    public function getAll() {
        return $this->store->getAll();
    }

    public function getById($id) {
        return $this->store->findById($id);
    }

    public function assign($data) {
        $proc = $this->procedureManager->getById($data['procedure_id']);
        if (!$proc) return ['error' => 'Процедура не найдена'];

        $duration = (int)$proc['duration'];
        $prepTime = (int)($proc['prep_time'] ?? 0);
        $totalBlock = $duration + $prepTime;

        $newStart = strtotime($data['date'] . ' ' . $data['time']);
        $newEnd = $newStart + ($totalBlock * 60);

        // Collision detection
        $existing = $this->getByCabinet($data['cabinet_id'], $data['date']);
        foreach ($existing as $app) {
            $eProc = $this->procedureManager->getById($app['procedure_id']);
            $eDuration = $eProc ? (int)$eProc['duration'] : 20;
            $ePrep = $eProc ? (int)($eProc['prep_time'] ?? 0) : 5;
            $eTotal = $eDuration + $ePrep;

            $eStart = strtotime($app['date'] . ' ' . $app['time']);
            $eEnd = $eStart + ($eTotal * 60);

            // Check overlap
            if (($newStart >= $eStart && $newStart < $eEnd) || ($newEnd > $eStart && $newEnd <= $eEnd) || ($newStart <= $eStart && $newEnd >= $eEnd)) {
                return ['error' => "Это время занято процедурой '" . ($app['procedure_name'] ?? '...') . "' до " . date('H:i', $eEnd)];
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
        $results = array_filter($all, function($item) use ($patientId) {
            return $item['patient_id'] == $patientId;
        });
        return array_values($results);
    }

    public function getByDate($date) {
        $all = $this->getAll();
        $results = array_filter($all, function($item) use ($date) {
            return $item['date'] == $date;
        });
        return array_values($results);
    }

    public function getByCabinet($cabinetId, $date = null) {
        $all = $this->getAll();
        $results = array_filter($all, function($item) use ($cabinetId, $date) {
            $match = $item['cabinet_id'] == $cabinetId;
            if ($date) {
                $match = $match && $item['date'] == $date;
            }
            return $match;
        });
        return array_values($results);
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
