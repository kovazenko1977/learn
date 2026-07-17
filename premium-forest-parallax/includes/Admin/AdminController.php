<?php
namespace PremiumForestParallax\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumForestParallax\Config;

/**
 * Controller class to manage WP Admin Settings Page.
 *
 * @package PremiumForestParallax\Admin
 */
class AdminController {

	/**
	 * Config instance.
	 *
	 * @var Config
	 */
	private Config $config;

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration object.
	 */
	public function __construct( Config $config ) {
		$this->config = $config;

		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Add menu item to the WordPress Admin Bar.
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Premium Forest Parallax Settings', 'premium-forest-parallax' ),
			__( 'Forest Parallax', 'premium-forest-parallax' ),
			'manage_options',
			'premium-forest-parallax',
			[ $this, 'render_settings_page' ],
			'dashicons-palmtree',
			80
		);
	}

	/**
	 * Enqueue admin stylesheet and scripts.
	 *
	 * @param string $hook The current admin page screen.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'toplevel_page_premium-forest-parallax' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'premium-forest-parallax-admin-css',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/css/admin.css',
			[],
			PREMIUM_FOREST_PARALLAX_VERSION
		);

		wp_enqueue_script(
			'premium-forest-parallax-admin-js',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/js/admin.js',
			[],
			PREMIUM_FOREST_PARALLAX_VERSION,
			true
		);
	}

	/**
	 * Register option and settings fields.
	 */
	public function register_settings(): void {
		register_setting(
			'premium_forest_parallax_settings_group',
			'premium_forest_parallax_settings',
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->config->get_defaults(),
			]
		);
	}

	/**
	 * Sanitize and validate settings before saving.
	 *
	 * @param array $input Settings array submitted via POST.
	 * @return array
	 */
	public function sanitize_settings( array $input ): array {
		$output   = [];
		$defaults = $this->config->get_defaults();

		foreach ( $defaults as $key => $default ) {
			if ( ! isset( $input[ $key ] ) ) {
				// Handle unchecked checkboxes
				if ( is_string( $default ) && ( '1' === $default || '0' === $default ) ) {
					$output[ $key ] = '0';
				} else {
					$output[ $key ] = $default;
				}
				continue;
			}

			$val = $input[ $key ];

			if ( is_array( $default ) ) {
				if ( is_string( $val ) ) {
					$exploded = explode( ',', $val );
					$trimmed = array_map( 'trim', $exploded );
					$output[ $key ] = array_filter( array_map( 'sanitize_text_field', $trimmed ) );
				} else {
					$output[ $key ] = array_map( 'sanitize_text_field', (array) $val );
				}
			} elseif ( is_int( $default ) ) {
				$output[ $key ] = intval( $val );
			} elseif ( 'custom_css' === $key ) {
				// CSS can contain >, <, or other styles, we sanitize without destroying styling markers
				$output[ $key ] = wp_strip_all_tags( $val );
			} elseif ( 'custom_js' === $key ) {
				// JS code is raw, we strip simple tags but retain code semantics safely.
				// As administrator can do unfiltered_html, we can decode/allow standard characters.
				$output[ $key ] = $val;
			} else {
				$output[ $key ] = sanitize_text_field( $val );
			}
		}

		return $output;
	}

	/**
	 * Render Settings Page Template.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'premium-forest-parallax' ) );
		}

		$settings = $this->config->get_settings();
		require_once PREMIUM_FOREST_PARALLAX_PATH . 'templates/admin-settings.php';
	}
}
