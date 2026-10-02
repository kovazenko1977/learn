<?php
if (!defined('ABSPATH')) {
    exit;
}

class Memory_Pages_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_mp_save_memorial', array(__CLASS__, 'ajax_save_memorial'));
        add_action('wp_ajax_mp_save_photo', array(__CLASS__, 'ajax_save_photo'));
        add_action('wp_ajax_mp_delete_photo', array(__CLASS__, 'ajax_delete_photo'));
        add_action('wp_ajax_mp_save_relative', array(__CLASS__, 'ajax_save_relative'));
        add_action('wp_ajax_mp_delete_relative', array(__CLASS__, 'ajax_delete_relative'));
        add_action('wp_ajax_mp_generate_qr', array(__CLASS__, 'ajax_generate_qr'));
        add_action('wp_ajax_mp_update_request_status', array(__CLASS__, 'ajax_update_request_status'));
    }

    /**
     * Register Admin Menu & Submenus
     */
    public static function register_admin_menu() {
        $icon = 'dashicons-nametag';

        add_menu_page(
            'Страницы памяти',
            'Страницы памяти',
            'manage_options',
            'memory-pages',
            array(__CLASS__, 'render_all_pages'),
            $icon,
            30
        );

        add_submenu_page(
            'memory-pages',
            'Все страницы',
            'Все страницы',
            'manage_options',
            'memory-pages',
            array(__CLASS__, 'render_all_pages')
        );

        add_submenu_page(
            'memory-pages',
            'Добавить страницу',
            'Добавить страницу',
            'manage_options',
            'memory-pages-add',
            array(__CLASS__, 'render_edit_page')
        );

        add_submenu_page(
            'memory-pages',
            'Заявки',
            'Заявки',
            'manage_options',
            'memory-pages-requests',
            array(__CLASS__, 'render_requests_page')
        );

        add_submenu_page(
            'memory-pages',
            'Фотографии',
            'Фотографии',
            'manage_options',
            'memory-pages-photos',
            array(__CLASS__, 'render_photos_page')
        );

        add_submenu_page(
            'memory-pages',
            'Родственники',
            'Родственники',
            'manage_options',
            'memory-pages-relatives',
            array(__CLASS__, 'render_relatives_page')
        );

        add_submenu_page(
            'memory-pages',
            'QR-коды',
            'QR-коды',
            'manage_options',
            'memory-pages-qr',
            array(__CLASS__, 'render_qr_page')
        );

        add_submenu_page(
            'memory-pages',
            'Статистика',
            'Статистика',
            'manage_options',
            'memory-pages-stats',
            array(__CLASS__, 'render_stats_page')
        );

        add_submenu_page(
            'memory-pages',
            'Журнал действий',
            'Журнал действий',
            'manage_options',
            'memory-pages-logs',
            array(__CLASS__, 'render_logs_page')
        );

        add_submenu_page(
            'memory-pages',
            'Настройки',
            'Настройки',
            'manage_options',
            'memory-pages-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Enqueue Admin Assets
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'memory-pages') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('memory-pages-admin-css', MEMORY_PAGES_URL . 'admin/css/admin.css', array(), MEMORY_PAGES_VERSION);
        wp_enqueue_script('memory-pages-admin-js', MEMORY_PAGES_URL . 'admin/js/admin.js', array('jquery', 'jquery-ui-sortable'), MEMORY_PAGES_VERSION, true);

        wp_localize_script('memory-pages-admin-js', 'mp_admin_opts', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mp_admin_nonce'),
        ));
    }

    // Render Callbacks
    public static function render_all_pages() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-all.php';
    }

    public static function render_edit_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-edit.php';
    }

    public static function render_requests_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-requests.php';
    }

    public static function render_photos_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-photos.php';
    }

    public static function render_relatives_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-relatives.php';
    }

    public static function render_qr_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-qr.php';
    }

    public static function render_stats_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-stats.php';
    }

    public static function render_logs_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-logs.php';
    }

    public static function render_settings_page() {
        require_once MEMORY_PAGES_PATH . 'admin/views/view-settings.php';
    }

    // AJAX Handlers
    public static function ajax_save_memorial() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }

        $data = $_POST['data'] ?? array();
        $result = Memory_Pages_Memorial::save($data, get_current_user_id());

        wp_send_json_success($result);
    }

    public static function ajax_save_photo() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }
        global $wpdb;

        $memorial_id = intval($_POST['memorial_id'] ?? 0);
        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        $photo_url = esc_url_raw($_POST['photo_url'] ?? '');
        $caption = sanitize_text_field($_POST['caption'] ?? '');
        $is_main = intval($_POST['is_main'] ?? 0);

        if (!$memorial_id || !$photo_url) {
            wp_send_json_error('Invalid parameters');
        }

        $table = $wpdb->prefix . 'memorial_photos';

        if ($is_main) {
            $wpdb->update($table, array('is_main' => 0), array('memorial_id' => $memorial_id));
            $wpdb->update($wpdb->prefix . 'memorials', array('main_photo_url' => $photo_url, 'main_photo_id' => $attachment_id), array('id' => $memorial_id));
        }

        $wpdb->insert($table, array(
            'memorial_id'   => $memorial_id,
            'attachment_id' => $attachment_id,
            'photo_url'     => $photo_url,
            'caption'       => $caption,
            'is_main'       => $is_main,
            'created_at'    => current_time('mysql'),
        ));

        wp_send_json_success(array('id' => $wpdb->insert_id));
    }

    public static function ajax_delete_photo() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }
        global $wpdb;

        $photo_id = intval($_POST['photo_id'] ?? 0);
        $table = $wpdb->prefix . 'memorial_photos';
        $wpdb->delete($table, array('id' => $photo_id));

        wp_send_json_success();
    }

    public static function ajax_save_relative() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }
        global $wpdb;

        $memorial_id = intval($_POST['memorial_id'] ?? 0);
        $rel_id = intval($_POST['relative_id'] ?? 0);

        $data = array(
            'memorial_id' => $memorial_id,
            'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'kinship_degree' => sanitize_text_field($_POST['kinship_degree'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'telegram' => sanitize_text_field($_POST['telegram'] ?? ''),
            'show_contact_to_visitor' => intval($_POST['show_contact_to_visitor'] ?? 0),
        );

        $table = $wpdb->prefix . 'memorial_relatives';

        if ($rel_id > 0) {
            $wpdb->update($table, $data, array('id' => $rel_id));
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            $rel_id = $wpdb->insert_id;
        }

        wp_send_json_success(array('id' => $rel_id));
    }

    public static function ajax_delete_relative() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }
        global $wpdb;

        $rel_id = intval($_POST['relative_id'] ?? 0);
        $table = $wpdb->prefix . 'memorial_relatives';
        $wpdb->delete($table, array('id' => $rel_id));

        wp_send_json_success();
    }

    public static function ajax_generate_qr() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }

        $memorial_id = intval($_POST['memorial_id'] ?? 0);
        $memorial = Memory_Pages_Memorial::get($memorial_id);

        if (!$memorial) {
            wp_send_json_error('Memorial not found');
        }

        $qr_url = Memory_Pages_QR::get_or_create_qr($memorial_id, $memorial['code']);
        wp_send_json_success(array('qr_url' => $qr_url));
    }

    public static function ajax_update_request_status() {
        check_ajax_referer('mp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No permission');
        }
        global $wpdb;

        $req_id = intval($_POST['request_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'new');

        $table = $wpdb->prefix . 'memorial_requests';
        $wpdb->update($table, array('status' => $status), array('id' => $req_id));

        wp_send_json_success();
    }
}
