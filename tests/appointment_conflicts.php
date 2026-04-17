<?php
require_once __DIR__ . '/../includes/Storage.php';

function checkConflicts($newAppointment, $existingAppointments) {
    foreach ($existingAppointments as $app) {
        if ($app['id'] === $newAppointment['id']) continue;
        if ($app['status'] === 'cancelled') continue;

        $start1 = strtotime($newAppointment['date'] . ' ' . $newAppointment['time_start']);
        $end1 = strtotime($newAppointment['date'] . ' ' . $newAppointment['time_end']);
        $start2 = strtotime($app['date'] . ' ' . $app['time_start']);
        $end2 = strtotime($app['date'] . ' ' . $app['time_end']);

        // Overlap condition
        if ($start1 < $end2 && $end1 > $start2) {
            if ($app['doctor_id'] === $newAppointment['doctor_id']) {
                return "Doctor is busy";
            }
            if ($app['room_id'] === $newAppointment['room_id']) {
                return "Room is occupied";
            }
        }
    }
    return null;
}

$existing = [
    [
        'id' => '1',
        'date' => '2023-10-27',
        'time_start' => '10:00',
        'time_end' => '11:00',
        'doctor_id' => 'doc1',
        'room_id' => 'room1',
        'status' => 'planned'
    ]
];

$test1 = [
    'id' => '2',
    'date' => '2023-10-27',
    'time_start' => '10:30',
    'time_end' => '11:30',
    'doctor_id' => 'doc1',
    'room_id' => 'room2'
];

$test2 = [
    'id' => '3',
    'date' => '2023-10-27',
    'time_start' => '09:00',
    'time_end' => '10:00',
    'doctor_id' => 'doc2',
    'room_id' => 'room1'
];

$test3 = [
    'id' => '4',
    'date' => '2023-10-27',
    'time_start' => '11:00',
    'time_end' => '12:00',
    'doctor_id' => 'doc1',
    'room_id' => 'room1'
];

echo "Test 1 (Doctor conflict): " . (checkConflicts($test1, $existing) === "Doctor is busy" ? "PASS" : "FAIL") . PHP_EOL;
echo "Test 2 (No conflict, ends exactly at start): " . (checkConflicts($test2, $existing) === null ? "PASS" : "FAIL") . PHP_EOL;
echo "Test 3 (No conflict, starts exactly at end): " . (checkConflicts($test3, $existing) === null ? "PASS" : "FAIL") . PHP_EOL;

$test4 = [
    'id' => '5',
    'date' => '2023-10-27',
    'time_start' => '10:15',
    'time_end' => '10:45',
    'doctor_id' => 'doc2',
    'room_id' => 'room1'
];
echo "Test 4 (Room conflict): " . (checkConflicts($test4, $existing) === "Room is occupied" ? "PASS" : "FAIL") . PHP_EOL;
