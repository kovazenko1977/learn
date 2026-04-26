<?php

class SLAProvider {
    public static function getDeadline($createdAt, $priority) {
        $settings = Storage::get('settings');
        $sla = $settings['sla_rules'] ?? [
            'low' => 72,
            'medium' => 48,
            'high' => 24,
            'critical' => 4
        ];

        $hours = $sla[$priority] ?? 48;
        return date('Y-m-d H:i:s', strtotime($createdAt . " + $hours hours"));
    }

    public static function isOverdue($task) {
        if ($task['status'] === 'completed') {
            $deadline = self::getDeadline($task['created_at'], $task['priority']);
            return strtotime($task['completed_at']) > strtotime($deadline);
        }

        $deadline = self::getDeadline($task['created_at'], $task['priority']);
        return time() > strtotime($deadline);
    }

    public static function getRemainingTime($task) {
        if ($task['status'] === 'completed') return 0;
        $deadline = self::getDeadline($task['created_at'], $task['priority']);
        return strtotime($deadline) - time();
    }
}
