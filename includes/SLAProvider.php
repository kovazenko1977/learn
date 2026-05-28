<?php

class SLAProvider {
    private $settings;

    public function __construct($settings = []) {
        $this->settings = $settings;
    }

    public function calculateDeadline($priority) {
        $sla = $this->settings['sla_rules'] ?? [
            'low' => 72,
            'medium' => 48,
            'high' => 24,
            'urgent' => 4
        ];

        $hours = $sla[$priority] ?? 48;
        $now = new DateTime();

        // Simple calculation (add hours)
        // In a real scenario, this would skip weekends and non-business hours
        $now->modify("+{$hours} hours");

        return $now->format('Y-m-d H:i:s');
    }
}
