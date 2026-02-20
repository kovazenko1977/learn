<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\UserManager;

checkRole(['manager', 'admin']);

$requestStore = new JsonStore('data/requests.json');
$requests = $requestStore->read();

// Apply filters if present
$fStatus = $_GET['status'] ?? null;
$fPerformerId = isset($_GET['performer_id']) ? (int)$_GET['performer_id'] : null;
$fStart = $_GET['start_date'] ?? null;
$fEnd = $_GET['end_date'] ?? null;

if ($fStatus || $fPerformerId || $fStart || $fEnd) {
    $requests = array_filter($requests, function($req) use ($fStatus, $fPerformerId, $fStart, $fEnd) {
        if ($fStatus && $req['status'] !== $fStatus) return false;
        if ($fPerformerId && ($req['performer_id'] ?? 0) !== $fPerformerId) return false;

        $createdAt = strtotime($req['created_at']);
        if ($fStart && $createdAt < strtotime($fStart . ' 00:00:00')) return false;
        if ($fEnd && $createdAt > strtotime($fEnd . ' 23:59:59')) return false;

        return true;
    });
}

$serviceStore = new JsonStore('data/services.json');
$services = [];
foreach ($serviceStore->read() as $s) $services[$s['id']] = $s['name'];

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);
$users = [];
foreach ($userManager->getAll() as $u) $users[$u['id']] = $u['name'];

$statusNames = [
    'new' => 'Новая',
    'assigned' => 'Назначена',
    'working' => 'В работе',
    'checking' => 'Проверка',
    'returned' => 'Доработка',
    'completed' => 'Выполнена',
    'closed' => 'Закрыта'
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=requests_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
// BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['ID', 'Дата создания', 'Служба', 'Статус', 'Приоритет', 'Место', 'Описание', 'Инициатор', 'Исполнитель']);

foreach ($requests as $req) {
    fputcsv($output, [
        $req['id'],
        $req['created_at'],
        $services[$req['service_id']] ?? $req['service_id'],
        $statusNames[$req['status']] ?? $req['status'],
        $req['priority'],
        "Корп. {$req['location']['building']}, эт. {$req['location']['floor']}, каб. {$req['location']['room']}",
        $req['description'],
        $users[$req['initiator_id']] ?? $req['initiator_id'],
        $users[$req['performer_id'] ?? 0] ?? '-'
    ]);
}

fclose($output);
exit;
