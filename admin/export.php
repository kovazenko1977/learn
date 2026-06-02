<?php
require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');
$type = $_GET['type'] ?? 'bookings';

header('Content-Type: text/csv; charset=utf-8');

if ($type === 'guests') {
    header('Content-Disposition: attachment; filename=guests_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'ФИО', 'Телефон', 'Пол', 'Гражданство', 'Адрес', 'Черный список', 'Причина']);

    $guests = $store->findAll('guests');
    foreach ($guests as $g) {
        fputcsv($output, [
            $g['id'],
            $g['name'],
            $g['phone'],
            $g['gender'] ?? '',
            $g['citizenship'] ?? '',
            $g['address'] ?? '',
            !empty($g['is_blacklisted']) ? 'Да' : 'Нет',
            $g['blacklist_reason'] ?? ''
        ]);
    }
} else {
    header('Content-Disposition: attachment; filename=bookings_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Гость', 'Телефон', 'Номер', 'Заезд', 'Выезд', 'Сумма', 'Статус', 'Заметки']);

    $bookings = $store->findAll('bookings');
    $rooms = $store->findAll('rooms');
    $roomMap = [];
    foreach ($rooms as $r) $roomMap[$r['id']] = $r['room_number'];

    foreach ($bookings as $b) {
        fputcsv($output, [
            $b['id'],
            $b['client_name'],
            $b['phone'],
            $roomMap[$b['room_id']] ?? $b['room_id'],
            $b['check_in'],
            $b['check_out'],
            $b['total_price'],
            $b['status'],
            $b['admin_notes'] ?? ''
        ]);
    }
}
fclose($output);
