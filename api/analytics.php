<?php
Auth::requireRole(['admin', 'director', 'marketing']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $appointments = Storage::list('appointments');
    $patients = Storage::list('patients');
    $doctors = Storage::list('doctors');
    $finance = Storage::list('finance');

    $stats = [
        'total_patients' => count($patients),
        'total_appointments' => count($appointments),
        'total_doctors' => count($doctors),
        'appointments_by_status' => [],
        'revenue' => 0
    ];

    foreach ($appointments as $a) {
        $status = $a['status'];
        $stats['appointments_by_status'][$status] = ($stats['appointments_by_status'][$status] ?? 0) + 1;
    }

    foreach ($finance as $f) {
        if ($f['type'] === 'income') {
            $stats['revenue'] += (float)$f['amount'];
        } elseif ($f['type'] === 'expense') {
            $stats['revenue'] -= (float)$f['amount'];
        }
    }

    echo json_encode($stats);
}
