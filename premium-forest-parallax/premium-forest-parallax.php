<?php
/*
Plugin Name: Premium Forest Parallax
Plugin URI: https://wes.by/
Description: Adds interactive detailed leaf parallax on screen edges.
Version: 1.0.0
Author: Kovazhenko S.B.
Author URI: https://wes.by/
Text Domain: premium-forest-parallax
Domain Path: /languages
License: GPLv2 or later
Requires at least: 6.0
Requires PHP: 8.0
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
