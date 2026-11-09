<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Database\JsonStore;
use App\Models\Section;

Auth::requireAuth();

$store = new JsonStore(__DIR__ . '/../data/subscribers.json');
$subs = $store->getAll();
$sections = Section::all();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=subscribers_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Email', 'Section', 'Date']);

foreach ($subs as $s) {
    $sec = array_filter($sections, fn($sec) => $sec['id'] === $s['section_id']);
    $secName = !empty($sec) ? reset($sec)['name'] : 'Unknown';
    fputcsv($output, [$s['email'], $secName, $s['date']]);
}

fclose($output);
