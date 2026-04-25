<?php

class SLAProvider {
    public static function calculateDeadline(string $priority): string {
        $settings = Storage::read('settings.json');
        $slaSettings = $settings['sla'] ?? [
            'high' => 4,    // 4 hours
            'medium' => 24, // 24 hours
            'low' => 72     // 72 hours
        ];

        $hoursToAdd = $slaSettings[$priority] ?? 24;
        $now = new DateTime();

        // Simple logic: just add hours.
        // More complex logic would skip non-business hours/weekends.
        $now->modify("+{$hoursToAdd} hours");

        return $now->format('Y-m-d H:i:s');
    }

    public static function isOverdue(string $deadline): bool {
        if (!$deadline) return false;
        return strtotime($deadline) < time();
    }
}
