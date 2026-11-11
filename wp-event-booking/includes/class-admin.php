<?php
/**
 * Admin Panel & Security Gate
 * Features:
 * - PIN Code authentication (default 222120, changeable in settings)
 * - Events & Time slots manager with maximum capacity configuration
 * - Bookings Manager with CSV export and Check-In toggle
 * - Visual Form Builder (Конструктор форм)
 * - Email & Telegram Bot settings with instant test trigger
 * - Appearance & Mobile Customizer (colors, borders, dark mode, custom CSS)
 * - 40 Documented Features Showcase
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

class WPEB_Admin {
    private $db;

    public function __construct() {
        $this->db = WPEB_Database::instance();

        if (function_exists('add_action')) {
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('wp_ajax_wpeb_save_form_builder', [$this, 'ajax_save_form_builder']);
            add_action('wp_ajax_wpeb_duplicate_form', [$this, 'ajax_duplicate_form']);
            add_action('wp_ajax_wpeb_delete_form', [$this, 'ajax_delete_form']);
            add_action('wp_ajax_wpeb_create_form', [$this, 'ajax_create_form']);
            add_action('wp_ajax_wpeb_test_telegram', [$this, 'ajax_test_telegram']);
        }
    }

    public function register_admin_menu() {
        add_menu_page(
            'Запись на мероприятия',
            'Запись онлайн',
            'manage_options',
            'wp-event-booking',
            [$this, 'render_admin_page'],
            'dashicons-calendar-alt',
            26
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wp-event-booking') === false) {
            return;
        }

        wp_enqueue_style('wpeb-admin-css', WPEB_PLUGIN_URL . 'assets/css/admin.css', [], WPEB_VERSION);
        wp_enqueue_script('wpeb-admin-js', WPEB_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], WPEB_VERSION, true);

        wp_localize_script('wpeb-admin-js', 'wpebAdminConfig', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    public function is_authenticated() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // Check session or cookie
        if (!empty($_SESSION['wpeb_admin_authenticated'])) {
            return true;
        }

        if (!empty($_COOKIE['wpeb_admin_token'])) {
            $expected = md5($this->db->get_setting('admin_pin', '222120') . 'WPEB_SALT');
            if ($_COOKIE['wpeb_admin_token'] === $expected) {
                $_SESSION['wpeb_admin_authenticated'] = true;
                return true;
            }
        }

        return false;
    }

    public function render_admin_page() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // Handle PIN unlock
        $pinError = '';
        if (isset($_POST['wpeb_action']) && $_POST['wpeb_action'] === 'unlock_pin') {
            $enteredPin = trim($_POST['wpeb_pin'] ?? '');
            $currentPin = $this->db->get_setting('admin_pin', '222120');

            if ($enteredPin === $currentPin) {
                $_SESSION['wpeb_admin_authenticated'] = true;
                $token = md5($currentPin . 'WPEB_SALT');
                @setcookie('wpeb_admin_token', $token, time() + 86400 * 7, '/');
            } else {
                $pinError = 'Неверный PIN-код доступа! Попробуйте снова.';
            }
        }

        // Handle Logout
        if (isset($_GET['wpeb_logout'])) {
            unset($_SESSION['wpeb_admin_authenticated']);
            @setcookie('wpeb_admin_token', '', time() - 3600, '/');
            header('Location: ' . remove_query_arg('wpeb_logout'));
            exit;
        }

        // If not authenticated, render PIN Lock Screen
        if (!$this->is_authenticated()) {
            $this->render_pin_gate($pinError);
            return;
        }

        // Handle form submits
        $this->handle_admin_actions();

        $events = $this->db->get_events();
        $selectedEventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (!empty($events) ? (int)$events[0]['id'] : 0);
        $slots = $selectedEventId ? $this->db->get_slots_by_event($selectedEventId) : [];
        $bookings = $this->db->get_bookings($selectedEventId ? ['event_id' => $selectedEventId] : []);

        $forms = $this->db->get_forms();
        $selectedFormId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : (!empty($forms) ? (int)$forms[0]['id'] : 0);
        $activeForm = $selectedFormId ? $this->db->get_form($selectedFormId) : (!empty($forms) ? $forms[0] : null);

        $settings = $this->db->get_all_settings();
        $analytics = $this->db->get_analytics();

        include WPEB_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    private function render_pin_gate($error = '') {
        ?>
        <div class="wpeb-pin-screen">
            <div style="font-size: 40px; margin-bottom: 12px;">🔒</div>
            <h2 class="wpeb-pin-title">Доступ к панели управления</h2>
            <p class="wpeb-pin-desc">Для входа в настройки онлайн-записи введите PIN-код администратора.</p>

            <?php if (!empty($error)): ?>
                <div style="background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:10px; border-radius:8px; margin-bottom:16px; font-size:14px;">
                    <?php echo esc_html($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="wpeb_action" value="unlock_pin" />
                <input type="password" name="wpeb_pin" class="wpeb-pin-input" placeholder="••••••" maxlength="10" autofocus required />
                <button type="submit" class="button button-primary button-hero" style="width: 100%;">Войти в панель</button>
            </form>
        </div>
        <?php
    }

    private function handle_admin_actions() {
        if (!isset($_POST['wpeb_save_settings'])) return;

        // Save Settings
        if (isset($_POST['admin_pin']) && !empty($_POST['admin_pin'])) {
            $this->db->update_setting('admin_pin', sanitize_text_field($_POST['admin_pin']));
            $token = md5(sanitize_text_field($_POST['admin_pin']) . 'WPEB_SALT');
            @setcookie('wpeb_admin_token', $token, time() + 86400 * 7, '/');
        }

        if (isset($_POST['telegram_token'])) {
            $this->db->update_setting('telegram_token', sanitize_text_field($_POST['telegram_token']));
            $this->db->update_setting('telegram_chat_id', sanitize_text_field($_POST['telegram_chat_id'] ?? ''));
            $this->db->update_setting('telegram_enabled', isset($_POST['telegram_enabled']) ? '1' : '0');
            $this->db->update_setting('telegram_template', sanitize_textarea_field($_POST['telegram_template'] ?? ''));
        }

        if (isset($_POST['email_to'])) {
            $this->db->update_setting('email_to', sanitize_email($_POST['email_to']));
            $this->db->update_setting('email_from_name', sanitize_text_field($_POST['email_from_name'] ?? ''));
            $this->db->update_setting('email_subject', sanitize_text_field($_POST['email_subject'] ?? ''));
            $this->db->update_setting('email_template', sanitize_textarea_field($_POST['email_template'] ?? ''));
            $this->db->update_setting('email_enabled', isset($_POST['email_enabled']) ? '1' : '0');
        }

        if (isset($_POST['primary_color'])) {
            $this->db->update_setting('primary_color', sanitize_hex_color($_POST['primary_color'] ?? '#3b82f6'));
            $this->db->update_setting('border_radius', sanitize_text_field($_POST['border_radius'] ?? '12'));
            $this->db->update_setting('custom_css', wp_strip_all_tags($_POST['custom_css'] ?? ''));
        }

        // Add New Slot Batch
        if (isset($_POST['add_slot_date']) && !empty($_POST['add_slot_date'])) {
            $date = sanitize_text_field($_POST['add_slot_date']);
            $time = sanitize_text_field($_POST['add_slot_time'] ?? '12:00');
            $capacity = max(1, (int)($_POST['add_slot_capacity'] ?? 10));
            $eventId = (int)($_POST['event_id'] ?? 1);

            $ts = strtotime($date);
            $year = (int)date('Y', $ts);
            $month = (int)date('n', $ts);

            $this->db->insert_slot([
                'event_id' => $eventId,
                'slot_year' => $year,
                'slot_month' => $month,
                'slot_date' => $date,
                'slot_time' => $time,
                'max_capacity' => $capacity,
                'booked_count' => 0,
                'is_active' => 1
            ]);
        }
    }

    public function ajax_save_form_builder() {
        $formId = (int)($_POST['form_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $raw = stripslashes($_POST['fields_json'] ?? '[]');
        $fields = json_decode($raw, true);

        if (!is_array($fields)) {
            wp_send_json_error(['error' => 'Некорректный формат данных полей']);
        }

        if ($formId > 0) {
            $data = ['fields_json' => $fields];
            if (!empty($title)) $data['title'] = $title;
            if ($description !== '') $data['description'] = $description;
            $this->db->update_form($formId, $data);
            wp_send_json_success(['message' => 'Форма успешно сохранена!', 'form_id' => $formId]);
        } else {
            $newId = $this->db->insert_form([
                'title' => !empty($title) ? $title : 'Новая форма записи',
                'description' => $description,
                'fields_json' => $fields,
                'status' => 'active'
            ]);
            wp_send_json_success(['message' => 'Новая форма создана!', 'form_id' => $newId]);
        }
    }

    public function ajax_duplicate_form() {
        $formId = (int)($_POST['form_id'] ?? 0);
        if (!$formId) {
            wp_send_json_error(['error' => 'Не указан ID формы']);
        }

        $newId = $this->db->duplicate_form($formId);
        if ($newId) {
            wp_send_json_success(['message' => 'Форма успешно сдублирована!', 'new_id' => $newId]);
        } else {
            wp_send_json_error(['error' => 'Не удалось сдублировать форму']);
        }
    }

    public function ajax_delete_form() {
        $formId = (int)($_POST['form_id'] ?? 0);
        if (!$formId) {
            wp_send_json_error(['error' => 'Не указан ID формы']);
        }

        $this->db->delete_form($formId);
        wp_send_json_success(['message' => 'Форма удалена']);
    }

    public function ajax_create_form() {
        $title = sanitize_text_field($_POST['title'] ?? 'Новая форма');
        $slug = sanitize_text_field($_POST['slug'] ?? ('form-' . substr(md5(uniqid()), 0, 6)));
        $templateKey = sanitize_text_field($_POST['template'] ?? '');

        $defaultFields = [
            ['field_key' => 'organization', 'field_label' => 'Организация / Компания', 'field_type' => 'text', 'placeholder' => '', 'is_required' => 0, 'width' => '50'],
            ['field_key' => 'comment', 'field_label' => 'Комментарий к записи', 'field_type' => 'textarea', 'placeholder' => '', 'is_required' => 0, 'width' => '100']
        ];

        $newId = $this->db->insert_form([
            'title' => $title,
            'slug' => $slug,
            'description' => '',
            'fields_json' => $defaultFields,
            'status' => 'active'
        ]);

        wp_send_json_success(['message' => 'Форма создана', 'form_id' => $newId]);
    }

    public function ajax_test_telegram() {
        $token = sanitize_text_field($_POST['token'] ?? '');
        $chatId = sanitize_text_field($_POST['chat_id'] ?? '');

        $testMsg = "🚀 <b>Тестовое уведомление WP Event Booking</b>\nСвязь с Telegram настроена успешно!\nВремя: " . date('d.m.Y H:i:s');
        $res = WPEB_Telegram::send_message($token, $chatId, $testMsg);

        if ($res['success']) {
            wp_send_json_success(['message' => 'Тестовое сообщение успешно доставлено!']);
        } else {
            wp_send_json_error(['error' => $res['error'] ?? 'Не удалось доставить сообщение']);
        }
    }
}
