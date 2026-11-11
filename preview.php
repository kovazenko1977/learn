<?php
/**
 * Standalone WordPress Emulation & Preview Server
 * Provides full WordPress environment simulation to view and test the plugin,
 * shortcodes, AJAX actions, and PIN-protected admin panel in the sandbox.
 */

define('WPEB_STANDALONE', true);

// WordPress mock helper functions
if (!function_exists('add_shortcode')) {
    $GLOBALS['_wpeb_shortcodes'] = [];
    $GLOBALS['_wpeb_actions'] = [];

    function add_shortcode($tag, $callback) {
        $GLOBALS['_wpeb_shortcodes'][$tag] = $callback;
    }

    function do_shortcode($content) {
        foreach ($GLOBALS['_wpeb_shortcodes'] as $tag => $cb) {
            $pattern = '/\[' . $tag . '([^\]]*)\]/';
            $content = preg_replace_callback($pattern, function($matches) use ($cb) {
                $rawAtts = trim($matches[1]);
                $atts = [];
                if (!empty($rawAtts)) {
                    preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $rawAtts, $m, PREG_SET_ORDER);
                    foreach ($m as $item) {
                        $atts[$item[1]] = $item[2];
                    }
                }
                return call_user_func($cb, $atts);
            }, $content);
        }
        return $content;
    }

    function shortcode_atts($pairs, $atts, $shortcode = '') {
        $out = [];
        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, (array)$atts) ? $atts[$name] : $default;
        }
        return $out;
    }

    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['_wpeb_actions'][$tag][] = $callback;
    }

    function do_action($tag, ...$args) {
        if (!empty($GLOBALS['_wpeb_actions'][$tag])) {
            foreach ($GLOBALS['_wpeb_actions'][$tag] as $cb) {
                call_user_func_array($cb, $args);
            }
        }
    }

    function esc_html($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function esc_attr($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function esc_textarea($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
    function sanitize_text_field($str) { return strip_tags(trim((string)$str)); }
    function sanitize_email($email) { return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL); }
    function sanitize_hex_color($color) { return preg_match('/^#[a-f0-9]{3,6}$/i', $color) ? $color : '#3b82f6'; }
    function sanitize_textarea_field($str) { return strip_tags(trim((string)$str)); }
    function wp_strip_all_tags($str) { return strip_tags($str); }
    function wp_trim_words($text, $num_words = 55) {
        $words = explode(' ', strip_tags($text));
        if (count($words) > $num_words) {
            return implode(' ', array_slice($words, 0, $num_words)) . '...';
        }
        return $text;
    }

    function checked($checked, $current = true, $echo = true) {
        $res = ($checked == $current) ? ' checked="checked"' : '';
        if ($echo) echo $res;
        return $res;
    }

    function selected($selected, $current = true, $echo = true) {
        $res = ($selected == $current) ? ' selected="selected"' : '';
        if ($echo) echo $res;
        return $res;
    }

    function wp_send_json_success($data = null) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    function wp_send_json_error($data = null) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    function plugin_dir_path($file) { return dirname($file) . '/'; }
    function plugin_dir_url($file) { return '/wp-event-booking/'; }
    function admin_url($path = '') { return '/preview.php?admin_ajax=1'; }
    function get_option($opt, $default = '') { return $default; }
    function get_bloginfo($show = '') { return 'Онлайн Запись на Мероприятия'; }
    function remove_query_arg($key) { return '/admin.php'; }
}

// Load Plugin Core
require_once __DIR__ . '/wp-event-booking/wp-event-booking.php';

// Handle standalone AJAX requests
if (isset($_GET['ajax']) || isset($_GET['admin_ajax']) || (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'wpeb_') === 0)) {
    $action = $_REQUEST['action'] ?? '';
    $plugin = WP_Event_Booking_Plugin::instance();
    $admin = new WPEB_Admin();

    if ($action === 'wpeb_get_months') {
        $plugin->ajax_get_months();
    } elseif ($action === 'wpeb_get_dates') {
        $plugin->ajax_get_dates();
    } elseif ($action === 'wpeb_get_slots') {
        $plugin->ajax_get_slots();
    } elseif ($action === 'wpeb_submit_booking') {
        $plugin->ajax_submit_booking();
    } elseif ($action === 'wpeb_lookup_booking') {
        $plugin->ajax_lookup_booking();
    } elseif ($action === 'wpeb_save_form_builder') {
        $admin->ajax_save_form_builder();
    } elseif ($action === 'wpeb_duplicate_form') {
        $admin->ajax_duplicate_form();
    } elseif ($action === 'wpeb_delete_form') {
        $admin->ajax_delete_form();
    } elseif ($action === 'wpeb_create_form') {
        $admin->ajax_create_form();
    } elseif ($action === 'wpeb_test_telegram') {
        $admin->ajax_test_telegram();
    }
    exit;
}

