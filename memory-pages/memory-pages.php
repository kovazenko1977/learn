<?php
/**
 * Plugin Name: Страницы памяти (Memory Pages)
 * Plugin URI:  https://github.com/memorial/memory-pages
 * Description: Полнофункциональная система создания и управления цифровыми мемориальными страницами памяти с интеграцией QR, родственников, визуальных шаблонов и Elementor.
 * Version:     1.0.0
 * Author:      Memory Pages Team
 * Text Domain: memory-pages
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin Constants
define('MEMORY_PAGES_VERSION', '1.0.0');
define('MEMORY_PAGES_FILE', __FILE__);
define('MEMORY_PAGES_PATH', plugin_dir_path(__FILE__));
define('MEMORY_PAGES_URL', plugin_dir_url(__FILE__));
define('MEMORY_PAGES_SLUG', 'memory-pages');

/**
 * Main Memory Pages Plugin Class
 */
final class Memory_Pages {

    /**
     * Single instance of the class
     * @var Memory_Pages
     */
    private static $instance = null;

    /**
     * Get main instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once MEMORY_PAGES_PATH . 'includes/class-db.php';
        require_once MEMORY_PAGES_PATH . 'includes/class-memorial.php';
        require_once MEMORY_PAGES_PATH . 'includes/class-rewrites.php';
        require_once MEMORY_PAGES_PATH . 'includes/class-qr.php';
        require_once MEMORY_PAGES_PATH . 'includes/class-stats.php';

        if (is_admin()) {
            require_once MEMORY_PAGES_PATH . 'admin/class-admin.php';
        }

        require_once MEMORY_PAGES_PATH . 'public/class-public.php';
        require_once MEMORY_PAGES_PATH . 'includes/class-elementor.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(MEMORY_PAGES_FILE, array($this, 'activate'));
        register_deactivation_hook(MEMORY_PAGES_FILE, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        Memory_Pages_DB::create_tables();

        // Flush rewrite rules on activation
        Memory_Pages_Rewrites::add_rewrite_rules();
        flush_rewrite_rules();

        // Save plugin version
        update_option('memory_pages_version', MEMORY_PAGES_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin functionality
     */
    public function init() {
        // Boot database component
        Memory_Pages_DB::init();

        // Boot rewrite component
        Memory_Pages_Rewrites::init();

        // Boot public component
        Memory_Pages_Public::init();

        // Boot admin component if in admin
        if (is_admin()) {
            Memory_Pages_Admin::init();
        }

        // Boot Elementor integration
        Memory_Pages_Elementor::init();
    }
}

/**
 * Initialize main plugin instance
 */
function memory_pages() {
    return Memory_Pages::get_instance();
}

// Start Plugin
memory_pages();
