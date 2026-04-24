<?php
require_once __DIR__ . '/../includes/SLAProvider.php';
require_once __DIR__ . '/../includes/Storage.php';

// Mock settings
$settings = [
    'sla' => ['Средний' => 10],
    'work_start' => '09:00',
    'work_end' => '17:00'
];
Storage::save('settings', $settings);

echo "Test 1: Normal calculation\n";
$deadline = SLAProvider::calculateDeadline('Средний');
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "Deadline: $deadline\n";

// If it's Friday 16:00, 10 hours should end on Monday 11:00 (assuming 8h work day)
// 1h on Friday (16-17), 8h on Monday (9-17), 1h left -> Monday 10:00?
// Let's test a specific start time if I could mock time(), but calculateDeadline uses time()

function testManual($startTS, $hours, $start, $end) {
    $ref = new ReflectionClass('SLAProvider');
    $method = $ref->getMethod('addBusinessHours');
    $method->setAccessible(true);
    return $method->invoke(null, $startTS, $hours, $start, $end);
}

echo "\nTest 2: Weekend span (Friday 16:00 + 10 hours, 9-17 schedule)\n";
$friday = strtotime('2024-04-26 16:00:00'); // Friday
$res = testManual($friday, 10, '09:00', '17:00');
echo "Start: 2024-04-26 16:00:00\n";
echo "Result: $res (Expected: 2024-04-29 11:00:00)\n";
// Friday: 16:00 -> 17:00 (1 hour)
// Monday: 09:00 -> 17:00 (8 hours, total 9)
// Tuesday: 09:00 -> 10:00 (1 hour, total 10)
// Wait, Friday 1h + Monday 8h = 9h. Tuesday 9:00-10:00 is 10th hour.
// Actually my logic: while(secondsToAdd > 0) currentTS += 60.
// 10 hours = 600 minutes.
// Friday 16:00-17:00 = 60 mins.
// Monday 09:00-17:00 = 8 * 60 = 480 mins. Total 540.
// Tuesday 09:00-10:00 = 60 mins. Total 600. Result 10:00.

echo "\nTest 3: Short day (09:00 - 10:00 schedule, 2 hours)\n";
$monday = strtotime('2024-04-22 09:30:00');
$res = testManual($monday, 2, '09:00', '10:00');
echo "Start: 2024-04-22 09:30:00\n";
echo "Result: $res (Expected: 2024-04-24 09:30:00)\n";
// Mon: 9:30-10:00 (30m)
// Tue: 9:00-10:00 (60m) -> 90m
// Wed: 9:00-9:30 (30m) -> 120m (2h)
