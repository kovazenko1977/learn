<?php
/**
 * Plugin Name: Premium Chatbot Berezina
 * Plugin URI: https://wes.by
 * Description: Интерактивный премиум чат-бот для санатория «Березина» (gu-berezina.by). Разработано Kovazhenko S.B. / WES.BY.
 * Version: 4.0.0
 * Author: Kovazhenko S.B.
 * Author URI: https://wes.by
 * License: GPLv2 or later
 * Text Domain: berezina-chatbot
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'includes/Storage.php';

// Constants
define('BEREZINA_CHATBOT_VERSION', '4.0.0');
define('BEREZINA_CHATBOT_URL', plugin_dir_url(__FILE__));
define('BEREZINA_CHATBOT_PATH', plugin_dir_path(__FILE__));

// Initialize Hooks
add_action('admin_menu', 'berezina_chatbot_add_admin_menu');
add_action('wp_enqueue_scripts', 'berezina_chatbot_enqueue_assets');
add_action('admin_enqueue_scripts', 'berezina_chatbot_enqueue_admin_assets');
add_action('wp_footer', 'berezina_chatbot_render_widget_on_footer');
add_shortcode('berezina_chatbot', 'berezina_chatbot_shortcode_callback');

// Register AJAX hooks
add_action('wp_ajax_berezina_chatbot_message', 'berezina_chatbot_ajax_message_handler');
add_action('wp_ajax_nopriv_berezina_chatbot_message', 'berezina_chatbot_ajax_message_handler');

add_action('wp_ajax_berezina_chatbot_save_settings', 'berezina_chatbot_ajax_save_settings');
add_action('wp_ajax_berezina_chatbot_get_data', 'berezina_chatbot_ajax_get_data');
add_action('wp_ajax_berezina_chatbot_upload_file', 'berezina_chatbot_ajax_upload_file');
add_action('wp_ajax_berezina_chatbot_delete_file', 'berezina_chatbot_ajax_delete_file');
add_action('wp_ajax_berezina_chatbot_clear_history', 'berezina_chatbot_ajax_clear_history');
add_action('wp_ajax_berezina_chatbot_delete_history_item', 'berezina_chatbot_ajax_delete_history_item');
add_action('wp_ajax_berezina_chatbot_bulk_add_qa', 'berezina_chatbot_ajax_bulk_add_qa');

/**
 * Register Admin Menu Page
 */
function berezina_chatbot_add_admin_menu() {
    add_menu_page(
        'Чат-бот Березина', // Page Title
        'Березина Бот',     // Menu Title
        'manage_options',    // Capability
        'berezina-chatbot',  // Menu Slug
        'berezina_chatbot_admin_page_callback', // Callback
        'dashicons-feedback', // Icon
        30                   // Position
    );
}

/**
 * Render Admin Page Template
 */
function berezina_chatbot_admin_page_callback() {
    if (!current_user_can('manage_options')) {
        return;
    }
    include BEREZINA_CHATBOT_PATH . 'includes/admin-template.php';
}

/**
 * Enqueue Frontend Assets
 */
function berezina_chatbot_enqueue_assets() {
    wp_enqueue_style('berezina-chatbot-css', BEREZINA_CHATBOT_URL . 'assets/css/chatbot.css', array(), BEREZINA_CHATBOT_VERSION);
    wp_enqueue_script('berezina-chatbot-js', BEREZINA_CHATBOT_URL . 'assets/js/chatbot.js', array(), BEREZINA_CHATBOT_VERSION, true);

    // Pass the WordPress ajax_url to the JavaScript file
    wp_localize_script('berezina-chatbot-js', 'berezinaChatbotConfig', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('berezina-chatbot-nonce')
    ));
}

/**
 * Enqueue Admin Assets
 */
