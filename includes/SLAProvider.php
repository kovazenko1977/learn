<?php
require_once __DIR__ . '/Storage.php';

class SLAProvider {
    public static function calculateDeadline($priority, $categoryName = 'General') {
        $settings = Storage::read('settings');
        $baseHours = 24;

        $multipliers = [
            'Низкий' => 1.5,
            'Средний' => 1.0,
            'Высокий' => 0.5,
            'Критический' => 0.25
        ];

        $slaSettings = $settings['sla'] ?? [];
        if (isset($slaSettings[$priority])) {
            $hoursToAdd = $slaSettings[$priority];
        } else {
            $factor = $multipliers[$priority] ?? 1.0;
            $hoursToAdd = $baseHours * $factor;
        }

        $currentTimestamp = time();
        $workStart = $settings['work_start'] ?? '09:00';
        $workEnd = $settings['work_end'] ?? '18:00';

        return self::addBusinessHours($currentTimestamp, $hoursToAdd, $workStart, $workEnd);
    }

    private static function addBusinessHours($startTS, $hoursToAdd, $workStart, $workEnd) {
        $currentTS = $startTS;
        $secondsToAdd = $hoursToAdd * 3600;

        list($startH, $startM) = explode(':', $workStart);
        list($endH, $endM) = explode(':', $workEnd);
        $startMin = $startH * 60 + $startM;
        $endMin = $endH * 60 + $endM;

        while ($secondsToAdd > 0) {
            $currentTS += 60;

            $hour = (int)date('G', $currentTS);
            $min = (int)date('i', $currentTS);
            $totalMin = $hour * 60 + $min;

            $dayOfWeek = (int)date('w', $currentTS);

            $isWorkingDay = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
            $isWorkingHour = ($totalMin > $startMin && $totalMin <= $endMin);

            if ($isWorkingDay && $isWorkingHour) {
                $secondsToAdd -= 60;
            }
        }

        return date('Y-m-d H:i:s', $currentTS);
    }
}
