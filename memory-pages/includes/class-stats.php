<?php
if (!defined('ABSPATH')) exit;

class Memory_Pages_Stats {

    public static function log_event($memorial_id, $event_type) {
        global $wpdb;

        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Insert event
        $wpdb->insert($wpdb->prefix . 'memorial_events', array(
            'memorial_id' => $memorial_id,
            'event_type'  => $event_type,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
            'created_at'  => current_time('mysql'),
        ));

        // Update daily aggregate
        $today = current_time('Y-m-d');
        $table_daily = $wpdb->prefix . 'memorial_daily_stats';

        $column = 'views_count';
        switch ($event_type) {
            case 'QR_VIEW': $column = 'qr_views_count'; break;
            case 'SEARCH_VIEW': $column = 'search_views_count'; break;
            case 'DIRECT_VIEW': $column = 'direct_views_count'; break;
            case 'CONTACT_OPEN': $column = 'contacts_opened_count'; break;
            case 'FLOWER': $column = 'flowers_count'; break;
            case 'CANDLE': $column = 'candles_count'; break;
            case 'PHOTO_DOWNLOAD': $column = 'downloads_count'; break;
            case 'SHARE': $column = 'shares_count'; break;
        }

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_daily WHERE memorial_id = %d AND stat_date = %s",
            $memorial_id, $today
        ));

        if ($exists) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_daily SET $column = $column + 1 WHERE id = %d",
                $exists
            ));
        } else {
            $wpdb->insert($table_daily, array(
                'memorial_id' => $memorial_id,
                'stat_date'   => $today,
                $column       => 1
            ));
        }
    }

    public static function get_overall_stats() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT m.full_name, m.code,
                SUM(d.views_count) as views,
                SUM(d.qr_views_count) as qr_views,
                SUM(d.search_views_count) as search_views,
                SUM(d.direct_views_count) as direct_views,
                SUM(d.contacts_opened_count) as contacts,
                SUM(d.flowers_count) as flowers,
                SUM(d.candles_count) as candles
             FROM {$wpdb->prefix}memorials m
             LEFT JOIN {$wpdb->prefix}memorial_daily_stats d ON m.id = d.memorial_id
             GROUP BY m.id ORDER BY m.id DESC",
            ARRAY_A
        );
    }
}
