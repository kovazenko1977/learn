<?php
if (!defined('ABSPATH')) exit;

class Memory_Pages_Public {

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_public_assets'));

        // Public AJAX actions
        add_action('wp_ajax_mp_public_flower', array(__CLASS__, 'ajax_flower'));
        add_action('wp_ajax_nopriv_mp_public_flower', array(__CLASS__, 'ajax_flower'));

        add_action('wp_ajax_mp_public_candle', array(__CLASS__, 'ajax_candle'));
        add_action('wp_ajax_nopriv_mp_public_candle', array(__CLASS__, 'ajax_candle'));

        add_action('wp_ajax_mp_public_contact_open', array(__CLASS__, 'ajax_contact_open'));
        add_action('wp_ajax_nopriv_mp_public_contact_open', array(__CLASS__, 'ajax_contact_open'));

        add_action('wp_ajax_mp_public_share', array(__CLASS__, 'ajax_share'));
        add_action('wp_ajax_nopriv_mp_public_share', array(__CLASS__, 'ajax_share'));
    }

    public static function enqueue_public_assets() {
        wp_enqueue_style('memory-pages-public-css', MEMORY_PAGES_URL . 'public/css/public.css', array(), MEMORY_PAGES_VERSION);
        wp_enqueue_script('memory-pages-public-js', MEMORY_PAGES_URL . 'public/js/public.js', array('jquery'), MEMORY_PAGES_VERSION, true);

        wp_localize_script('memory-pages-public-js', 'mp_public_opts', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mp_public_nonce'),
        ));
    }

    public static function render_memorial($code) {
        $memorial = Memory_Pages_Memorial::get($code);

        if (!$memorial) {
            status_header(404);
            wp_die('Страница памяти не найдена', '404 Not Found');
        }

        $is_preview = isset($_GET['preview']) && current_user_can('manage_options');

        // Access check
        if ($memorial['status'] !== 'published' && !$is_preview) {
            if ($memorial['status'] === 'archive') {
                wp_die('Эта страница памяти находится в архиве.', 'Архив');
            } else {
                wp_die('Эта страница памяти пока не опубликована.', 'Доступ ограничен');
            }
        }

        // Track view
        $source = sanitize_text_field($_GET['source'] ?? 'direct');
        $event_type = ($source === 'qr') ? 'QR_VIEW' : 'DIRECT_VIEW';
        Memory_Pages_Stats::log_event($memorial['id'], $event_type);

        $photos = Memory_Pages_Memorial::get_photos($memorial['id']);
        $relatives = Memory_Pages_Memorial::get_relatives($memorial['id']);
        $qr_url = Memory_Pages_QR::get_or_create_qr($memorial['id'], $memorial['code']);

        require_once MEMORY_PAGES_PATH . 'templates/single-memorial.php';
        exit;
    }

    // AJAX Handlers
    public static function ajax_flower() {
        check_ajax_referer('mp_public_nonce', 'nonce');
        $id = intval($_POST['memorial_id'] ?? 0);
        Memory_Pages_Stats::log_event($id, 'FLOWER');

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(flowers_count) FROM {$wpdb->prefix}memorial_daily_stats WHERE memorial_id = %d", $id
        ));

        wp_send_json_success(array('count' => intval($count)));
    }

    public static function ajax_candle() {
        check_ajax_referer('mp_public_nonce', 'nonce');
        $id = intval($_POST['memorial_id'] ?? 0);
        Memory_Pages_Stats::log_event($id, 'CANDLE');

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(candles_count) FROM {$wpdb->prefix}memorial_daily_stats WHERE memorial_id = %d", $id
        ));

        wp_send_json_success(array('count' => intval($count)));
    }

    public static function ajax_contact_open() {
        check_ajax_referer('mp_public_nonce', 'nonce');
        $id = intval($_POST['memorial_id'] ?? 0);
        Memory_Pages_Stats::log_event($id, 'CONTACT_OPEN');
        wp_send_json_success();
    }

    public static function ajax_share() {
        check_ajax_referer('mp_public_nonce', 'nonce');
        $id = intval($_POST['memorial_id'] ?? 0);
        Memory_Pages_Stats::log_event($id, 'SHARE');
        wp_send_json_success();
    }
}
