<?php require_once "auth.php";

if (!isset($_SESSION['admin_logged_in'])) {
    exit('Access denied');
}

require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$bookings = $store->findAll('bookings');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bookings_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['ID', 'Check-in', 'Check-out', 'Room ID', 'Phone', 'Status', 'Total Price', 'Created At']);

if (is_array($bookings)) {
    foreach ($bookings as $b) {
        if (!is_array($b)) continue;
        fputcsv($output, [
            $b['id'] ?? '',
            $b['check_in'] ?? '',
            $b['check_out'] ?? '',
            $b['room_id'] ?? '',
            $b['phone'] ?? '',
            $b['status'] ?? '',
            $b['total_price'] ?? '',
            $b['created_at'] ?? ''
        ]);
    }
}

fclose($output);
exit;
