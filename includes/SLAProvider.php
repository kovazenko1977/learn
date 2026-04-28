<?php
class SLAProvider {
    public static function getDeadline($priority, $createdAt) {
        $settings = Storage::read('settings');
        $sla = $settings['sla'] ?? [
            'Low' => 72,
            'Medium' => 48,
            'High' => 24,
            'Urgent' => 4
        ];

        $hours = $sla[$priority] ?? 48;
        return date('Y-m-d H:i:s', strtotime($createdAt) + ($hours * 3600));
    }

    public static function isOverdue($task) {
        if ($task['status'] === 'Completed') return false;
        $deadline = self::getDeadline($task['priority'], $task['created_at']);
        return time() > strtotime($deadline);
    }
}