function berezina_chatbot_enqueue_admin_assets($hook) {
    if ($hook !== 'toplevel_page_berezina-chatbot') {
        return;
    }

    // Load standard WordPress scripts and styling
    wp_enqueue_media();

    // Enqueue Vue 3 and Tailwind from CDNs for visual layout inside admin panel
    wp_enqueue_script('vue3', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', false);
    wp_enqueue_style('tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', array(), '2.2.19');

    // Custom Admin Javascript
    wp_enqueue_script('berezina-chatbot-admin-js', BEREZINA_CHATBOT_URL . 'assets/js/admin.js', array('vue3'), BEREZINA_CHATBOT_VERSION, true);

    // Context localization
    wp_localize_script('berezina-chatbot-admin-js', 'berezinaAdminConfig', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('berezina-chatbot-admin-nonce')
    ));
}

/**
 * Render Widget Inline Footer Injection
 */
function berezina_chatbot_render_widget_on_footer() {
    // Render the widget container if not disabled
    ?>
    <div id="berezina-chatbot-widget-wrapper"></div>
    <?php
}

/**
 * Shortcode support
 */
function berezina_chatbot_shortcode_callback() {
    ob_start();
    ?>
    <div id="berezina-chatbot-shortcode-container" class="berezina-chatbot-embedded"></div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX Message Handler for Frontend Chat
 */
function berezina_chatbot_ajax_message_handler() {
    // Don't verify referer strictly to prevent conflict with caching plugins, but validate content
    $settings = Berezina_Chatbot_Storage::read('settings.json');
    $knowledge = Berezina_Chatbot_Storage::read('knowledge.json');

    $rawMessage = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $message = mb_strtolower(trim($rawMessage));

    if (empty($rawMessage)) {
        wp_send_json_error(array('error' => 'No message provided'));
    }

    // Check operating hours
    date_default_timezone_set(isset($settings['working_hours']['timezone']) ? $settings['working_hours']['timezone'] : 'Europe/Moscow');
    $dayOfWeek = date('w'); // 0 (Sun) to 6 (Sat)
    $now = date('H:i');

    $daySchedule = isset($settings['schedule'][$dayOfWeek]) ? $settings['schedule'][$dayOfWeek] : null;

    if ($daySchedule) {
        $isOpen = $daySchedule['enabled'];
        if ($isOpen) {
            if ($now < $daySchedule['start'] || $now > $daySchedule['end']) {
                $isOpen = false;
            }
        }

        if (!$isOpen) {
            wp_send_json(array(
                'answer' => $settings['working_hours']['out_of_hours_message'],
                'is_fallback' => false,
                'show_lead_form' => true
            ));
        }
    }

    // Similarity helper as an anonymous closure to avoid multiple declaration fatal errors in PHP
    $berezina_similarity = function($str1, $str2) {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        if ($len1 === 0 || $len2 === 0) return 0;

        $words1 = preg_split('/\s+/u', $str1);
        $words2 = preg_split('/\s+/u', $str2);

        $intersection = array_intersect($words1, $words2);
        $overlap = count($intersection) / max(count($words1), count($words2)) * 100;

        $chars1 = preg_split('//u', $str1, -1, PREG_SPLIT_NO_EMPTY);
        $chars2 = preg_split('//u', $str2, -1, PREG_SPLIT_NO_EMPTY);
        $char_intersection = array_intersect($chars1, $chars2);
        $char_overlap = count($char_intersection) / max(count($chars1), count($chars2)) * 100;

        return ($overlap * 0.7) + ($char_overlap * 0.3);
    };

    $bestMatch = null;
    $highestScore = 0;

    foreach ($knowledge as $item) {
        foreach ($item['keywords'] as $keyword) {
            $keyword = mb_strtolower($keyword);

            // Exact substring
            if (mb_strpos($message, $keyword) !== false) {
                $score = 90 + (mb_strlen($keyword) / mb_strlen($message) * 10);
                if ($score > $highestScore) {
                    $highestScore = $score;
                    $bestMatch = $item['answer'];
                }
            }

            // Fuzzy match
            $sim = $berezina_similarity($message, $keyword);
            if ($sim > $highestScore) {
                $highestScore = $sim;
                $bestMatch = $item['answer'];
            }
        }
    }

    $threshold = isset($settings['fallback']['threshold']) ? $settings['fallback']['threshold'] : 40;
    $response = array();

    if ($highestScore >= $threshold && $bestMatch) {
        $formId = null;
        if (preg_match('/\[form:([a-zA-Z0-9_-]+)\]/', $bestMatch, $matches)) {
            $formId = $matches[1];
            $bestMatch = str_replace($matches[0], '', $bestMatch);
        }

        $response = array(
            'answer' => trim($bestMatch),
            'score' => $highestScore,
            'is_fallback' => false,
            'form_id' => $formId
        );
    } else {
        $response = array(
            'answer' => $settings['fallback']['message'],
            'score' => $highestScore,
            'is_fallback' => true,
            'button_text' => $settings['fallback']['button_text'],
            'phone' => $settings['contacts']['phone'],
            'show_lead_form' => true
        );
    }

    // Lead capturing notifications
    if (strpos($rawMessage, 'FORM_SUBMISSION') === 0 || strpos($rawMessage, 'LEAD_PHONE') === 0) {
        $notif = isset($settings['notifications']) ? $settings['notifications'] : array();
        $subject = "Новая заявка из чат-бота";
        $body = $rawMessage;

        if (!empty($notif['email']['enabled']) && !empty($notif['email']['address'])) {
            wp_mail($notif['email']['address'], $subject, $body);
        }

        if (!empty($notif['telegram']['enabled']) && !empty($notif['telegram']['token']) && !empty($notif['telegram']['chat_id'])) {
            $token = $notif['telegram']['token'];
            $chat_id = $notif['telegram']['chat_id'];
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $text = "🔔 {$subject}\n\n" . str_replace(array('FORM_SUBMISSION', 'LEAD_PHONE'), '', $body);

            wp_remote_post($url, array(
                'body' => array(
                    'chat_id' => $chat_id,
                    'text' => $text
                ),
                'timeout' => 10
            ));
        }
    }

    // Store dialog log
    $history = Berezina_Chatbot_Storage::read('history.json');
    if (!is_array($history)) {
        $history = array();
    }
    $history[] = array(
        'id' => uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'user_message' => $rawMessage,
        'bot_answer' => $response['answer'],
        'score' => $highestScore,
        'is_fallback' => $response['is_fallback']
    );

    Berezina_Chatbot_Storage::write('history.json', array_slice($history, -1000));

    wp_send_json($response);
}

/**
 * Get WordPress Uploads directory for the chatbot
 */
function berezina_chatbot_get_upload_dir() {
    $wp_upload = wp_upload_dir();
    $base_dir = $wp_upload['basedir'];
    $chatbot_dir = $base_dir . '/berezina-chatbot/';
    if (!is_dir($chatbot_dir)) {
        wp_mkdir_p($chatbot_dir);
    }
    return $chatbot_dir;
}

/**
 * Get WordPress Uploads URL for the chatbot
 */
function berezina_chatbot_get_upload_url() {
    $wp_upload = wp_upload_dir();
    $base_url = $wp_upload['baseurl'];
    return $base_url . '/berezina-chatbot/';
}

/**
 * AJAX Get All Data for Admin Dashboard
 */
function berezina_chatbot_ajax_get_data() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    $settings = Berezina_Chatbot_Storage::read('settings.json') ?: array();
    $knowledge = Berezina_Chatbot_Storage::read('knowledge.json') ?: array();
    $history = Berezina_Chatbot_Storage::read('history.json') ?: array();

    // Fetch uploads from standard WordPress uploads folder
    $uploadDir = berezina_chatbot_get_upload_dir();
    $files = array();
    if (is_dir($uploadDir)) {
        $items = array_diff(scandir($uploadDir), array('.', '..'));
        foreach ($items as $item) {
            if (is_file($uploadDir . $item)) {
                $files[] = array(
                    'name' => $item,
                    'size' => filesize($uploadDir . $item),
                    'date' => date('Y-m-d H:i:s', filemtime($uploadDir . $item))
                );
            }
        }
    }

    wp_send_json_success(array(
        'settings'  => $settings,
        'knowledge' => $knowledge,
        'history'   => $history,
        'uploads'   => $files
    ));
}

