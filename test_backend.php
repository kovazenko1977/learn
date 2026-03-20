<?php
require_once __DIR__ . '/includes/Storage.php';
require_once __DIR__ . '/includes/SanatoriumManager.php';
require_once __DIR__ . '/includes/ProgramManager.php';
require_once __DIR__ . '/includes/BookingManager.php';

function test($name, $callback) {
    echo "Testing $name: ";
    try {
        $result = $callback();
        echo $result ? "PASSED" : "FAILED";
    } catch (Exception $e) {
        echo "ERROR (" . $e->getMessage() . ")";
    }
    echo "\n";
}

// Clear test data
@unlink(__DIR__ . '/data/sanatorium_data.json');
@unlink(__DIR__ . '/data/rooms_data.json');
@unlink(__DIR__ . '/data/programs_data.json');
@unlink(__DIR__ . '/data/bookings_data.json');

test("SanatoriumManager - Info", function() {
    $info = ['name' => 'Test Sanatorium', 'address' => 'Test Address'];
    SanatoriumManager::updateInfo($info);
    $readInfo = SanatoriumManager::getInfo();
    return $readInfo['name'] === 'Test Sanatorium';
});

test("SanatoriumManager - Rooms", function() {
    $room = ['name' => 'Standard Room', 'building_id' => 'B1', 'price' => 5000];
    SanatoriumManager::saveRoom($room);
    $rooms = SanatoriumManager::getRooms('B1');
    return count($rooms) === 1 && reset($rooms)['name'] === 'Standard Room';
});

test("ProgramManager - Programs", function() {
    $program = ['name' => 'Cardio', 'duration' => 14];
    $id = ProgramManager::save($program);
    $readProgram = ProgramManager::getById($id);
    return $readProgram['name'] === 'Cardio' && $readProgram['duration'] === 14;
});

test("BookingManager - Lifecycle", function() {
    $booking = [
        'guest_name' => 'John Doe',
        'program_id' => 'PRG_123',
        'room_id' => 'RM_456',
        'check_in' => '2023-10-01',
        'check_out' => '2023-10-14'
    ];
    $id = BookingManager::create($booking);
    $readBooking = BookingManager::getById($id);
    if ($readBooking['guest_name'] !== 'John Doe' || $readBooking['status'] !== 'new') return false;

    BookingManager::updateStatus($id, 'confirmed');
    $updatedBooking = BookingManager::getById($id);
    return $updatedBooking['status'] === 'confirmed';
});

echo "\nData files created:\n";
foreach (glob(__DIR__ . '/data/*.json') as $file) {
    echo "- " . basename($file) . "\n";
}
