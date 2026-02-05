<?php
/**
 * Plugin Name: Sanatorium Management (Система управления санаторием)
 * Description: Профессиональная система учета для санатория: управление номерами, путевками, процедурами и бронированием.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: sanatorium-management
 * Domain Path: /languages
 * Russian Name: Управление Санаторием
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('SAN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAN_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include files
require_once SAN_PLUGIN_DIR . 'includes/db.php';
require_once SAN_PLUGIN_DIR . 'includes/admin.php';
require_once SAN_PLUGIN_DIR . 'includes/frontend.php';
require_once SAN_PLUGIN_DIR . 'includes/ajax.php';
require_once SAN_PLUGIN_DIR . 'includes/notifications.php';

// Activation and Deactivation
register_activation_hook(__FILE__, 'san_activate_plugin');
register_deactivation_hook(__FILE__, 'san_deactivate_plugin');

function san_activate_plugin() {
    san_create_db_tables();
}

function san_deactivate_plugin() {
    // Clean up if needed
}

// Initialize styles and scripts
add_action('admin_enqueue_scripts', 'san_enqueue_admin_assets');
function san_enqueue_admin_assets($hook) {
    if (strpos($hook, 'sanatorium') !== false) {
        wp_enqueue_style('san-admin-css', SAN_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('san-admin-js', SAN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
    }
}
