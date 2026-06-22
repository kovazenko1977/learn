<?php
header('Content-Type: text/csv; charset=utf-8');
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

Auth::requireAuth();

$type = $_GET['type'] ?? 'clients';
$filename = $type . '_' . date('Y-m-d') . '.csv';

header('Content-Disposition: attachment; filename="' . $filename . '"');

// Add UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

if ($type === 'clients') {
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Status', 'Source', 'Tags', 'Created At'], ';');
    $data = Storage::read('clients');
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['email'] ?? '',
            $row['phone'] ?? '',
            $row['status'] ?? '',
            $row['source'] ?? '',
            $row['tags'] ?? '',
            $row['created_at'] ?? ''
        ], ';');
    }
} elseif ($type === 'performance') {
    fputcsv($output, ['ID', 'Name', 'Role', 'Points'], ';');
    $data = Storage::read('users');
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['role'] ?? '',
            $row['points'] ?? 0
        ], ';');
    }
}

fclose($output);
exit;
