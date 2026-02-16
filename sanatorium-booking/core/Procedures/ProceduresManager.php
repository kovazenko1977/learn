<?php

namespace Sanatorium\Core\Procedures;

use Sanatorium\Core\Database\JsonStore;

class ProceduresManager {
    private JsonStore $store;
    private string $table = 'procedure_assignments';

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function assignProcedure(array $data): int {
        if (!isset($data['status'])) {
            $data['status'] = 'assigned';
        }
        return $this->store->save($this->table, $data);
    }

    public function getAssignment(int $id): ?array {
        return $this->store->findOne($this->table, $id);
    }

    public function getAllAssignments(): array {
        return $this->store->findAll($this->table);
    }

    public function deleteAssignment(int $id): bool {
        return $this->store->delete($this->table, $id);
    }

    public function getAvailableSlots(int $procedureId, string $date): array {
        $procedure = $this->store->findOne('procedures', $procedureId);
        if (!$procedure) return [];

        $duration = (int)($procedure['duration'] ?? 20);
        $breakTime = (int)($procedure['break_time'] ?? 0);
        $totalMinutes = $duration + $breakTime;
        if ($totalMinutes <= 0) $totalMinutes = 30;

        $assignments = $this->getAllAssignments();
        $busySlots = [];
        foreach ($assignments as $a) {
            if (is_array($a) && (int)($a['procedure_id'] ?? 0) === $procedureId && ($a['date'] ?? '') === $date && ($a['status'] ?? '') !== 'cancelled') {
                $startTime = strtotime($date . ' ' . ($a['time'] ?? '00:00'));
                $busySlots[] = [
                    'start' => $startTime,
                    'end' => $startTime + $totalMinutes * 60
                ];
            }
        }

        $availableSlots = [];
        $startOfDay = strtotime($date . ' 08:00');
        $endOfDay = strtotime($date . ' 20:00');

        $currentTime = $startOfDay;
        while ($currentTime + $totalMinutes * 60 <= $endOfDay) {
            $slotStart = $currentTime;
            $slotEnd = $currentTime + $totalMinutes * 60;

            $isBusy = false;
            foreach ($busySlots as $busy) {
                if ($slotStart < $busy['end'] && $slotEnd > $busy['start']) {
                    $isBusy = true;
                    break;
                }
            }

            if (!$isBusy) {
                $availableSlots[] = date('H:i', $currentTime);
            }

            // Increment by 10 minutes to allow flexible scheduling, or by $totalMinutes
            $currentTime += 10 * 60;
        }

        return $availableSlots;
    }

    public function updateAssignmentStatus(int $id, string $status): bool {
        $assignment = $this->getAssignment($id);
        if (!$assignment) return false;
        $assignment['status'] = $status;
        return (bool)$this->store->save($this->table, $assignment);
    }

    public function getPatientAssignments(int $guestId): array {
        $all = $this->getAllAssignments();
        $patientAssignments = [];
        foreach ($all as $a) {
            if (is_array($a) && (int)($a['guest_id'] ?? 0) === $guestId) {
                $patientAssignments[] = $a;
            }
        }
        return $patientAssignments;
    }

    public function getAssignmentsByDate(string $date): array {
        $all = $this->getAllAssignments();
        $dateAssignments = [];
        foreach ($all as $a) {
            if (is_array($a) && ($a['date'] ?? '') === $date) {
                $dateAssignments[] = $a;
            }
        }
        return $dateAssignments;
    }
}