/**
 * AJAX Save Settings and Knowledge Base
 */
function berezina_chatbot_ajax_save_settings() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    if (!isset($_POST['settings']) || !isset($_POST['knowledge'])) {
        wp_send_json_error(array('error' => 'Missing data'));
    }

    $settings = json_decode(stripslashes($_POST['settings']), true);
    $knowledge = json_decode(stripslashes($_POST['knowledge']), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array('error' => 'Invalid JSON input data'));
    }

    Berezina_Chatbot_Storage::write('settings.json', $settings);
    Berezina_Chatbot_Storage::write('knowledge.json', $knowledge);

    wp_send_json_success(array('success' => true));
}

/**
 * AJAX Admin File Upload Handler
 */
function berezina_chatbot_ajax_upload_file() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    if (!isset($_FILES['file'])) {
        wp_send_json_error(array('error' => 'No file uploaded'));
    }

    $file = $_FILES['file'];
    $allowedExts = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        wp_send_json_error(array('error' => 'Unsupported file type'));
    }

    if ($file['size'] > 15 * 1024 * 1024) { // 15MB limit
        wp_send_json_error(array('error' => 'File too large (max 15MB)'));
    }

    $uploadDir = berezina_chatbot_get_upload_dir();
    $fileName = uniqid() . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        wp_send_json_success(array(
            'success'   => true,
            'file_url'  => berezina_chatbot_get_upload_url() . $fileName,
            'file_name' => $fileName
        ));
    } else {
        wp_send_json_error(array('error' => 'Failed to store file'));
    }
}

