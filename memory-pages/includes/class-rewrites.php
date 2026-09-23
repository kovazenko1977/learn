<?php
if (!defined('ABSPATH')) {
    exit;
}

class Memory_Pages_Rewrites {

    public static function init() {
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'template_redirect'));
    }

    /**
     * Add rewrite rules for permanent URLs /memory/XXXXXXXX/
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^memory/([0-9]{8})/?$',
            'index.php?memorial_code=$matches[1]',
            'top'
        );
    }

    /**
     * Add query variable for memorial code
     */
    public static function add_query_vars($vars) {
        $vars[] = 'memorial_code';
        $vars[] = 'memorial_preview';
        return $vars;
    }

    /**
     * Template redirect handler
     */
    public static function template_redirect() {
        $code = get_query_var('memorial_code');
        if (!empty($code)) {
            // Include public renderer logic
            Memory_Pages_Public::render_memorial($code);
            exit;
        }
    }

    /**
     * Generate unique 8-digit numeric code
     * @return string
     */
    public static function generate_unique_code() {
        global $wpdb;
        $table_memorials = $wpdb->prefix . 'memorials';

        do {
            // Generate random 8-digit number (10000000 to 99999999)
            $code = (string) wp_rand(10000000, 99999999);

            // Check uniqueness in database
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_memorials WHERE code = %s",
                $code
            ));
        } while ($exists > 0);

        return $code;
    }
}
