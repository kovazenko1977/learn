<?php
/**
 * Plugin Name: Premium Forest Parallax
 * Plugin URI: https://wes.by
 * Description: Premium Forest Parallax plugin adds an interactive, high-performance, live-nature aesthetic parallax leaf and particle effect on the edges of your website using WebGL2, Three.js, and Canvas2D fallback.
 * Version: 1.0.0
 * Author: Коваженко С.Б.
 * Author URI: https://wes.by
 * License: GPLv2 or later
 * Text Domain: premium-forest-parallax
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires At Least: 6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'PREMIUM_FOREST_PARALLAX_VERSION', '1.0.0' );
define( 'PREMIUM_FOREST_PARALLAX_FILE', __FILE__ );
define( 'PREMIUM_FOREST_PARALLAX_PATH', plugin_dir_path( __FILE__ ) );
define( 'PREMIUM_FOREST_PARALLAX_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register PSR-4 Autoloader
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'PremiumForestParallax\\';
	$len    = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = PREMIUM_FOREST_PARALLAX_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Initialize Plugin
add_action( 'plugins_loaded', function () {
	\PremiumForestParallax\Plugin::get_instance();
} );
