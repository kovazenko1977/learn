<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Storage.php';

$user = Auth::authenticate();

$tasks = Storage::read('tasks');

$format = $_GET['format'] ?? 'csv';

if ($format === 'print') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Tasks Export</title>
        <style>
            table { width: 100%; border-collapse: collapse; font-family: sans-serif; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <h1>Service Requests Report - <?php echo date('Y-m-d'); ?></h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Executor</th><th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $t): ?>
                <tr>
                    <td>#<?php echo substr($t['id'], 0, 6); ?></td>
                    <td><?php echo htmlspecialchars($t['title']); ?></td>
                    <td><?php echo htmlspecialchars($t['category']); ?></td>
                    <td><?php echo htmlspecialchars($t['priority']); ?></td>
                    <td><?php echo htmlspecialchars($t['status']); ?></td>
                    <td><?php echo htmlspecialchars($t['executor_name'] ?? '—'); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($t['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="no-print" style="margin-top: 20px;">
            <button onclick="window.print()">Print to PDF</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tasks_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Title', 'Category', 'Priority', 'Status', 'Creator', 'Executor', 'Created At', 'Deadline']);

foreach ($tasks as $t) {
    fputcsv($output, [
        $t['id'],
        $t['title'],
        $t['category'],
        $t['priority'],
        $t['status'],
        $t['creator_name'],
        $t['executor_name'],
        $t['created_at'],
        $t['deadline']
    ]);
}

fclose($output);