// Check route
$page = $_GET['page'] ?? 'booking';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Event Booking — Онлайн запись на мероприятия (WordPress Plugin)</title>
    <!-- Plugin Assets -->
    <link rel="stylesheet" href="/wp-event-booking/assets/css/frontend.css">
    <link rel="stylesheet" href="/wp-event-booking/assets/css/admin.css">
    <style>
        body {
            margin: 0;
            background: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .wp-site-header {
            background: #1e293b;
            color: #ffffff;
            padding: 12px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
        }
        .wp-site-brand {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .wp-site-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .wp-site-nav a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .wp-site-nav a:hover,
        .wp-site-nav a.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }
        .wp-page-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 16px;
        }
    </style>
    <script>
        window.wpebConfig = {
            ajax_url: '/preview.php?ajax=1'
        };
        window.wpebAdminConfig = {
            ajax_url: '/preview.php?admin_ajax=1'
        };
    </script>
</head>
<body>
    <header class="wp-site-header">
        <div class="wp-site-brand">
            <span>🎟 WordPress Event Booking</span>
        </div>
        <nav class="wp-site-nav">
            <a href="/preview.php?page=booking" class="<?php echo $page === 'booking' ? 'active' : ''; ?>">Стандартная форма [event_booking]</a>
            <a href="/preview.php?page=vip_form" class="<?php echo $page === 'vip_form' ? 'active' : ''; ?>">VIP-форма [event_booking form="vip-business-session"]</a>
            <a href="/preview.php?page=catalog" class="<?php echo $page === 'catalog' ? 'active' : ''; ?>">Каталог [event_booking_catalog]</a>
            <a href="/preview.php?page=lookup" class="<?php echo $page === 'lookup' ? 'active' : ''; ?>">Проверка брони [event_booking_lookup]</a>
            <a href="/preview.php?page=admin" class="<?php echo $page === 'admin' ? 'active' : ''; ?>" style="background:#3b82f6; color:#ffffff;">🔒 Админка (PIN: 222120)</a>
        </nav>
    </header>

    <main class="wp-page-container">
        <?php if ($page === 'booking'): ?>
            <div style="text-align:center; margin-bottom: 24px;">
                <h1 style="margin-bottom:8px; font-size:26px;">Демонстрация шорткода <code>[event_booking]</code></h1>
                <p style="color:#64748b;">Каскадный выбор месяца, даты, свободных сеансов и оформление билета</p>
            </div>
            <?php echo do_shortcode('[event_booking]'); ?>

        <?php elseif ($page === 'vip_form'): ?>
            <div style="text-align:center; margin-bottom: 24px;">
                <h1 style="margin-bottom:8px; font-size:26px;">Демонстрация VIP-формы <code>[event_booking form="vip-business-session"]</code></h1>
                <p style="color:#64748b;">Форма с выбором категории билета, диеты питания, трансфера и контактов ассистента</p>
            </div>
            <?php echo do_shortcode('[event_booking form="vip-business-session"]'); ?>

        <?php elseif ($page === 'catalog'): ?>
            <?php echo do_shortcode('[event_booking_catalog]'); ?>

        <?php elseif ($page === 'lookup'): ?>
            <?php echo do_shortcode('[event_booking_lookup]'); ?>

        <?php elseif ($page === 'admin'): ?>
            <?php
            $admin = new WPEB_Admin();
            $admin->render_admin_page();
            ?>
        <?php endif; ?>
    </main>

    <script src="/wp-event-booking/assets/js/frontend.js"></script>
    <script src="/wp-event-booking/assets/js/admin.js"></script>
</body>
</html>
