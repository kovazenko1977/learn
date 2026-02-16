<?php

namespace Medical\Core\Managers;

use Medical\Core\JsonStore;

class AnalyticsManager {
    public function getSummary() {
        $appointmentsStore = new JsonStore('appointments');
        $patientsStore = new JsonStore('patients');

        $appointments = $appointmentsStore->getAll();
        $patients = $patientsStore->getAll();

        $totalRevenue = 0;
        foreach ($appointments as $app) {
            if ($app['status'] === 'paid' && isset($app['price'])) {
                $totalRevenue += (float)$app['price'];
            }
        }

        return [
            'total_patients' => count($patients),
            'total_appointments' => count($appointments),
            'total_revenue' => $totalRevenue,
            'attended_count' => count(array_filter($appointments, function($a) { return !empty($a['attended']); })),
        ];
    }

    public function getWorkloadByCabinet() {
        $appointmentsStore = new JsonStore('appointments');
        $appointments = $appointmentsStore->getAll();

        $workload = [];
        foreach ($appointments as $app) {
            $cab = $app['cabinet_id'] ?? 'Unknown';
            if (!isset($workload[$cab])) $workload[$cab] = 0;
            $workload[$cab]++;
        }
        return $workload;
    }
}
