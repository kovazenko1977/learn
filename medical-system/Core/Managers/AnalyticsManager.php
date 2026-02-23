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

    public function getAgeStats() {
        $patients = $this->patientsStore->getAll();
        $groups = ['0-18' => 0, '19-35' => 0, '36-60' => 0, '60+' => 0];
        $now = new \DateTime();

        foreach ($patients as $p) {
            $birth = new \DateTime($p['birth_date']);
            $age = $now->diff($birth)->y;

            if ($age <= 18) $groups['0-18']++;
            elseif ($age <= 35) $groups['19-35']++;
            elseif ($age <= 60) $groups['36-60']++;
            else $groups['60+']++;
        }
        return $groups;
    }

    public function getMkbStats() {
        $patients = $this->patientsStore->getAll();
        $stats = [];
        foreach ($patients as $p) {
            if (isset($p['history']) && is_array($p['history'])) {
                foreach ($p['history'] as $entry) {
                    $code = $entry['diagnosis_code'] ?? 'Unknown';
                    $text = $entry['diagnosis_text'] ?? '';
                    if (!isset($stats[$code])) $stats[$code] = ['count' => 0, 'text' => $text];
                    $stats[$code]['count']++;
                }
            }
        }
        arsort($stats);
        return array_slice($stats, 0, 15);
    }

    public function getStayDurationStats() {
        $patients = $this->patientsStore->getAll();
        $appointments = $this->appointmentsStore->getAll();

        $durations = [];
        foreach ($patients as $p) {
            $pid = $p['id'];
            $pApps = array_filter($appointments, function($a) use ($pid) { return $a['patient_id'] === $pid; });
            if (empty($pApps)) continue;

            $dates = array_map(function($a) { return strtotime($a['date']); }, $pApps);
            $minDate = min($dates);
            $maxDate = max($dates);

            $days = ceil(($maxDate - $minDate) / (60 * 60 * 24)) + 1;
            if (!isset($durations[$days])) $durations[$days] = 0;
            $durations[$days]++;
        }
        ksort($durations);
        return $durations;
    }

    public function getCabinetTimeLoad($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $procStore = new JsonStore('procedures_directory');
        $procedures = [];
        foreach ($procStore->getAll() as $pr) { $procedures[$pr['id']] = $pr; }

        $load = []; // cabinet => minutes
        foreach ($appointments as $app) {
            $cab = $app['cabinet_id'];
            $procId = $app['procedure_id'];
            $duration = $procedures[$procId]['duration'] ?? 20;

            if (!isset($load[$cab])) $load[$cab] = 0;
            $load[$cab] += $duration;
        }
        arsort($load);
        return $load;
    }

    public function getDailyRevenue($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $daily = [];
        foreach ($appointments as $app) {
            if ($app['status'] === 'paid') {
                $date = $app['date'];
                if (!isset($daily[$date])) $daily[$date] = 0;
                $daily[$date] += (float)($app['price'] ?? 0);
            }
        }
        ksort($daily);
        return $daily;
    }

    public function getDailyRegistrations($startDate = null, $endDate = null) {
        $patients = $this->patientsStore->getAll();
        $daily = [];
        foreach ($patients as $p) {
            $date = date('Y-m-d', strtotime($p['created_at']));
            if ($startDate && $date < $startDate) continue;
            if ($endDate && $date > $endDate) continue;
            if (!isset($daily[$date])) $daily[$date] = 0;
            $daily[$date]++;
        }
        ksort($daily);
        return $daily;
    }

    public function getProcedurePopularity($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $popularity = [];
        foreach ($appointments as $app) {
            $name = $app['procedure_name'];
            if (!isset($popularity[$name])) $popularity[$name] = 0;
            $popularity[$name]++;
        }
        arsort($popularity);
        return array_slice($popularity, 0, 10);
    }

    public function getDoctorPerformance($startDate = null, $endDate = null) {
        $appointments = $this->getFilteredAppointments($startDate, $endDate);
        $perf = [];
        foreach ($appointments as $app) {
            $doc = $app['doctor'] ?? 'Не указан';
            if (!isset($perf[$doc])) {
                $perf[$doc] = ['count' => 0, 'revenue' => 0];
            }
            $perf[$doc]['count']++;
            if ($app['status'] === 'paid') {
                $perf[$doc]['revenue'] += (float)($app['price'] ?? 0);
            }
        }
        return $perf;
    }
}
