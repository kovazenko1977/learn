<?php
/**
 * Uninstall Premium Forest Parallax
 *
 * This file is runs when the plugin is deleted from the WordPress admin panel.
 * It cleans up all the custom options database records.
 *
 * @package PremiumForestParallax
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly.
}

// Delete options
delete_option( 'premium_forest_parallax_settings' );
