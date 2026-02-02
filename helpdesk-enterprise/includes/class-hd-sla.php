<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_SLA {
    /**
     * Calculate deadline based on SLA hours and department settings.
     */
    public static function calculate_deadline($base_hours, $dept_settings, $start_time = null) {
        if (!$start_time) {
            $start_time = current_time('timestamp');
        } else if (is_string($start_time)) {
            $start_time = strtotime($start_time);
        }

        $working_hours = isset($dept_settings['working_hours']) ? $dept_settings['working_hours'] : ['start' => '09:00', 'end' => '18:00'];
        $weekends = isset($dept_settings['weekends']) ? $dept_settings['weekends'] : [0, 6]; // 0=Sun, 6=Sat
        $holidays = isset($dept_settings['holidays']) ? $dept_settings['holidays'] : []; // Array of Y-m-d

        $current_time = $start_time;

        // Ensure start time is within working hours and not on a holiday/weekend
        $current_time = self::adjust_to_working_time($current_time, $working_hours, $weekends, $holidays);

        $remaining_hours = $base_hours;

        while ($remaining_hours > 0) {
            $day_end_str = date('Y-m-d', $current_time) . ' ' . $working_hours['end'];
            $day_end = strtotime($day_end_str);

            $available_today = ($day_end - $current_time) / 3600;

            if ($available_today >= $remaining_hours) {
                $current_time += $remaining_hours * 3600;
                $remaining_hours = 0;
            } else {
                $remaining_hours -= $available_today;
                // Move to next working day start
                $current_time = strtotime('+1 day', $current_time);
                $current_time = strtotime(date('Y-m-d', $current_time) . ' ' . $working_hours['start']);
                $current_time = self::adjust_to_working_time($current_time, $working_hours, $weekends, $holidays);
            }
        }

        return date('Y-m-d H:i:s', $current_time);
    }

    private static function adjust_to_working_time($time, $working_hours, $weekends, $holidays) {
        $max_iterations = 365; // Prevent infinite loop
        while ($max_iterations-- > 0) {
            $date_str = date('Y-m-d', $time);
            $day_of_week = date('w', $time);

            // Is it a weekend or holiday?
            if (in_array($day_of_week, $weekends) || in_array($date_str, $holidays)) {
                $time = strtotime('+1 day', $time);
                $time = strtotime(date('Y-m-d', $time) . ' ' . $working_hours['start']);
                continue;
            }

            $start_of_day = strtotime($date_str . ' ' . $working_hours['start']);
            $end_of_day = strtotime($date_str . ' ' . $working_hours['end']);

            if ($time < $start_of_day) {
                $time = $start_of_day;
            } else if ($time >= $end_of_day) {
                $time = strtotime('+1 day', $time);
                $time = strtotime(date('Y-m-d', $time) . ' ' . $working_hours['start']);
                continue;
            }

            break;
        }
        return $time;
    }
}
