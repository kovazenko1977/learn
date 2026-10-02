<?php
if (!defined('ABSPATH')) {
    exit;
}

class Memory_Pages_DB {

    public static function init() {
        // Any init logic if needed
    }

    /**
     * Create custom MySQL tables for Memory Pages plugin
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. wp_memorials
        $table_memorials = $wpdb->prefix . 'memorials';
        $sql_memorials = "CREATE TABLE $table_memorials (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code varchar(16) NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'draft',
            full_name varchar(255) NOT NULL,
            birth_date date DEFAULT NULL,
            death_date date DEFAULT NULL,
            birth_place varchar(255) DEFAULT '',
            cemetery varchar(255) DEFAULT '',
            plot varchar(64) DEFAULT '',
            row_number varchar(64) DEFAULT '',
            place_number varchar(64) DEFAULT '',
            biography longtext DEFAULT '',
            main_photo_id bigint(20) UNSIGNED DEFAULT 0,
            main_photo_url text DEFAULT '',
            created_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            updated_by bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_memorials);

        // 2. wp_memorial_photos
        $table_photos = $wpdb->prefix . 'memorial_photos';
        $sql_photos = "CREATE TABLE $table_photos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED NOT NULL,
            attachment_id bigint(20) UNSIGNED DEFAULT 0,
            photo_url text NOT NULL,
            caption varchar(255) DEFAULT '',
            is_main tinyint(1) NOT NULL DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY memorial_id (memorial_id)
        ) $charset_collate;";
        dbDelta($sql_photos);

        // 3. wp_memorial_relatives
        $table_relatives = $wpdb->prefix . 'memorial_relatives';
        $sql_relatives = "CREATE TABLE $table_relatives (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED NOT NULL,
            full_name varchar(255) NOT NULL,
            kinship_degree varchar(100) DEFAULT '',
            phone varchar(100) DEFAULT '',
            email varchar(100) DEFAULT '',
            telegram varchar(100) DEFAULT '',
            show_contact_to_visitor tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY memorial_id (memorial_id)
        ) $charset_collate;";
        dbDelta($sql_relatives);

        // 4. wp_memorial_requests
        $table_requests = $wpdb->prefix . 'memorial_requests';
        $sql_requests = "CREATE TABLE $table_requests (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED DEFAULT 0,
            applicant_name varchar(255) NOT NULL,
            phone varchar(100) DEFAULT '',
            email varchar(100) DEFAULT '',
            message text DEFAULT '',
            status varchar(32) NOT NULL DEFAULT 'new',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY memorial_id (memorial_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_requests);

        // 5. wp_memorial_events
        $table_events = $wpdb->prefix . 'memorial_events';
        $sql_events = "CREATE TABLE $table_events (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED NOT NULL,
            event_type varchar(50) NOT NULL,
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY memorial_id (memorial_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_events);

        // 6. wp_memorial_daily_stats
        $table_daily_stats = $wpdb->prefix . 'memorial_daily_stats';
        $sql_daily_stats = "CREATE TABLE $table_daily_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED NOT NULL,
            stat_date date NOT NULL,
            views_count int(11) NOT NULL DEFAULT 0,
            qr_views_count int(11) NOT NULL DEFAULT 0,
            search_views_count int(11) NOT NULL DEFAULT 0,
            direct_views_count int(11) NOT NULL DEFAULT 0,
            contacts_opened_count int(11) NOT NULL DEFAULT 0,
            flowers_count int(11) NOT NULL DEFAULT 0,
            candles_count int(11) NOT NULL DEFAULT 0,
            downloads_count int(11) NOT NULL DEFAULT 0,
            shares_count int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY memorial_date (memorial_id, stat_date),
            KEY stat_date (stat_date)
        ) $charset_collate;";
        dbDelta($sql_daily_stats);

        // 7. wp_memorial_qr
        $table_qr = $wpdb->prefix . 'memorial_qr';
        $sql_qr = "CREATE TABLE $table_qr (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED NOT NULL,
            code varchar(16) NOT NULL,
            qr_image_url text DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY memorial_id (memorial_id),
            KEY code (code)
        ) $charset_collate;";
        dbDelta($sql_qr);

        // 8. wp_memorial_logs (Версионирование и аудит)
        $table_logs = $wpdb->prefix . 'memorial_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            memorial_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            action varchar(100) NOT NULL,
            old_value longtext DEFAULT '',
            new_value longtext DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY memorial_id (memorial_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_logs);
    }
}
