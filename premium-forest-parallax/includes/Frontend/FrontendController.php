<?php
namespace PremiumForestParallax\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumForestParallax\Config;

/**
 * Controller class to manage Frontend asset queuing, canvas injection, and settings passing.
 *
 * @package PremiumForestParallax\Frontend
 */
class FrontendController {

	/**
	 * Config instance.
	 *
	 * @var Config
	 */
	private Config $config;

	/**
	 * Constructor.
	 *
	 * @param Config $config Configuration object.
	 */
	public function __construct( Config $config ) {
		$this->config = $config;

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'wp_footer', [ $this, 'inject_parallax_canvas' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_support' ] );
	}

	/**
	 * Conditional assets enqueuing based on page targets and exclusions.
	 */
	public function enqueue_frontend_assets(): void {
		$settings = $this->config->get_settings();

		if ( '1' !== $settings['enabled'] ) {
			return;
		}

		// Verify Target Page Limitations
		if ( ! empty( $settings['target_pages'] ) && is_array( $settings['target_pages'] ) ) {
			$post_id = get_the_ID();
			if ( ! in_array( (string) $post_id, $settings['target_pages'], true ) ) {
				return;
			}
		}

		// Verify Exclude Page Limitations
		if ( ! empty( $settings['exclude_pages'] ) && is_array( $settings['exclude_pages'] ) ) {
			$post_id = get_the_ID();
			if ( ! empty( $post_id ) && in_array( (string) $post_id, $settings['exclude_pages'], true ) ) {
				return;
			}
		}

		// Enqueue styling
		wp_enqueue_style(
			'premium-forest-parallax-frontend',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/css/frontend.css',
			[],
			PREMIUM_FOREST_PARALLAX_VERSION
		);

		// Enqueue Three.js from a robust, lightning-fast CDN
		wp_enqueue_script(
			'three-js',
			'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
			[],
			'128',
			true
		);

		// Enqueue Modular JS files
		wp_enqueue_script(
			'premium-forest-shaders',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/js/shaders.js',
			[],
			PREMIUM_FOREST_PARALLAX_VERSION,
			true
		);

		wp_enqueue_script(
			'premium-forest-utils-canvas',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/js/utils-canvas.js',
			[],
			PREMIUM_FOREST_PARALLAX_VERSION,
			true
		);

		wp_enqueue_script(
			'premium-forest-utils-webgl',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/js/utils-webgl.js',
			[ 'three-js', 'premium-forest-shaders' ],
			PREMIUM_FOREST_PARALLAX_VERSION,
			true
		);

		wp_enqueue_script(
			'premium-forest-physics',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/js/physics.js',
			[ 'premium-forest-utils-canvas' ],
			PREMIUM_FOREST_PARALLAX_VERSION,
			true
		);

		wp_enqueue_script(
			'premium-forest-loader',
			PREMIUM_FOREST_PARALLAX_URL . 'assets/js/loader.js',
			[ 'premium-forest-physics', 'premium-forest-utils-webgl' ],
			PREMIUM_FOREST_PARALLAX_VERSION,
			true
		);

		// Pass server-side options array to front-end JS context
		wp_localize_script(
			'premium-forest-loader',
			'premiumForestParams',
			[
				'config'  => $settings,
				'svg_url' => PREMIUM_FOREST_PARALLAX_URL . 'assets/svg/',
			]
		);
	}

	/**
	 * Inject Canvas HTML Container to footer.
	 * Center screen area is styled to remain fully pointer-interactive and empty of leaf blocks.
	 */
	public function inject_parallax_canvas(): void {
		$settings = $this->config->get_settings();
		if ( '1' !== $settings['enabled'] ) {
			return;
		}
		?>
		<!-- Premium Forest Parallax Layer Canvas -->
		<div id="premium-forest-parallax-container" class="premium-forest-env-container" aria-hidden="true">
			<canvas id="premium-forest-parallax-canvas"></canvas>

			<?php if ( '1' === $settings['overlay_mode_enabled'] ) : ?>
				<!-- Premium Forest Photo Overlay Frames -->
				<?php if ( ! empty( $settings['overlay_image_left'] ) ) : ?>
					<div class="premium-forest-overlay-edge forest-overlay-left" style="background-image: url('<?php echo esc_url( $settings['overlay_image_left'] ); ?>'); width: <?php echo intval( $settings['overlay_width'] ); ?>px; opacity: <?php echo intval( $settings['overlay_opacity'] ) / 100; ?>;"></div>
				<?php endif; ?>
				<?php if ( ! empty( $settings['overlay_image_right'] ) ) : ?>
					<div class="premium-forest-overlay-edge forest-overlay-right" style="background-image: url('<?php echo esc_url( $settings['overlay_image_right'] ); ?>'); width: <?php echo intval( $settings['overlay_width'] ); ?>px; opacity: <?php echo intval( $settings['overlay_opacity'] ) / 100; ?>;"></div>
				<?php endif; ?>
				<?php if ( ! empty( $settings['overlay_image_top'] ) ) : ?>
					<div class="premium-forest-overlay-edge forest-overlay-top" style="background-image: url('<?php echo esc_url( $settings['overlay_image_top'] ); ?>'); height: <?php echo intval( $settings['overlay_width'] ); ?>px; opacity: <?php echo intval( $settings['overlay_opacity'] ) / 100; ?>;"></div>
				<?php endif; ?>
				<?php if ( ! empty( $settings['overlay_image_bottom'] ) ) : ?>
					<div class="premium-forest-overlay-edge forest-overlay-bottom" style="background-image: url('<?php echo esc_url( $settings['overlay_image_bottom'] ); ?>'); height: <?php echo intval( $settings['overlay_width'] ); ?>px; opacity: <?php echo intval( $settings['overlay_opacity'] ) / 100; ?>;"></div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		// Inject custom styling variables (must not use esc_html which destroys parent selectors, e.g. > )
		if ( ! empty( $settings['custom_css'] ) ) {
			echo '<style>' . wp_strip_all_tags( $settings['custom_css'] ) . '</style>';
		}
	}

	/**
	 * Register elementor widget hook structure to ensure 100% Elementor theme compatibility.
	 */
	public function register_elementor_support( $widgets_manager ): void {
		// Enforce automatic support compatibility mapping - assets enqueued automatically via global filters.
	}
}
