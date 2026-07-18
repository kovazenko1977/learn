<?php
namespace PremiumForestParallax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Config & Default Settings Class
 *
 * @package PremiumForestParallax
 */
class Config {

	/**
	 * Default settings for the plugin.
	 *
	 * @var array
	 */
	private array $defaults = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->defaults = [
			// General Tab
			'enabled'            => '1',
			'autoload'           => '1',
			'target_pages'       => [],
			'exclude_pages'      => [],

			// Leaves Tab
			'leaf_count'         => 30,
			'leaf_mode'          => 'falling', // falling, swaying
			'leaf_size_min'      => 30,
			'leaf_size_max'      => 60,
			'leaf_opacity'       => 90,
			'leaf_random_color'  => '0',
			'leaf_svg_set'       => 'mixed', // birch, oak, linden, maple, aspen, mixed
			'custom_leaf_image'  => '',

			// Edge Branches (Forest Frame) Settings
			'edge_branches_enabled'      => '1',
			'edge_branches_width'        => 180,
			'edge_branches_density'      => 8,
			'edge_branches_color'        => '#3d6a24',
			'edge_branches_color2'       => '#2f541c',
			'edge_branches_sway_speed'   => 10,
			'edge_branches_sway_amplitude' => 15,
			'custom_branch_image'        => '',

			// Wind Tab
			'wind_direction'     => 180, // Angle in degrees
			'wind_strength'      => 5,
			'wind_gusts'         => 3,
			'wind_randomness'    => 5,
			'wind_frequency'     => 2,
			'wind_rotation'      => 10,

			// Particles Tab
			'particles_enabled'  => '1',
			'particles_type'     => 'pollen', // pollen, dust, small, glowing
			'particles_count'    => 50,
			'particles_size'     => 4,
			'particles_speed'    => 2,

			// Lighting Tab
			'light_god_rays'     => '1',
			'light_bloom'        => '1',
			'light_sun_glow'     => '1',
			'light_lens_flare'   => '0',
			'light_soft_light'   => '1',

			// Fog Tab
			'fog_enabled'        => '1',
			'fog_depth'          => '1',
			'fog_atmospheric'    => '1',
			'fog_blur'           => 10,
			'fog_distance'       => 50,
			'fog_opacity'        => 30,

			// Parallax Tab
			'parallax_layers'    => 7,
			'parallax_speed'     => 5,
			'parallax_depth'     => 4,
			'parallax_inertia'   => 8,
			'parallax_smooth'    => '1',

			// WebGL Tab
			'webgl_enabled'      => '1',
			'webgl_bloom_pass'   => '1',
			'webgl_blur_pass'    => '1',
			'webgl_noise_pass'   => '1',
			'webgl_fog_pass'     => '1',

			// Performance Tab
			'perf_limit_fps'     => '1',
			'perf_target_fps'    => 60,
			'perf_observe'       => '1',
			'perf_pause_hidden'  => '1',
			'perf_pause_offscr'  => '1',

			// Advanced Tab
			'custom_css'         => '',
			'custom_js'          => '',
		];
	}

	/**
	 * Get all active plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$saved = get_option( 'premium_forest_parallax_settings', [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return array_merge( $this->defaults, $saved );
	}

	/**
	 * Get a single setting option.
	 *
	 * @param string $key Settings key.
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? null;
	}

	/**
	 * Get all defaults.
	 *
	 * @return array
	 */
	public function get_defaults(): array {
		return $this->defaults;
	}
}
