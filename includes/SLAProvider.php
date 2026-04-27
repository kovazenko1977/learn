<?php
class SLAProvider {
    public static function calculateDeadline($priorityId, $createdAt = null) {
        $settings = Storage::getSettings();
        $priority = null;
        foreach ($settings['priorities'] as $p) {
            if ($p['id'] === $priorityId) {
                $priority = $p;
                break;
            }
        }
        if (!$priority) return null;

        $start = $createdAt ? strtotime($createdAt) : time();
        $deadline = $start + ($priority['sla_hours'] * 3600);
        return date('Y-m-d H:i:s', $deadline);
    }

    public static function getStatus($task) {
        if ($task['status'] === 'Completed') return 'in_sla';
        if (!$task['deadline']) return 'no_sla';
        return strtotime($task['deadline']) < time() ? 'overdue' : 'in_sla';
    }
}