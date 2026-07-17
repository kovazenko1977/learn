<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Page Template for Premium Forest Parallax
 *
 * @var array $settings Current saved configuration.
 */
?>
<div class="wrap premium-forest-wrap">
	<div class="premium-forest-header">
		<div>
			<h1>Premium Forest Parallax</h1>
			<p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">
				<?php esc_html_e( 'Bring live nature, detailed falling leaves, dynamic Perlin wind, and edge atmospheric effects directly to your WordPress site.', 'premium-forest-parallax' ); ?>
			</p>
		</div>
		<div class="author-tag">
			<?php echo esc_html__( 'Author:', 'premium-forest-parallax' ) . ' <strong>Коваженко С.Б.</strong><br>'; ?>
			<?php echo esc_html__( 'Developer:', 'premium-forest-parallax' ) . ' <a href="https://wes.by" target="_blank">wes.by</a>'; ?>
		</div>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'premium_forest_parallax_settings_group' ); ?>

		<div class="premium-forest-body">
			<!-- Tabs Sidebar -->
			<div class="premium-forest-tabs">
				<a href="#" class="premium-forest-tab-link active" data-tab="general"><?php esc_html_e( 'General', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="leaves"><?php esc_html_e( 'Leaves', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="wind"><?php esc_html_e( 'Wind', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="particles"><?php esc_html_e( 'Particles', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="lighting"><?php esc_html_e( 'Lighting', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="fog"><?php esc_html_e( 'Fog', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="parallax"><?php esc_html_e( 'Parallax', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="webgl"><?php esc_html_e( 'WebGL', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="performance"><?php esc_html_e( 'Performance', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="advanced"><?php esc_html_e( 'Advanced', 'premium-forest-parallax' ); ?></a>
			</div>

			<!-- Tabs Content -->
			<div class="premium-forest-content">

				<!-- 1. General Tab -->
				<div id="tab-general" class="premium-forest-tab-content active">
					<h2><?php esc_html_e( 'General Settings', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Parallax Effect', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[enabled]" value="1" <?php checked( $settings['enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Toggle the forest parallax effect globally on/off.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Autoload assets', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[autoload]" value="1" <?php checked( $settings['autoload'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Automatically load assets on all frontend pages.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Target Pages IDs', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" name="premium_forest_parallax_settings[target_pages]" value="<?php echo esc_attr( implode( ',', (array) $settings['target_pages'] ) ); ?>" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Comma-separated Page/Post IDs to exclusively display the effect. Leave empty for all.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Exclude Pages IDs', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" name="premium_forest_parallax_settings[exclude_pages]" value="<?php echo esc_attr( implode( ',', (array) $settings['exclude_pages'] ) ); ?>" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Comma-separated Page/Post IDs where the effect should be hidden completely.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 2. Leaves Tab -->
				<div id="tab-leaves" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Leaves Settings', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Number of Leaves', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_count]" value="<?php echo esc_attr( $settings['leaf_count'] ); ?>" min="5" max="150" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Total falling or floating leaf count simulated on the screen.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Minimum Size (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_size_min]" value="<?php echo esc_attr( $settings['leaf_size_min'] ); ?>" min="5" max="100" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Maximum Size (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_size_max]" value="<?php echo esc_attr( $settings['leaf_size_max'] ); ?>" min="10" max="250" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Leaves Opacity (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_opacity]" value="<?php echo esc_attr( $settings['leaf_opacity'] ); ?>" min="10" max="100" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Randomize Colors', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[leaf_random_color]" value="1" <?php checked( $settings['leaf_random_color'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Dynamically tint leaves for a more natural variation.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Leaf Variety (SVG Set)', 'premium-forest-parallax' ); ?></th>
							<td>
								<select name="premium_forest_parallax_settings[leaf_svg_set]">
									<option value="birch" <?php selected( $settings['leaf_svg_set'], 'birch' ); ?>><?php esc_html_e( 'Birch Only', 'premium-forest-parallax' ); ?></option>
									<option value="oak" <?php selected( $settings['leaf_svg_set'], 'oak' ); ?>><?php esc_html_e( 'Oak Only', 'premium-forest-parallax' ); ?></option>
									<option value="linden" <?php selected( $settings['leaf_svg_set'], 'linden' ); ?>><?php esc_html_e( 'Linden Only', 'premium-forest-parallax' ); ?></option>
									<option value="maple" <?php selected( $settings['leaf_svg_set'], 'maple' ); ?>><?php esc_html_e( 'Maple Only', 'premium-forest-parallax' ); ?></option>
									<option value="aspen" <?php selected( $settings['leaf_svg_set'], 'aspen' ); ?>><?php esc_html_e( 'Aspen Only', 'premium-forest-parallax' ); ?></option>
									<option value="mixed" <?php selected( $settings['leaf_svg_set'], 'mixed' ); ?>><?php esc_html_e( 'Mixed Forest (All SVG Leaf types)', 'premium-forest-parallax' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<!-- 3. Wind Tab -->
				<div id="tab-wind" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Wind Simulation (Perlin Noise)', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Wind Angle/Direction (deg)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_direction]" value="<?php echo esc_attr( $settings['wind_direction'] ); ?>" min="0" max="360" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Direction of wind blowing (0 to 360 degrees).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Wind Strength', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_strength]" value="<?php echo esc_attr( $settings['wind_strength'] ); ?>" min="0" max="50" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Gust Force', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_gusts]" value="<?php echo esc_attr( $settings['wind_gusts'] ); ?>" min="0" max="10" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Wind Randomness', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_randomness]" value="<?php echo esc_attr( $settings['wind_randomness'] ); ?>" min="0" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Perlin Frequency', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_frequency]" value="<?php echo esc_attr( $settings['wind_frequency'] ); ?>" min="1" max="10" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Leaf Rotation Speed', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_rotation]" value="<?php echo esc_attr( $settings['wind_rotation'] ); ?>" min="0" max="50" />
							</td>
						</tr>
					</table>
				</div>

				<!-- 4. Particles Tab -->
				<div id="tab-particles" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Micro Particles Settings', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Micro Particles', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[particles_enabled]" value="1" <?php checked( $settings['particles_enabled'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Particle Type', 'premium-forest-parallax' ); ?></th>
							<td>
								<select name="premium_forest_parallax_settings[particles_type]">
									<option value="pollen" <?php selected( $settings['particles_type'], 'pollen' ); ?>><?php esc_html_e( 'Forest Pollen', 'premium-forest-parallax' ); ?></option>
									<option value="dust" <?php selected( $settings['particles_type'], 'dust' ); ?>><?php esc_html_e( 'Atmospheric Dust', 'premium-forest-parallax' ); ?></option>
									<option value="small" <?php selected( $settings['particles_type'], 'small' ); ?>><?php esc_html_e( 'Tiny Leaf Bits', 'premium-forest-parallax' ); ?></option>
									<option value="glowing" <?php selected( $settings['particles_type'], 'glowing' ); ?>><?php esc_html_e( 'Glowing Fireflies', 'premium-forest-parallax' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Max Particles Count', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[particles_count]" value="<?php echo esc_attr( $settings['particles_count'] ); ?>" min="10" max="500" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Particle Average Size (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[particles_size]" value="<?php echo esc_attr( $settings['particles_size'] ); ?>" min="1" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Velocity Factor', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[particles_speed]" value="<?php echo esc_attr( $settings['particles_speed'] ); ?>" min="1" max="10" />
							</td>
						</tr>
					</table>
				</div>

				<!-- 5. Lighting Tab -->
				<div id="tab-lighting" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Lighting & Post-Processing Shaders', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable God Rays', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_god_rays]" value="1" <?php checked( $settings['light_god_rays'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Project volumetric light shafts through the edge branches.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Bloom Pass', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_bloom]" value="1" <?php checked( $settings['light_bloom'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Give glowing particles and bright highlights a soft organic glow.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Atmospheric Sun Glow', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_sun_glow]" value="1" <?php checked( $settings['light_sun_glow'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Simulate Camera Lens Flare', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_lens_flare]" value="1" <?php checked( $settings['light_lens_flare'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Soft Ambient Light', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_soft_light]" value="1" <?php checked( $settings['light_soft_light'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 6. Fog Tab -->
				<div id="tab-fog" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Atmospheric Fog Settings', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Depth Fog', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[fog_enabled]" value="1" <?php checked( $settings['fog_enabled'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Differentiate Fog on Layer Depth', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[fog_depth]" value="1" <?php checked( $settings['fog_depth'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Atmospheric Fog Overlay', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[fog_atmospheric]" value="1" <?php checked( $settings['fog_atmospheric'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Fog Blur Intensity (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[fog_blur]" value="<?php echo esc_attr( $settings['fog_blur'] ); ?>" min="0" max="50" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Fog Visibility Distance', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[fog_distance]" value="<?php echo esc_attr( $settings['fog_distance'] ); ?>" min="10" max="200" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Fog Maximum Opacity (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[fog_opacity]" value="<?php echo esc_attr( $settings['fog_opacity'] ); ?>" min="0" max="100" />
							</td>
						</tr>
					</table>
				</div>

				<!-- 7. Parallax Tab -->
				<div id="tab-parallax" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Parallax Layers & Physics', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Total Parallax Layers (Min 7)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_layers]" value="<?php echo esc_attr( $settings['parallax_layers'] ); ?>" min="7" max="15" />
								<span class="premium-forest-desc"><?php esc_html_e( 'We enforce a minimum of 7 premium layers of forest branches around the screen borders.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Multiplier Speed Factor', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_speed]" value="<?php echo esc_attr( $settings['parallax_speed'] ); ?>" min="1" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Perceived Scene Depth', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_depth]" value="<?php echo esc_attr( $settings['parallax_depth'] ); ?>" min="1" max="10" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Mouse Inertia Smoothing', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_inertia]" value="<?php echo esc_attr( $settings['parallax_inertia'] ); ?>" min="1" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Scroll Smoothing Integration', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[parallax_smooth]" value="1" <?php checked( $settings['parallax_smooth'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 8. WebGL Tab -->
				<div id="tab-webgl" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Three.js & WebGL2 Engine Settings', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable WebGL Rendering', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_enabled]" value="1" <?php checked( $settings['webgl_enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Uses Three.js and custom GLSL vertex/fragment shaders. Uncheck to force Canvas2D.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Bloom Shader Pass', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_bloom_pass]" value="1" <?php checked( $settings['webgl_bloom_pass'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Gaussian Blur Pass', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_blur_pass]" value="1" <?php checked( $settings['webgl_blur_pass'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Film/Noise Distort Pass', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_noise_pass]" value="1" <?php checked( $settings['webgl_noise_pass'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Atmospheric Fog Pass', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_fog_pass]" value="1" <?php checked( $settings['webgl_fog_pass'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 9. Performance Tab -->
				<div id="tab-performance" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Optimizations & Smart CPU/GPU Throttle', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Use Automatic FPS Limiter', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_limit_fps]" value="1" <?php checked( $settings['perf_limit_fps'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Target FPS Threshold', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[perf_target_fps]" value="<?php echo esc_attr( $settings['perf_target_fps'] ); ?>" min="15" max="120" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Dynamic Device Benchmarking', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_observe]" value="1" <?php checked( $settings['perf_observe'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Runs performance diagnostic tests on page load (CPU, GPU, RAM, screen refresh rate) to assign Ultra, High, Medium, Low, or Lite profiles automatically.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Pause when Page Tab is Hidden', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_pause_hidden]" value="1" <?php checked( $settings['perf_pause_hidden'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Pause if canvas viewport goes offscreen', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_pause_offscr]" value="1" <?php checked( $settings['perf_pause_offscr'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 10. Advanced Tab -->
				<div id="tab-advanced" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Advanced Integration & Custom CSS/JS', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Custom CSS Stylesheet', 'premium-forest-parallax' ); ?></th>
							<td>
								<textarea name="premium_forest_parallax_settings[custom_css]" rows="6"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
								<span class="premium-forest-desc"><?php esc_html_e( 'Inject custom CSS overrides into the frontend headers.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Custom JS Callback', 'premium-forest-parallax' ); ?></th>
							<td>
								<textarea name="premium_forest_parallax_settings[custom_js]" rows="6"><?php echo esc_textarea( $settings['custom_js'] ); ?></textarea>
								<span class="premium-forest-desc"><?php esc_html_e( 'Evaluate custom script logic once the WebGL canvas starts rendering.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

			</div>
		</div>

		<div class="premium-forest-footer">
			<?php submit_button( __( 'Save Premium Settings', 'premium-forest-parallax' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>
