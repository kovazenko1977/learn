<?php

class SLAProvider {
    public static function calculateDeadline($priority, $createdAt = null) {
        $settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
        $hours = $settings['priorities'][$priority] ?? 24;

        $start = $createdAt ? strtotime($createdAt) : time();
        return date('Y-m-d H:i:s', $start + ($hours * 3600));
    }

    public static function isOverdue($deadline) {
        return strtotime($deadline) < time();
    }
}