/**
 * AJAX Delete File
 */
function berezina_chatbot_ajax_delete_file() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    if (!isset($_POST['name'])) {
        wp_send_json_error(array('error' => 'No file name provided'));
    }

    $name = basename(sanitize_text_field($_POST['name']));
    $filePath = berezina_chatbot_get_upload_dir() . $name;

    if (file_exists($filePath)) {
        unlink($filePath);
        wp_send_json_success(array('success' => true));
    } else {
        wp_send_json_error(array('error' => 'File not found'));
    }
}

/**
 * AJAX Clear Dialogue Log History
 */
function berezina_chatbot_ajax_clear_history() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    Berezina_Chatbot_Storage::write('history.json', array());
    wp_send_json_success(array('success' => true));
}

/**
 * AJAX Delete Single History Item
 */
function berezina_chatbot_ajax_delete_history_item() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    if (!isset($_POST['id'])) {
        wp_send_json_error(array('error' => 'Missing ID'));
    }

    $id = sanitize_text_field($_POST['id']);
    $history = Berezina_Chatbot_Storage::read('history.json') ?: array();
    $filtered = array();

    foreach ($history as $item) {
        if ($item['id'] !== $id) {
            $filtered[] = $item;
        }
    }

    Berezina_Chatbot_Storage::write('history.json', $filtered);
    wp_send_json_success(array('success' => true));
}

/**
 * AJAX Bulk Add Q&A
 */
function berezina_chatbot_ajax_bulk_add_qa() {
    check_ajax_referer('berezina-chatbot-admin-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('error' => 'Permission denied'));
    }

    if (!isset($_POST['text'])) {
        wp_send_json_error(array('error' => 'Missing text data'));
    }

    $text = stripslashes($_POST['text']);
    $lines = explode("\n", $text);
    $knowledge = Berezina_Chatbot_Storage::read('knowledge.json') ?: array();
    $added = 0;

    foreach ($lines as $line) {
        $parts = explode(';', $line, 2);
        if (count($parts) < 2) continue;
        $keywords_raw = trim($parts[0]);
        $answer = trim($parts[1]);
        if ($keywords_raw === '' || $answer === '') continue;
        $keywords = array_filter(array_map('trim', explode(',', $keywords_raw)));
        if (empty($keywords)) continue;
        $knowledge[] = array(
            'keywords' => array_values($keywords),
            'answer' => $answer
        );
        $added++;
    }

    Berezina_Chatbot_Storage::write('knowledge.json', $knowledge);

    wp_send_json_success(array(
        'success' => true,
        'added' => $added,
        'knowledge' => $knowledge
    ));
}
