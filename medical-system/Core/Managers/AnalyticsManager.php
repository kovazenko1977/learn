<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class AnalyticsManager {
    private $appointmentsStore;
    private $patientsStore;

    public function __construct() {
        $this->appointmentsStore = new JsonStore('appointments');
        $this->patientsStore = new JsonStore('patients');
    }

    public function getSummary($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $allPatients = $this->patientsStore->getAll();

        $totalRevenue = 0;
        $attendedCount = 0;
        foreach ($appointments as $app) {
            if ($app['status'] === 'paid' && isset($app['price'])) {
                $totalRevenue += (float)$app['price'];
            }
            if (!empty($app['attended'])) {
                $attendedCount++;
            }
        }

        return [
            'total_patients' => count($allPatients),
            'total_appointments' => count($appointments),
            'total_revenue' => $totalRevenue,
            'attended_count' => $attendedCount,
        ];
    }

    public function getFilteredAppointments($startDate = null, $endDate = null) {
        $all = $this->appointmentsStore->getAll();
        if (!$startDate && !$endDate) return $all;

        return array_filter($all, function($app) use ($startDate, $endDate) {
            $appTs = strtotime($app['date']);
            if ($startDate && $appTs < strtotime($startDate)) return false;
            if ($endDate && $appTs > strtotime($endDate)) return false;
            return true;
        });
    }

    public function getWorkloadByCabinet($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);

        $workload = [];
        foreach ($appointments as $app) {
            $cab = $app['cabinet_id'] ?? 'Unknown';
            if (!isset($workload[$cab])) $workload[$cab] = 0;
            $workload[$cab]++;
        }
        arsort($workload);
        return $workload;
    }

    public function getProcedureStats($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $stats = [];

        foreach ($appointments as $app) {
            $pid = $app['procedure_id'];
            if (!isset($stats[$pid])) {
                $stats[$pid] = [
                    'name' => $app['procedure_name'],
                    'total_records' => 0,
                    'attended' => 0,
                    'revenue' => 0,
                    'unpaid' => 0
                ];
            }
            $stats[$pid]['total_records']++;
            if (!empty($app['attended'])) $stats[$pid]['attended']++;
            if ($app['status'] === 'paid') $stats[$pid]['revenue'] += (float)($app['price'] ?? 0);
            if ($app['status'] === 'unpaid') $stats[$pid]['unpaid']++;
        }
        return $stats;
    }

    public function getPatientDetails($patientId) {
        $all = $this->appointmentsStore->getAll();
        $patientApps = array_filter($all, function($app) use ($patientId) {
            return $app['patient_id'] === $patientId;
        });

        // Sort by date/time
        usort($patientApps, function($a, $b) {
            return strtotime($b['date'] . ' ' . $b['time']) <=> strtotime($a['date'] . ' ' . $a['time']);
        });

        return array_values($patientApps);
    }

    public function getDoctorLoad($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $load = [];
        foreach ($appointments as $app) {
            $doc = $app['doctor'] ?? 'Не указан';
            if (!isset($load[$doc])) $load[$doc] = 0;
            $load[$doc]++;
        }
        arsort($load);
        return $load;
    }
}
