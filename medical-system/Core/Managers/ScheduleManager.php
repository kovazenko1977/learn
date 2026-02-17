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
        // Status should be already set in $data, but provide a default
        if (!isset($data['status'])) {
            $data['status'] = 'free';
        }
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
            return isset($item['patient_id']) && $item['patient_id'] == $patientId;
        });
        return array_values($results);
    }

    public function getByDate($date) {
        $all = $this->getAll();
        $results = array_filter($all, function($item) use ($date) {
            return isset($item['date']) && $item['date'] == $date;
        });
        return array_values($results);
    }

    public function getByCabinet($cabinetId, $date = null) {
        $all = $this->getAll();
        $results = array_filter($all, function($item) use ($cabinetId, $date) {
            $match = isset($item['cabinet_id']) && $item['cabinet_id'] == $cabinetId;
            if ($date) {
                $match = $match && isset($item['date']) && $item['date'] == $date;
            }
            return $match;
        });
        return array_values($results);
    }

    public function getOccupiedSlots($cabinetId, $date) {
        $appointments = $this->getByCabinet($cabinetId, $date);
        $slots = [];
        foreach ($appointments as $app) {
            $proc = $this->procedureManager->getById($app['procedure_id']);
            $duration = $proc ? (int)$proc['duration'] : 20;
            $prep = $proc ? (int)($proc['prep_time'] ?? 0) : 5;
            $total = $duration + $prep;

            $start = $app['time'];
            $end = date('H:i', strtotime($date . ' ' . $start) + ($total * 60));

            $slots[] = ['start' => $start, 'end' => $end, 'procedure' => $app['procedure_name']];
        }
        return $slots;
    }

    public function bulkAssign($data, $startDate, $endDate, $frequency = 'daily') {
        $results = [];
        $current = strtotime($startDate);
        $last = strtotime($endDate);

        while ($current <= $last) {
            $dateStr = date('d-m-Y', $current);
            $instanceData = $data;
            $instanceData['date'] = $dateStr;

            $res = $this->assign($instanceData);
            $results[$dateStr] = $res;

            if ($frequency === 'daily') {
                $current = strtotime('+1 day', $current);
            } elseif ($frequency === 'every_other') {
                $current = strtotime('+2 days', $current);
            } else {
                break;
            }
        }
        return $results;
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

    public function autoCancelUnpaid() {
        $apps = $this->store->getAll();
        $changed = false;
        $now = time();

        foreach ($apps as &$app) {
            // Cancel unpaid paid procedures if they are in the past
            if (isset($app['type']) && $app['type'] === 'paid' && isset($app['status']) && $app['status'] === 'unpaid' && empty($app['attended'])) {
                $appTime = strtotime($app['date'] . ' ' . $app['time']);
                if ($appTime !== false && $appTime < ($now - 7200)) { // 2 hours after scheduled time
                    $app['status'] = 'cancelled';
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $this->store->save($apps);
        }
    }
}
