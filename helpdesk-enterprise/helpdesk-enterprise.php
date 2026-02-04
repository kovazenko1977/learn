<?php
/**
 * Plugin Name: Helpdesk Enterprise
 * Description: Корпоративная система управления заявками с SLA и интеграцией с Telegram.
 * Version: 6.0
 * Author: Kovazenko S.B.
 * Text Domain: helpdesk-enterprise
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HD_PATH', plugin_dir_path(__FILE__));
define('HD_URL', plugin_dir_url(__FILE__));

class HelpdeskEnterprise {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once HD_PATH . 'includes/class-hd-db.php';
        require_once HD_PATH . 'includes/class-hd-auth.php';
        require_once HD_PATH . 'includes/class-hd-roles.php';
        require_once HD_PATH . 'includes/class-hd-sla.php';
        require_once HD_PATH . 'includes/class-hd-request-manager.php';
        require_once HD_PATH . 'includes/class-hd-admin.php';
        require_once HD_PATH . 'includes/class-hd-dashboard.php';
        require_once HD_PATH . 'includes/class-hd-telegram.php';
        require_once HD_PATH . 'includes/class-hd-export.php';
        require_once HD_PATH . 'includes/class-hd-api.php';
    }

    private function init_hooks() {
        add_action('init', array('HD_Auth', 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        HD_DB::create_tables();
        HD_Roles::init();
        $this->ensure_default_admin();
    }

    private function ensure_default_admin() {
        global $wpdb;
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hd_users");
        if (!$exists) {
            $wpdb->insert("{$wpdb->prefix}hd_users", array(
                'username' => 'admin',
                'password' => password_hash('admin', PASSWORD_DEFAULT),
                'phone' => '80000000000',
                'display_name' => 'System Admin',
                'role' => 'hd_administrator',
                'api_token' => wp_generate_password(32, false)
            ));
        }
    }
}

function HD() {
    return HelpdeskEnterprise::get_instance();
}

HD();
