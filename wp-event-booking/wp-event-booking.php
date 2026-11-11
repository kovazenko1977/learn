<?php
/**
 * Plugin Name: Онлайн запись на мероприятия (WP Event Booking)
 * Plugin URI: https://example.com/wp-event-booking
 * Description: Профессиональный плагин онлайн-записи на мероприятия с адаптивным каскадным конструктором даты/времени, лимитами вместимости, конструктором форм, защитой админки по PIN-коду и отправкой уведомлений в Telegram и на Email.
 * Version: 2.5.0
 * Author: WP Event Pro Team
 * Author URI: https://example.com
 * Text Domain: wp-event-booking
 * Domain Path: /languages
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

if (!defined('WPEB_VERSION')) {
    define('WPEB_VERSION', '2.5.0');
}
if (!defined('WPEB_PLUGIN_DIR')) {
    define('WPEB_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WPEB_PLUGIN_URL')) {
    define('WPEB_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Require Core Classes
require_once WPEB_PLUGIN_DIR . 'includes/class-database.php';
require_once WPEB_PLUGIN_DIR . 'includes/class-mailer.php';
require_once WPEB_PLUGIN_DIR . 'includes/class-telegram.php';
require_once WPEB_PLUGIN_DIR . 'includes/class-booking-engine.php';
require_once WPEB_PLUGIN_DIR . 'includes/class-admin.php';

class WP_Event_Booking_Plugin {
    private static $instance = null;
    private $db;
    private $engine;
    private $admin;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->db = WPEB_Database::instance();
        $this->engine = new WPEB_Booking_Engine();
        $this->admin = new WPEB_Admin();

        $this->init_hooks();
    }

    private function init_hooks() {
        // Activation & Deactivation
        if (function_exists('register_activation_hook')) {
            register_activation_hook(__FILE__, [$this, 'activate']);
        }

        // Shortcodes
        add_shortcode('event_booking', [$this, 'render_booking_shortcode']);
        add_shortcode('event_booking_catalog', [$this, 'render_catalog_shortcode']);
        add_shortcode('event_booking_lookup', [$this, 'render_lookup_shortcode']);

        // Frontend Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // AJAX Handlers
        add_action('wp_ajax_wpeb_get_months', [$this, 'ajax_get_months']);
        add_action('wp_ajax_nopriv_wpeb_get_months', [$this, 'ajax_get_months']);

        add_action('wp_ajax_wpeb_get_dates', [$this, 'ajax_get_dates']);
        add_action('wp_ajax_nopriv_wpeb_get_dates', [$this, 'ajax_get_dates']);

        add_action('wp_ajax_wpeb_get_slots', [$this, 'ajax_get_slots']);
        add_action('wp_ajax_nopriv_wpeb_get_slots', [$this, 'ajax_get_slots']);

        add_action('wp_ajax_wpeb_submit_booking', [$this, 'ajax_submit_booking']);
        add_action('wp_ajax_nopriv_wpeb_submit_booking', [$this, 'ajax_submit_booking']);

        add_action('wp_ajax_wpeb_lookup_booking', [$this, 'ajax_lookup_booking']);
        add_action('wp_ajax_nopriv_wpeb_lookup_booking', [$this, 'ajax_lookup_booking']);
    }

    public function activate() {
        $this->db->create_tables();
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('wpeb-frontend-css', WPEB_PLUGIN_URL . 'assets/css/frontend.css', [], WPEB_VERSION);
        wp_enqueue_script('wpeb-frontend-js', WPEB_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], WPEB_VERSION, true);

        wp_localize_script('wpeb-frontend-js', 'wpebConfig', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpeb_frontend_nonce')
        ]);
    }

    public function render_booking_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'title' => '',
            'theme' => 'default'
        ], $atts, 'event_booking');

        $eventId = (int)$atts['id'];
        if (!$eventId) {
            $events = $this->db->get_events('active');
            if (!empty($events)) {
                $eventId = (int)$events[0]['id'];
            }
        }

        $event = $this->db->get_event($eventId);
        if (!$event) {
            return '<div class="wpeb-notice wpeb-error">Мероприятие не найдено или снято с публикации.</div>';
        }

        $fields = $this->db->get_form_fields($eventId);
        $settings = $this->db->get_all_settings();

        ob_start();
        include WPEB_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    public function render_catalog_shortcode($atts) {
        $events = $this->db->get_events('active');
        ob_start();
        ?>
        <div class="wpeb-catalog-container">
            <h2 class="wpeb-catalog-title">Доступные мероприятия для онлайн-записи</h2>
            <div class="wpeb-catalog-grid">
                <?php foreach ($events as $ev): ?>
                    <div class="wpeb-catalog-card">
                        <div class="wpeb-card-header">
                            <h3><?php echo esc_html($ev['title']); ?></h3>
                            <span class="wpeb-card-price"><?php echo $ev['price'] > 0 ? esc_html($ev['price'] . ' ' . $ev['currency']) : 'Бесплатно'; ?></span>
                        </div>
                        <p class="wpeb-card-desc"><?php echo esc_html(wp_trim_words($ev['description'], 20)); ?></p>
                        <?php if (!empty($ev['location'])): ?>
                            <div class="wpeb-card-loc">📍 <?php echo esc_html($ev['location']); ?></div>
                        <?php endif; ?>
                        <div class="wpeb-card-action">
                            <a href="#book-event-<?php echo $ev['id']; ?>" class="wpeb-btn wpeb-btn-primary" onclick="window.wpebSelectEvent && window.wpebSelectEvent(<?php echo $ev['id']; ?>)">Записаться онлайн</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_lookup_shortcode() {
        ob_start();
        ?>
        <div class="wpeb-lookup-box">
            <h3>Проверить статус бронирования</h3>
            <p>Введите ваш 8-значный номер бронирования, чтобы узнать статус или скачать посадочный талон.</p>
            <div class="wpeb-lookup-input-group">
                <input type="text" id="wpebLookupCode" placeholder="Например: A1B2C3D4" maxlength="12" />
                <button type="button" class="wpeb-btn wpeb-btn-primary" id="wpebLookupBtn">Проверить</button>
            </div>
            <div id="wpebLookupResult" style="margin-top: 15px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    // AJAX Endpoint: Get months with available slots
    public function ajax_get_months() {
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)($_POST['event_id'] ?? 0);
        $months = $this->engine->get_event_months($eventId);
        wp_send_json_success(['months' => $months]);
    }

    // AJAX Endpoint: Get dates in chosen month
    public function ajax_get_dates() {
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)($_POST['event_id'] ?? 0);
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)($_POST['year'] ?? 0);
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)($_POST['month'] ?? 0);

        $dates = $this->engine->get_event_dates($eventId, $year, $month);
        wp_send_json_success(['dates' => $dates]);
    }

    // AJAX Endpoint: Get slots in chosen date
    public function ajax_get_slots() {
        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)($_POST['event_id'] ?? 0);
        $dateStr = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : sanitize_text_field($_POST['date'] ?? '');

        $slots = $this->engine->get_event_time_slots($eventId, $dateStr);
        wp_send_json_success(['slots' => $slots]);
    }

    // AJAX Endpoint: Submit Booking
    public function ajax_submit_booking() {
        $data = [
            'event_id' => (int)($_POST['event_id'] ?? 0),
            'slot_id' => (int)($_POST['slot_id'] ?? 0),
            'client_name' => sanitize_text_field($_POST['client_name'] ?? ''),
            'client_email' => sanitize_email($_POST['client_email'] ?? ''),
            'client_phone' => sanitize_text_field($_POST['client_phone'] ?? ''),
            'ticket_count' => max(1, (int)($_POST['ticket_count'] ?? 1)),
            'custom_fields' => $_POST['custom_fields'] ?? []
        ];

        $res = $this->engine->create_booking($data);
        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    // AJAX Endpoint: Lookup Booking
    public function ajax_lookup_booking() {
        $code = sanitize_text_field($_GET['code'] ?? ($_POST['code'] ?? ''));
        if (empty($code)) {
            wp_send_json_error(['error' => 'Укажите код бронирования']);
        }

        $booking = $this->db->get_booking_by_code($code);
        if (!$booking) {
            wp_send_json_error(['error' => 'Запись с таким кодом не найдена']);
        }

        wp_send_json_success(['booking' => $booking]);
    }
}

// Instantiate plugin
WP_Event_Booking_Plugin::instance();
