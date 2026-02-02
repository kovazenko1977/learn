<?php

if (!defined('ABSPATH')) {
    exit;
}

class HD_DB {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Departments
        $sql[] = "CREATE TABLE {$wpdb->prefix}hd_departments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            manager_id bigint(20) DEFAULT 0,
            settings longtext DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Categories
        $sql[] = "CREATE TABLE {$wpdb->prefix}hd_categories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            department_id bigint(20) NOT NULL,
            base_sla int(11) DEFAULT 0,
            priority varchar(50) DEFAULT 'medium',
            default_executor_id bigint(20) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Requests
        $sql[] = "CREATE TABLE {$wpdb->prefix}hd_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description longtext NOT NULL,
            category_id bigint(20) NOT NULL,
            department_id bigint(20) NOT NULL,
            responsible_id bigint(20) NOT NULL,
            executor_id bigint(20) DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            deadline datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // History
        $sql[] = "CREATE TABLE {$wpdb->prefix}hd_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            old_value longtext DEFAULT NULL,
            new_value longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Comments
        $sql[] = "CREATE TABLE {$wpdb->prefix}hd_comments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Photos
        $sql[] = "CREATE TABLE {$wpdb->prefix}hd_photos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            file_url varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        foreach ($sql as $query) {
            dbDelta($query);
        }
    }
}
