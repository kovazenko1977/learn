<?php
Auth::requireRole(['admin', 'director', 'marketing']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $appointments = Storage::list('appointments');
    $patients = Storage::list('patients');
    $doctors = Storage::list('doctors');
    $finance = ($_SESSION['role'] === 'marketing') ? [] : Storage::list('finance');

    $stats = [
        'total_patients' => count($patients),
        'total_appointments' => count($appointments),
        'total_doctors' => count($doctors),
        'appointments_by_status' => [],
        'revenue' => ($_SESSION['role'] === 'marketing') ? null : 0,
        'sources' => [],
        'tags' => []
    ];

    foreach ($appointments as $a) {
        $status = $a['status'];
        $stats['appointments_by_status'][$status] = ($stats['appointments_by_status'][$status] ?? 0) + 1;
    }

    if ($_SESSION['role'] !== 'marketing') {
        foreach ($finance as $f) {
            if ($f['type'] === 'income') {
                $stats['revenue'] += (float)$f['amount'];
            } elseif ($f['type'] === 'expense') {
                $stats['revenue'] -= (float)$f['amount'];
            }
        }
    }

    foreach ($patients as $p) {
        if (!empty($p['source_id'])) {
            $stats['sources'][$p['source_id']] = ($stats['sources'][$p['source_id']] ?? 0) + 1;
        }
        if (!empty($p['tags']) && is_array($p['tags'])) {
            foreach ($p['tags'] as $tagId) {
                $stats['tags'][$tagId] = ($stats['tags'][$tagId] ?? 0) + 1;
            }
        }
    }

    // Marketing Source Stats
    $sources = Storage::list('sources');
    $marketing = [];
    foreach ($sources as $s) {
        $marketing[$s['id']] = ['name' => $s['name'], 'count' => 0, 'appointments' => 0];
    }
    foreach ($patients as $p) {
        if (!empty($p['source_id']) && isset($marketing[$p['source_id']])) {
            $marketing[$p['source_id']]['count']++;
        }
    }
    foreach ($appointments as $a) {
        if (!empty($a['source_id']) && isset($marketing[$a['source_id']])) {
             $marketing[$a['source_id']]['appointments']++;
        }
    }
    $stats['marketing'] = array_values($marketing);

    // Salary Reports (30% commission example)
    $doctors = Storage::list('doctors');
    $salary = [];
    foreach ($doctors as $d) {
        $earned = 0;
        foreach ($appointments as $a) {
            if ($a['doctor_id'] === $d['id'] && $a['status'] === 'completed') {
                $service = Storage::read('services', $a['service_id']);
                if ($service) $earned += ($service['base_price'] * 0.3);
            }
        }
        $salary[] = [
            'doctor_name' => $d['full_name'],
            'earned' => $earned
        ];
    }
    $stats['salary'] = $salary;

    echo json_encode($stats);
}
