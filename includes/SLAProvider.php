<?php
require_once __DIR__ . '/Storage.php';

class SLAProvider {
    /**
     * Calculates the deadline based on priority and category base hours.
     * Considers business hours (9:00 - 18:00) and weekends.
     */
    public static function calculateDeadline($priority, $categoryName = 'General') {
        $settings = Storage::read('settings');
        $baseHours = 24; // Default

        // Priority multipliers
        $multipliers = [
            'Low' => 1.5,
            'Medium' => 1.0,
            'High' => 0.5,
            'Critical' => 0.25
        ];

        // Try to get base hours from settings or category-specific SLA if implemented
        $slaSettings = $settings['sla'] ?? [];
        if (isset($slaSettings[$priority])) {
            // If settings provide direct hours per priority, use them as base
            $baseHours = $slaSettings[$priority];
            $hoursToAdd = $baseHours; // already scaled in settings usually
        } else {
            $factor = $multipliers[$priority] ?? 1.0;
            $hoursToAdd = $baseHours * $factor;
        }

        $currentTimestamp = time();
        return self::addBusinessHours($currentTimestamp, $hoursToAdd);
    }

    private static function addBusinessHours($startTS, $hoursToAdd) {
        $currentTS = $startTS;
        $secondsToAdd = $hoursToAdd * 3600;

        while ($secondsToAdd > 0) {
            $currentTS += 60; // Advance minute by minute for precision

            $hour = (int)date('G', $currentTS);
            $dayOfWeek = (int)date('w', $currentTS); // 0 (Sun) to 6 (Sat)

            // Business hours: 9:00 - 18:00 (9 to 17 inclusive)
            // Working days: 1 (Mon) to 5 (Fri)
            $isWorkingDay = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
            $isWorkingHour = ($hour >= 9 && $hour < 18);

            if ($isWorkingDay && $isWorkingHour) {
                $secondsToAdd -= 60;
            }
        }

        return date('Y-m-d H:i:s', $currentTS);
    }
}
