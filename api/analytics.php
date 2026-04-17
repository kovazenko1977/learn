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

    echo json_encode($stats);
}
