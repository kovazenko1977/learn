<?php
namespace PremiumForestParallax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class (Singleton)
 *
 * @package PremiumForestParallax
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Config instance.
	 *
	 * @var Config
	 */
	public Config $config;

	/**
	 * Admin controller instance.
	 *
	 * @var Admin\AdminController
	 */
	public Admin\AdminController $admin;

	/**
	 * Frontend controller instance.
	 *
	 * @var Frontend\FrontendController
	 */
	public Frontend\FrontendController $frontend;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for Singleton.
	 */
	private function __construct() {
		$this->config   = new Config();
		$this->admin    = new Admin\AdminController( $this->config );
		$this->frontend = new Frontend\FrontendController( $this->config );

		$this->init();
	}

	/**
	 * Initialize plugin hooks.
	 */
	public function init(): void {
		// Load text domain
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Load translation files.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'premium-forest-parallax',
			false,
			dirname( plugin_basename( PREMIUM_FOREST_PARALLAX_FILE ) ) . '/languages'
		);
	}
}
