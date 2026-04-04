<?php
/**
 * Plugin Name: Excel Sections Manager
 * Description: Manage site sections (like News, etc.) via an Excel file. Use shortcodes to display sections.
 * Version: 1.1.0
 * Author: Jules
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Autoload dependencies
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Define constants
define( 'ESM_PATH', plugin_dir_path( __FILE__ ) );
define( 'ESM_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once ESM_PATH . 'includes/class-excel-parser.php';
require_once ESM_PATH . 'includes/class-admin-page.php';
require_once ESM_PATH . 'includes/class-shortcode.php';

// Initialize the plugin
add_action( 'plugins_loaded', 'esm_init' );

function esm_init() {
    new ESM_Admin_Page();
    new ESM_Shortcode();
}

// Enqueue styles
add_action( 'wp_enqueue_scripts', 'esm_enqueue_styles' );
function esm_enqueue_styles() {
    wp_enqueue_style( 'esm-styles', ESM_URL . 'assets/css/style.css', array(), '1.1.0' );
}
