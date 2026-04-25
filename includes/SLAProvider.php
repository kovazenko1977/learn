<?php

class SLAProvider {
    public static function calculateDeadline($createdAt, $priority, $businessHours) {
        $created = new DateTime($createdAt);

        // Simple SLA logic:
        // High -> 4 business hours
        // Medium -> 8 business hours
        // Low -> 24 business hours

        $hours = 8;
        if ($priority === 'High') $hours = 4;
        if ($priority === 'Low') $hours = 24;

        $deadline = clone $created;
        $addedHours = 0;

        while ($addedHours < $hours) {
            $deadline->modify('+1 hour');

            $hour = (int)$deadline->format('H');
            $day = (int)$deadline->format('w'); // 0 (Sun) to 6 (Sat)

            $start = (int)explode(':', $businessHours['start'])[0];
            $end = (int)explode(':', $businessHours['end'])[0];

            if (in_array($day, $businessHours['days']) && $hour >= $start && $hour < $end) {
                $addedHours++;
            }
        }

        return $deadline->format('Y-m-d H:i:s');
    }

    public static function getStatus($deadline) {
        $now = new DateTime();
        $due = new DateTime($deadline);

        if ($now > $due) return 'Overdue';

        $diff = $due->getTimestamp() - $now->getTimestamp();
        if ($diff < 3600 * 2) return 'Expiring'; // Less than 2 hours

        return 'On Time';
    }
}
