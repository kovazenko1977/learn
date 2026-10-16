<?php
if (!defined('ABSPATH')) {
    exit;
}

// 1. AJAX: Candle Lighting
add_action('wp_ajax_mp_light_candle', 'mp_ajax_light_candle');
add_action('wp_ajax_nopriv_mp_light_candle', 'mp_ajax_light_candle');

function mp_ajax_light_candle() {
    check_ajax_referer('memory_pages_nonce', 'nonce');
    global $wpdb;

    $page_id = intval($_POST['page_id']);
    $table_candles = $wpdb->prefix . 'memorial_candles';

    $wpdb->insert($table_candles, array(
        'page_id' => $page_id,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
    ));

    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_candles WHERE page_id = %d", $page_id));

    wp_send_json_success(array('count' => intval($count)));
}

// 2. AJAX: Add Condolence
add_action('wp_ajax_mp_add_condolence', 'mp_ajax_add_condolence');
add_action('wp_ajax_nopriv_mp_add_condolence', 'mp_ajax_add_condolence');

function mp_ajax_add_condolence() {
    check_ajax_referer('memory_pages_nonce', 'nonce');
    global $wpdb;

    $page_id = intval($_POST['page_id']);
    $author_name = sanitize_text_field($_POST['author_name']);
    $message = sanitize_textarea_field($_POST['message']);

    if (empty($author_name) || empty($message)) {
        wp_send_json_error('Пожалуйста, заполните все поля.');
    }

    $table_condolences = $wpdb->prefix . 'memorial_condolences';
    $wpdb->insert($table_condolences, array(
        'page_id' => $page_id,
        'author_name' => $author_name,
        'message' => $message
    ));

    wp_send_json_success();
}
