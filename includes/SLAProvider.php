<?php

class SLAProvider {
    private $priorities;

    public function __construct($settings) {
        $this->priorities = $settings['priorities'] ?? [];
    }

    public function calculateDeadline($priorityName, $createdAt = null) {
        $hours = 24; // default
        foreach ($this->priorities as $p) {
            if ($p['name'] === $priorityName) {
                $hours = $p['sla_hours'];
                break;
            }
        }

        $baseTime = $createdAt ? strtotime($createdAt) : time();
        return date('Y-m-d H:i:s', $baseTime + ($hours * 3600));
    }
}
