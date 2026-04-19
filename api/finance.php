<?php
Auth::requireRole(['admin', 'director']);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET') {
    if ($action === 'export_csv') {
        Auth::requireRole(['admin', 'director']);
        $ops = Storage::list('finance');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=finance.csv');
        $output = fopen('php://output', 'w');
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        fputcsv($output, ['ID', 'Дата', 'Тип', 'Сумма', 'Пациент ID', 'Комментарий'], ';');
        foreach ($ops as $f) {
            fputcsv($output, [$f['id'], $f['created_at'] ?? ($f['date'] ?? ''), $f['type'], $f['amount'], $f['patient_id'] ?? '-', $f['comment'] ?? ''], ';');
        }
        fclose($output);
        exit;
    }

    if ($action === 'get_active_shift') {
        $shifts = Storage::list('shifts');
        foreach ($shifts as $s) {
            if ($s['status'] === 'open') {
                echo json_encode($s);
                exit;
            }
        }
        echo json_encode(null);
    } elseif ($action === 'shifts_history') {
        $shifts = Storage::list('shifts');
        usort($shifts, function($a, $b) { return strcmp($b['opened_at'], $a['opened_at']); });
        echo json_encode($shifts);
    } else {
        $transactions = Storage::list('finance');

        $today = date('Y-m-d');
        $month = date('Y-m');
        $summary = ['today_income' => 0, 'month_income' => 0, 'today_expense' => 0, 'month_expense' => 0];

        foreach($transactions as $t) {
            $tDate = substr($t['created_at'] ?? $t['date'], 0, 10);
            $tMonth = substr($tDate, 0, 7);
            $amount = (float)$t['amount'];

            if ($tDate === $today) {
                if ($t['type'] === 'income') $summary['today_income'] += $amount;
                else $summary['today_expense'] += $amount;
            }
            if ($tMonth === $month) {
                if ($t['type'] === 'income') $summary['month_income'] += $amount;
                else $summary['month_expense'] += $amount;
            }
        }

        echo json_encode([
            'transactions' => array_values($transactions),
            'summary' => $summary
        ]);
    }
} elseif ($method === 'POST') {
    if ($action === 'open_shift') {
        $shifts = Storage::list('shifts');
        foreach ($shifts as $s) {
            if ($s['status'] === 'open') {
                echo json_encode(['error' => 'Shift already open']);
                exit;
            }
        }
        $id = uniqid();
        $shift = [
            'id' => $id,
            'opened_at' => date('c'),
            'opened_by' => $_SESSION['user_id'],
            'status' => 'open',
            'total_income' => 0,
            'total_expense' => 0
        ];
        Storage::write('shifts', $id, $shift);
        echo json_encode(['success' => true, 'id' => $id]);
    } elseif ($action === 'close_shift') {
        $shifts = Storage::list('shifts');
        $activeShift = null;
        foreach ($shifts as $s) {
            if ($s['status'] === 'open') {
                $activeShift = $s;
                break;
            }
        }
        if (!$activeShift) {
            echo json_encode(['error' => 'No open shift found']);
            exit;
        }

        $transactions = Storage::list('finance');
        $income = 0;
        $expense = 0;
        foreach ($transactions as $t) {
            if ($t['created_at'] >= $activeShift['opened_at']) {
                if ($t['type'] === 'income') $income += (float)$t['amount'];
                if ($t['type'] === 'expense') $expense += (float)$t['amount'];
            }
        }

        $activeShift['closed_at'] = date('c');
        $activeShift['closed_by'] = $_SESSION['user_id'];
        $activeShift['status'] = 'closed';
        $activeShift['total_income'] = $income;
        $activeShift['total_expense'] = $expense;

        Storage::write('shifts', $activeShift['id'], $activeShift);
        echo json_encode(['success' => true]);
    } else {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $input = Security::sanitize($input);
        $id = uniqid();
        $input['id'] = $id;
        $input['created_at'] = date('c');
        $input['created_by'] = $_SESSION['user_id'];
        Storage::write('finance', $id, $input);
        echo json_encode(['success' => true, 'id' => $id]);
    }
}
