<?php
Auth::requireRole(['admin', 'senior_admin', 'manager', 'doctor', 'patient']);

$method = $_SERVER['REQUEST_METHOD'];


if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'export_csv') {
        Auth::requireRole(['admin', 'director']);
        $patients = Storage::list('patients');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=patients.csv');
        $output = fopen('php://output', 'w');
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        fputcsv($output, ['ID', 'ФИО', 'Телефон', 'Email', 'Дата рождения', 'Создан'], ';');
        foreach ($patients as $p) {
            fputcsv($output, [$p['id'], $p['full_name'], $p['phone'], $p['email'], $p['birth_date'] ?? '', $p['created_at'] ?? ''], ';');
        }
        fclose($output);
        exit;
    }

    $id = isset($_GET['id']) ? $_GET['id'] : null;

    // Patient can only see their own record
    if ($_SESSION['role'] === 'patient' && $id !== $_SESSION['user_id']) {
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    if ($id) {
        $patient = Storage::read('patients', $id);
        if ($patient) {
            // Calculate balance and timeline
            $allFinance = Storage::list('finance');
            $allAppointments = Storage::list('appointments');
            $allServices = Storage::list('services');

            $payments = 0;
            foreach ($allFinance as $f) {
                if ($f['type'] === 'income' && isset($f['patient_id']) && $f['patient_id'] === $id) {
                    $payments += $f['amount'];
                }
            }

            $costs = 0;
            $timeline = [];
            foreach ($allAppointments as $app) {
                if ($app['patient_id'] === $id) {
                    $service = null;
                    foreach ($allServices as $s) {
                        if ($s['id'] === $app['service_id']) {
                            $service = $s;
                            break;
                        }
                    }
                    if ($app['status'] === 'completed' && $service) {
                        $costs += $service['base_price'];
                    }
                    $timeline[] = [
                        'date' => $app['date'] . ' ' . $app['time_start'],
                        'type' => 'appointment',
                        'title' => 'Прием: ' . ($service ? $service['name'] : 'Услуга не указана'),
                        'status' => $app['status']
                    ];
                }
            }

            foreach ($allFinance as $f) {
                if (isset($f['patient_id']) && $f['patient_id'] === $id) {
                     $timeline[] = [
                        'date' => $f['created_at'] ?? $f['date'],
                        'type' => 'finance',
                        'title' => ($f['type'] === 'income' ? 'Оплата' : 'Расход') . ': ' . $f['amount'] . ' ₽',
                        'comment' => $f['comment'] ?? ''
                    ];
                }
            }

            $docs = Storage::list('documents');
            foreach ($docs as $d) {
                if ($d['patient_id'] === $id) {
                    $timeline[] = [
                        'date' => $d['created_at'],
                        'type' => 'document',
                        'title' => 'Документ: ' . ($d['title'] ?: $d['type'])
                    ];
                }
            }

            usort($timeline, function($a, $b) { return strcmp($b['date'], $a['date']); });

            $patient['balance'] = $payments - $costs;
            $patient['timeline'] = array_slice($timeline, 0, 50);

            echo json_encode($patient);
        } else {
            echo json_encode(['error' => 'Patient not found']);
        }
    } else {
        if ($_SESSION['role'] === 'patient') {
            echo json_encode([]);
            exit;
        }
        $patients = Storage::list('patients');
        echo json_encode(array_values($patients));
    }
} elseif ($method === 'POST') {
    if ($_SESSION['role'] === 'patient') {
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $input = Security::sanitize($input);

    $id = isset($input['id']) ? $input['id'] : uniqid();
    $existing = Storage::read('patients', $id);

    // Duplicate check (by phone or email)
    $all = Storage::list('patients');
    foreach ($all as $p) {
        if ($p['id'] === $id) continue;
        if ((!empty($input['phone']) && $p['phone'] === $input['phone']) ||
            (!empty($input['email']) && $p['email'] === $input['email'])) {
            echo json_encode(['error' => 'Patient with this phone or email already exists']);
            exit;
        }
    }

    if ($existing) {
        Storage::saveVersion('patients', $id, $existing);
    }

    $input['id'] = $id;
    $input['updated_at'] = date('c');
    $input['updated_by'] = $_SESSION['user_id'];

    if (!$existing) {
        $input['created_at'] = date('c');
        $input['created_by'] = $_SESSION['user_id'];
    }

    Storage::write('patients', $id, $input);
    Logger::log("Patient saved: " . $input['full_name'], "info", "system.log");
    echo json_encode(['success' => true, 'id' => $id]);
} elseif ($method === 'DELETE') {
    Auth::requireRole(['admin']);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        Storage::delete('patients', $id);
        Logger::log("Patient deleted: $id", "warning", "system.log");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'ID required']);
    }
}
