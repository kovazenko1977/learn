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
        $lunch_break = isset($dept_settings['lunch_break']) ? $dept_settings['lunch_break'] : ['start' => '13:00', 'end' => '14:00'];
        $weekends = isset($dept_settings['weekends']) ? $dept_settings['weekends'] : [0, 6]; // 0=Sun, 6=Sat
        $holidays = isset($dept_settings['holidays']) ? $dept_settings['holidays'] : []; // Array of Y-m-d

        $current_time = $start_time;

        // Ensure start time is within working hours and not on a holiday/weekend
        $current_time = self::adjust_to_working_time($current_time, $working_hours, $weekends, $holidays, $lunch_break);

        $remaining_hours = $base_hours;

        while ($remaining_hours > 0) {
            $date_str = date('Y-m-d', $current_time);
            $day_end = strtotime($date_str . ' ' . $working_hours['end']);
            $lunch_start = strtotime($date_str . ' ' . $lunch_break['start']);
            $lunch_end = strtotime($date_str . ' ' . $lunch_break['end']);

            // Calculate available time today considering lunch
            if ($current_time < $lunch_start) {
                $available_before_lunch = ($lunch_start - $current_time) / 3600;
                $available_after_lunch = ($day_end - $lunch_end) / 3600;
                $available_today = $available_before_lunch + $available_after_lunch;
            } else if ($current_time >= $lunch_end) {
                $available_today = ($day_end - $current_time) / 3600;
            } else {
                // We are in lunch, adjust_to_working_time should have handled this, but just in case
                $current_time = $lunch_end;
                $available_today = ($day_end - $current_time) / 3600;
            }

            if ($available_today >= $remaining_hours) {
                // Check if adding remaining hours crosses lunch
                if ($current_time < $lunch_start && ($current_time + $remaining_hours * 3600) > $lunch_start) {
                    $current_time += ($remaining_hours * 3600) + ($lunch_end - $lunch_start);
                } else {
                    $current_time += $remaining_hours * 3600;
                }
                $remaining_hours = 0;
            } else {
                $remaining_hours -= $available_today;
                // Move to next working day start
                $current_time = strtotime('+1 day', $current_time);
                $current_time = strtotime(date('Y-m-d', $current_time) . ' ' . $working_hours['start']);
                $current_time = self::adjust_to_working_time($current_time, $working_hours, $weekends, $holidays, $lunch_break);
            }
        }

        return date('Y-m-d H:i:s', $current_time);
    }

    private static function adjust_to_working_time($time, $working_hours, $weekends, $holidays, $lunch_break) {
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
            $lunch_start = strtotime($date_str . ' ' . $lunch_break['start']);
            $lunch_end = strtotime($date_str . ' ' . $lunch_break['end']);

            if ($time < $start_of_day) {
                $time = $start_of_day;
            } else if ($time >= $end_of_day) {
                $time = strtotime('+1 day', $time);
                $time = strtotime(date('Y-m-d', $time) . ' ' . $working_hours['start']);
                continue;
            } else if ($time >= $lunch_start && $time < $lunch_end) {
                $time = $lunch_end;
            }

            break;
        }
        return $time;
    }
}
