<?php
namespace Medical\Core;

class ProceduresManager {
    private $store;
    private $proceduresTable = 'procedures_list';
    private $assignmentsTable = 'procedure_assignments';

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    // Dictionary Management
    public function getProcedures() {
        return $this->store->findAll($this->proceduresTable);
    }

    public function saveProcedure($data) {
        return $this->store->save($this->proceduresTable, $data);
    }

    public function deleteProcedure($id) {
        return $this->store->delete($this->proceduresTable, $id);
    }

    // Assignments
    public function assignProcedure($data) {
        // data: patient_name, phone, procedure_id, date, time, status (pending/paid/completed), is_paid (bool)
        if (!isset($data['status'])) $data['status'] = 'pending';
        if (!isset($data['is_paid'])) {
            $proc = $this->store->findOne($this->proceduresTable, $data['procedure_id']);
            $data['is_paid'] = (isset($proc['price']) && (float)($proc['price'] ?? 0) <= 0);
        }
        return $this->store->save($this->assignmentsTable, $data);
    }

    public function batchAssign($patientData, $procedures) {
        // patientData: [patient_name, phone]
        // procedures: [[procedure_id, date, time], ...]
        foreach ($procedures as $proc) {
            $this->assignProcedure(array_merge($patientData, $proc));
        }
        return true;
    }

    public function getAssignments($filters = []) {
        $all = $this->store->findAll($this->assignmentsTable);
        if (empty($filters)) return $all;

        return array_values(array_filter($all, function($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if ($key === 'date' && $item['date'] !== $value) return false;
                if ($key === 'status' && $item['status'] !== $value) return false;
                if ($key === 'patient' && stripos($item['patient_name'], $value) === false && stripos($item['phone'], $value) === false) return false;
            }
            return true;
        }));
    }

    public function updateAssignment($id, $data) {
        $existing = $this->store->findOne($this->assignmentsTable, $id);
        if (!$existing) return false;
        $newData = array_merge($existing, $data);
        return $this->store->save($this->assignmentsTable, $newData);
    }

    // Slot Calculation
    public function getAvailableSlots($date, $procedureId) {
        $procedure = $this->store->findOne($this->proceduresTable, $procedureId);
        if (!$procedure) return [];

        $duration = (int)($procedure['duration'] ?? 30);
        $break = (int)($procedure['break_time'] ?? 5);
        $totalSlot = $duration + $break;

        $start = 8 * 60; // 08:00
        $end = 20 * 60;   // 20:00

        $existing = $this->getAssignments(['date' => $date]);
        // Filter by same procedure or same room (in this simple app, we assume procedures share resources if they overlap)
        // For simplicity, let's say a nurse can handle one patient at a time if we don't have multiple rooms/nurses defined.
        // But usually, it's per procedure type (machine/office).

        $slots = [];
        for ($t = $start; $t + $duration <= $end; $t += $totalSlot) {
            $slotTime = sprintf('%02d:%02d', floor($t / 60), $t % 60);
            $isOccupied = false;
            foreach ($existing as $assign) {
                if ($assign['time'] === $slotTime && $assign['procedure_id'] == $procedureId) {
                    $isOccupied = true;
                    break;
                }
            }
            if (!$isOccupied) {
                $slots[] = $slotTime;
            }
        }
        return $slots;
    }

    public function getAnalytics($startDate, $endDate) {
        $all = $this->store->findAll($this->assignmentsTable);
        $stats = [
            'total' => 0,
            'completed' => 0,
            'paid_revenue' => 0.0,
            'by_procedure' => []
        ];

        $procList = [];
        foreach ($this->getProcedures() as $p) {
            $procList[$p['id']] = $p;
        }

        foreach ($all as $item) {
            if ($item['date'] >= $startDate && $item['date'] <= $endDate) {
                $stats['total']++;
                if ($item['status'] === 'completed') $stats['completed']++;

                $pId = $item['procedure_id'];
                if (!isset($stats['by_procedure'][$pId])) {
                    $stats['by_procedure'][$pId] = [
                        'name' => $procList[$pId]['name'] ?? 'Unknown',
                        'count' => 0,
                        'revenue' => 0.0
                    ];
                }
                $stats['by_procedure'][$pId]['count']++;

                if ($item['is_paid']) {
                    $price = (float)($procList[$pId]['price'] ?? 0);
                    $stats['paid_revenue'] += $price;
                    $stats['by_procedure'][$pId]['revenue'] += $price;
                }
            }
        }
        return $stats;
    }
}
