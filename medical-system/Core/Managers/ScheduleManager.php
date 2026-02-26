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

        // Check Working Hours
        $workStart = strtotime($data['date'] . ' ' . ($proc['work_start'] ?? '08:00'));
        $workEnd = strtotime($data['date'] . ' ' . ($proc['work_end'] ?? '17:00'));

        if ($newStart < $workStart || $newEnd > $workEnd) {
            return ['error' => "Время вне графика работы процедуры (" . ($proc['work_start'] ?? '08:00') . " - " . ($proc['work_end'] ?? '17:00') . ")"];
        }

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
        (new LogManager())->log('Назначение процедуры', ['patient' => $data['patient_name'], 'procedure' => $data['procedure_name'], 'date' => $data['date']]);
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

    public function getByDateRange($startDate, $endDate) {
        $all = $this->getAll();
        $results = array_filter($all, function($item) use ($startDate, $endDate) {
            return isset($item['date']) && $item['date'] >= $startDate && $item['date'] <= $endDate;
        });
        // Sort by date and time
        usort($results, function($a, $b) {
            if ($a['date'] === $b['date']) {
                return $a['time'] <=> $b['time'];
            }
            return $a['date'] <=> $b['date'];
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

    public function getFreeSlots($procedureId, $cabinetId, $date) {
        $proc = $this->procedureManager->getById($procedureId);
        if (!$proc) return [];

        $duration = (int)$proc['duration'];
        $prepTime = (int)($proc['prep_time'] ?? 0);
        $totalBlock = $duration + $prepTime;

        $workStartStr = $proc['work_start'] ?? '08:00';
        $workEndStr = $proc['work_end'] ?? '17:00';

        $workStart = strtotime($date . ' ' . $workStartStr);
        $workEnd = strtotime($date . ' ' . $workEndStr);

        $occupied = $this->getOccupiedSlots($cabinetId, $date);

        $freeSlots = [];
        $currentTime = $workStart;

        // Step by 5 minutes for high precision selection, or 15 for better UI?
        // Let's use 10 minutes step for a balance.
        while ($currentTime + ($duration * 60) <= $workEnd) {
            $slotStart = $currentTime;
            $slotEnd = $slotStart + ($totalBlock * 60);

            $isOccupied = false;
            foreach ($occupied as $occ) {
                $occStart = strtotime($date . ' ' . $occ['start']);
                $occEnd = strtotime($date . ' ' . $occ['end']);

                if (($slotStart >= $occStart && $slotStart < $occEnd) ||
                    ($slotEnd > $occStart && $slotEnd <= $occEnd) ||
                    ($slotStart <= $occStart && $slotEnd >= $occEnd)) {
                    $isOccupied = true;
                    // If occupied, we jump to the end of this occupied block to save cycles
                    $currentTime = $occEnd;
                    break;
                }
            }

            if (!$isOccupied) {
                $freeSlots[] = date('H:i', $slotStart);
                $currentTime += 10 * 60; // 10 min step
            }
        }

        return $freeSlots;
    }

    public function getEarliestFreeSlot($procedureId, $cabinetId, $date) {
        $freeSlots = $this->getFreeSlots($procedureId, $cabinetId, $date);

        if (empty($freeSlots)) return null;

        // If it's today, we should probably filter out past times
        if ($date === date('Y-m-d')) {
            $now = date('H:i');
            foreach ($freeSlots as $slot) {
                if ($slot >= $now) return $slot;
            }
        }

        return $freeSlots[0];
    }

    public function bulkMarkPaid($ids) {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->markPaid($id)) {
                $count++;
            }
        }
        return $count;
    }

    public function bulkAssign($data, $startDate, $endDate, $frequency = 'daily') {
        $results = [];
        $current = strtotime($startDate);
        $last = strtotime($endDate);

        while ($current <= $last) {
            $dateStr = date('Y-m-d', $current);
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
        $res = $this->store->updateById($id, [
            'attended' => true,
            'attended_at' => date('Y-m-d H:i:s'),
            'performed_by' => $nurseName
        ]);
        if ($res) {
            (new LogManager())->log('Процедура выполнена', ['appointment_id' => $id, 'nurse' => $nurseName]);
        }
        return $res;
    }

    public function cancel($id) {
        $res = $this->store->updateById($id, ['status' => 'cancelled']);
        if ($res) {
            (new LogManager())->log('Отмена назначения', ['appointment_id' => $id]);
        }
        return $res;
    }

    public function delete($id) {
        $res = $this->store->deleteById($id);
        if ($res) {
            (new LogManager())->log('Удаление назначения', ['appointment_id' => $id]);
        }
        return $res;
    }

    public function update($id, $data) {
        $res = $this->store->updateById($id, $data);
        if ($res) {
            (new LogManager())->log('Корректировка назначения', ['appointment_id' => $id]);
        }
        return $res;
    }

    public function markPaid($id) {
        $res = $this->store->updateById($id, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
        if ($res) {
            (new LogManager())->log('Оплата процедуры', ['appointment_id' => $id]);
        }
        return $res;
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

    public function deleteOverdueUnpaid() {
        $apps = $this->store->getAll();
        $now = time();
        $newApps = [];
        $deletedCount = 0;

        foreach ($apps as $app) {
            $isOverdue = false;
            if (isset($app['status']) && $app['status'] === 'unpaid' && empty($app['attended'])) {
                $appTime = strtotime($app['date'] . ' ' . $app['time']);
                if ($appTime !== false && $appTime < ($now - 7200)) { // 2 hours after scheduled time
                    $isOverdue = true;
                }
            }

            if ($isOverdue) {
                $deletedCount++;
            } else {
                $newApps[] = $app;
            }
        }

        if ($deletedCount > 0) {
            $this->store->save($newApps);
            (new LogManager())->log('Массовое удаление просроченных неоплаченных процедур', ['count' => $deletedCount]);
        }

        return $deletedCount;
    }
}
