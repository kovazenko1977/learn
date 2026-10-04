<?php
/**
 * Plugin Name: Страницы Памяти — Цифровые Мемориалы & Elementor
 * Plugin URI: https://memory-pages.ru
 * Description: Полноценная плагин-платформа для WordPress и Elementor. Создание цифровых страниц памяти с фотогалереей (до 10 фото), биографией, контактами родственников, точными GPS-координатами захоронения, постоянными QR-кодами для ритуальных табличек, личным кабинетом (вход по телефону), премодерацией администратора и множеством Elementor шорткодов.
 * Version: 2.0.0
 * Author: Коваженко С.Б.
 * Text Domain: memory-pages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MEMORY_PAGES_PATH', plugin_dir_path(__FILE__));
define('MEMORY_PAGES_URL', plugin_dir_url(__FILE__));
define('MEMORY_PAGES_VERSION', '2.0.0');

// Activation & Deactivation Hooks
register_activation_hook(__FILE__, 'memory_pages_activate');
register_deactivation_hook(__FILE__, 'memory_pages_deactivate');

function memory_pages_activate() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // 1. Pages Table
    $table_pages = $wpdb->prefix . 'memorial_pages';
    $sql_pages = "CREATE TABLE $table_pages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT 0,
        full_name varchar(255) NOT NULL,
        birth_date varchar(50) DEFAULT '',
        death_date varchar(50) DEFAULT '',
        main_photo varchar(500) DEFAULT '',
        biography text DEFAULT '',
        burial_location varchar(255) DEFAULT '',
        burial_latitude varchar(50) DEFAULT '',
        burial_longitude varchar(50) DEFAULT '',
        status varchar(50) DEFAULT 'pending',
        rejection_reason text DEFAULT '',
        slug varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";
    dbDelta($sql_pages);

    // 2. Photos Table
    $table_photos = $wpdb->prefix . 'memorial_photos';
    $sql_photos = "CREATE TABLE $table_photos (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        photo_url varchar(500) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_photos);

    // 3. Relatives Table
    $table_relatives = $wpdb->prefix . 'memorial_relatives';
    $sql_relatives = "CREATE TABLE $table_relatives (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        relation varchar(100) DEFAULT '',
        phone varchar(50) DEFAULT '',
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_relatives);

    // 4. Condolences Table
    $table_condolences = $wpdb->prefix . 'memorial_condolences';
    $sql_condolences = "CREATE TABLE $table_condolences (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        author_name varchar(255) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_condolences);

    // 5. Candles Table
    $table_candles = $wpdb->prefix . 'memorial_candles';
    $sql_candles = "CREATE TABLE $table_candles (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_id bigint(20) NOT NULL,
        ip_address varchar(50) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_candles);

    // 6. Users Table
    $table_users = $wpdb->prefix . 'memorial_users';
    $sql_users = "CREATE TABLE $table_users (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        phone varchar(50) NOT NULL,
        password_hash varchar(255) NOT NULL,
        full_name varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY phone (phone)
    ) $charset_collate;";
    dbDelta($sql_users);

    // Add Rewrite Rule for /memory/SLUG/
    add_rewrite_rule('^memory/([^/]+)/?$', 'index.php?memorial_slug=$matches[1]', 'top');
    flush_rewrite_rules();
}

function memory_pages_deactivate() {
    flush_rewrite_rules();
}

// Register Query Var for Rewrite Rule
add_filter('query_vars', function($vars) {
    $vars[] = 'memorial_slug';
    return $vars;
});

// Template Include for Single Memorial Page
add_action('template_redirect', function() {
    $slug = get_query_var('memorial_slug');
    if ($slug) {
        include(MEMORY_PAGES_PATH . 'templates/single-memorial.php');
        exit;
    }
});

// Enqueue Enqueued Assets
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('memory-pages-public-css', MEMORY_PAGES_URL . 'assets/css/public.css', array(), MEMORY_PAGES_VERSION);
    wp_enqueue_script('qrcode-js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', array(), '1.0.0', true);
    wp_enqueue_script('memory-pages-public-js', MEMORY_PAGES_URL . 'assets/js/public.js', array('jquery'), MEMORY_PAGES_VERSION, true);
    wp_localize_script('memory-pages-public-js', 'MemoryPagesObj', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('memory_pages_nonce')
    ));
});

// Load Includes
require_once(MEMORY_PAGES_PATH . 'includes/admin-menu.php');
require_once(MEMORY_PAGES_PATH . 'includes/ajax-handlers.php');
require_once(MEMORY_PAGES_PATH . 'includes/elementor-widgets.php');
