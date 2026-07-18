/**
 * Premium Forest Parallax Main Application Loader
 *
 * Automatically instantiates the core physics loop, chooses the WebGL vs Canvas2D
 * renderer based on the profile, and optimizes performance with dynamic ResizeObservers and IntersectionObservers.
 */
document.addEventListener('DOMContentLoaded', async function () {
	if (typeof premiumForestParams === 'undefined') {
		return;
	}

	const config = premiumForestParams.config;
	const container = document.getElementById('premium-forest-parallax-container');
	const canvas = document.getElementById('premium-forest-parallax-canvas');

	if (!container || !canvas) return;

	// Detect touch/mobile device
	const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

	// Total mobile opt-out check
	if (isTouchDevice && config.mobile_disable_on_touch === '1') {
		container.style.display = 'none';
		return;
	}

	// 1. Run Dynamic Hardware Benchmarking and Determine Device Profile
	const profile = await window.PremiumForestParallax.DeviceProfiler.getProfile(config.perf_observe === '1');

	// Apply profile parameters to configuration overrides
	adjustConfigForProfile(config, profile, isTouchDevice);

	// 2. Preload Leaf SVG Textures
	const loadedAssets = await preloadLeafAssets(config, premiumForestParams.svg_url);

	// 3. Instantiate Physics Engine & Setup Layers/Particles
	const physics = new window.PremiumForestParallax.ForestPhysicsEngine(canvas, config);

	// 4. Choose appropriate renderer
	let renderer = null;
	const useWebGL = config.webgl_enabled === '1' && profile !== 'Lite' && typeof THREE !== 'undefined';

	if (useWebGL) {
		renderer = new window.PremiumForestParallax.WebGLRenderer(canvas, config, loadedAssets.textures);
		renderer.setupScene(physics.layers, physics.leaves.length, physics.particles);
	} else {
		renderer = new window.PremiumForestParallax.Canvas2DRenderer(canvas, config, loadedAssets.images);
	}

	// 5. Setup IntersectionObserver & ResizeObserver to avoid offscreen or zero-sized rendering overhead
	let isVisible = true;
	if (config.perf_observe === '1' && typeof IntersectionObserver !== 'undefined') {
		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				isVisible = entry.isIntersecting;
			});
		}, { threshold: 0.01 });
		observer.observe(container);
	}

	const resizeCanvas = () => {
		const w = window.innerWidth;
		const h = window.innerHeight;
		physics.resize(w, h);
		renderer.resize(w, h);
	};

	if (typeof ResizeObserver !== 'undefined') {
		const resizeObserver = new ResizeObserver(() => {
			resizeCanvas();
		});
		resizeObserver.observe(document.body);
	} else {
		window.addEventListener('resize', resizeCanvas);
	}
	resizeCanvas();

	// 5.5 Mobile/Desktop Interaction Fade on Tap/Click UX integration
	let isFadedOut = false;
	let reappearTimeout = null;

	// Mobile handler
	if (isTouchDevice && config.mobile_fade_on_tap === '1') {
		const fadeOutMs = parseInt(config.mobile_fade_out_duration || 800);
		const reappearMs = parseInt(config.mobile_reappear_delay || 4000);

		window.addEventListener('touchstart', function () {
			// Fade out the entire container smoothly using GPU-accelerated CSS transition
			container.style.transition = `opacity ${fadeOutMs}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
			container.style.opacity = '0';
			isFadedOut = true;

			// Clear previous timer
			if (reappearTimeout) {
				clearTimeout(reappearTimeout);
			}

			// Restart reappear timer upon inactivity
			reappearTimeout = setTimeout(() => {
				container.style.transition = `opacity ${fadeOutMs}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
				container.style.opacity = '1';
				isFadedOut = false;
			}, reappearMs);
		}, { passive: true });
	}

	// Desktop handler
	if (!isTouchDevice && config.desktop_fade_on_click === '1') {
		const fadeOutMs = parseInt(config.desktop_fade_out_duration || 800);
		const reappearMs = parseInt(config.desktop_reappear_delay || 4000);

		window.addEventListener('mousedown', function () {
			// Fade out the entire container smoothly using GPU-accelerated CSS transition
			container.style.transition = `opacity ${fadeOutMs}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
			container.style.opacity = '0';
			isFadedOut = true;

			// Clear previous timer
			if (reappearTimeout) {
				clearTimeout(reappearTimeout);
			}

			// Restart reappear timer upon inactivity
			reappearTimeout = setTimeout(() => {
				container.style.transition = `opacity ${fadeOutMs}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`;
				container.style.opacity = '1';
				isFadedOut = false;
			}, reappearMs);
		});
	}

	// 6. Execute Main requestAnimationFrame Render Loop with dynamic throttling and pausing
	let lastTime = performance.now();
	let isTabActive = true;

	if (config.perf_pause_hidden === '1') {
		document.addEventListener('visibilitychange', () => {
			isTabActive = !document.hidden;
		});
	}

	const targetInterval = config.perf_limit_fps === '1' ? (1000 / parseFloat(config.perf_target_fps || 60)) : 0;

	const loop = (time) => {
		requestAnimationFrame(loop);

		// Dynamic optimization: Skip calculations if page is out of view or tab is hidden
		if (!isVisible || !isTabActive) {
			return;
		}

		const deltaTime = time - lastTime;
		if (targetInterval > 0 && deltaTime < targetInterval) {
			return; // Bound rate limiting threshold
		}

		lastTime = time;

		// Perform frame update tick
		const frameState = physics.update(deltaTime);

		// Apply GPU-accelerated CSS Parallax transform on Photo Overlays if enabled
		if (config.overlay_mode_enabled === '1') {
			const strength = parseFloat(config.overlay_parallax_strength || 5) * 0.1;
			if (strength > 0) {
				const ox = physics.mouse.x * strength;
				const oy = physics.mouse.y * strength;

				const overlays = document.querySelectorAll('.premium-forest-overlay-edge');
				overlays.forEach(overlay => {
					// Different edges can slide slightly differently for beautiful depth illusion
					let factorX = 1.0;
					let factorY = 1.0;
					if (overlay.classList.contains('forest-overlay-left')) { factorX = 0.5; factorY = 0.3; }
					else if (overlay.classList.contains('forest-overlay-right')) { factorX = -0.5; factorY = 0.3; }
					else if (overlay.classList.contains('forest-overlay-top')) { factorX = 0.3; factorY = 0.5; }
					else if (overlay.classList.contains('forest-overlay-bottom')) { factorX = 0.3; factorY = -0.5; }

					overlay.style.transform = `translate3d(${ox * factorX}px, ${oy * factorY}px, 0)`;
				});
			}
		}

		// Calculate fog depth opacity relative to wind force
		const fogOpacity = parseFloat((config.fog_opacity || 30) / 100);

		// Render the frame
		// In WebGL mode, renderer expects time value; In Canvas2D fallback, renderer expects fogOpacity.
		if (useWebGL) {
			renderer.render(physics.layers, physics.leaves, physics.particles, frameState.wind, time * 0.001);
		} else {
			renderer.render(physics.layers, physics.leaves, physics.particles, fogOpacity);
		}
	};

	requestAnimationFrame(loop);

	// Evaluate Custom JS Code provided in settings
	if (config.custom_js) {
		try {
			const customFn = new Function('physics', 'renderer', config.custom_js);
			customFn(physics, renderer);
		} catch (err) {
			console.error("Premium Forest Parallax - Custom JS Error: ", err);
		}
	}
});

/**
 * Scale parameters down or up dynamically to match device performance thresholds + granular mobile options.
 */
function adjustConfigForProfile(config, profile, isTouch) {
	// 1. Core Profile Defaults
	if (profile === 'Lite') {
		config.webgl_enabled = '0';
		config.leaf_count = Math.min(parseInt(config.leaf_count), 15);
		config.particles_enabled = '0';
		config.fog_enabled = '0';
	} else if (profile === 'Low') {
		config.webgl_enabled = '0';
		config.leaf_count = Math.min(parseInt(config.leaf_count), 20);
		config.particles_count = Math.min(parseInt(config.particles_count), 20);
	} else if (profile === 'Medium') {
		config.leaf_count = Math.min(parseInt(config.leaf_count), 30);
	} else if (profile === 'Ultra') {
		config.leaf_count = Math.max(parseInt(config.leaf_count), 50);
	}

	// 2. Granular Mobile Overrides
	if (isTouch && config.mobile_optimize_enabled === '1') {
		if (config.mobile_force_canvas2d === '1') {
			config.webgl_enabled = '0';
		}

		if (config.mobile_disable_effects === '1') {
			config.webgl_bloom_pass = '0';
			config.webgl_blur_pass = '0';
			config.webgl_noise_pass = '0';
			config.webgl_fog_pass = '0';
			config.fog_enabled = '0';
		}

		// Apply percentage reductions based on admin sliders
		const leavesReduction = parseFloat(config.mobile_reduce_leaves || 50) * 0.01;
		config.leaf_count = Math.floor(parseInt(config.leaf_count) * (1 - leavesReduction));

		const particlesReduction = parseFloat(config.mobile_reduce_particles || 50) * 0.01;
		config.particles_count = Math.floor(parseInt(config.particles_count) * (1 - particlesReduction));

		const branchesReduction = parseFloat(config.mobile_reduce_branches || 30) * 0.01;
		config.edge_branches_density = Math.floor(parseInt(config.edge_branches_density || 8) * (1 - branchesReduction));
		config.edge_branches_density = Math.max(config.edge_branches_density, 2); // Keep minimum of 2 branches for visuals
	}
}

/**
 * Parallel async helper to preload all 15 leaf SVG types + custom uploaded images
 */
async function preloadLeafAssets(config, baseUrl) {
	const leafTypes = ['birch1', 'birch2', 'birch3', 'oak1', 'oak2', 'oak3', 'linden1', 'linden2', 'linden3', 'maple1', 'maple2', 'maple3', 'aspen1', 'aspen2', 'aspen3'];

	// Add custom files if specified
	if (config.custom_leaf_image) {
		leafTypes.push('custom_leaf');
	}
	if (config.custom_branch_image) {
		leafTypes.push('custom_branch');
	}

	const images = {};
	const textures = {};

	const promises = leafTypes.map(type => {
		return new Promise((resolve) => {
			let imgUrl = `${baseUrl}${type}.svg`;
			if (type === 'custom_leaf') {
				imgUrl = config.custom_leaf_image;
			} else if (type === 'custom_branch') {
				imgUrl = config.custom_branch_image;
			}

			const img = new Image();
			img.onload = () => {
				images[type] = img;

				// Prepare Three.js Texture if WebGL is available
				if (typeof THREE !== 'undefined' && config.webgl_enabled === '1') {
					const tex = new THREE.Texture(img);
					tex.needsUpdate = true;
					textures[type] = tex;
				}
				resolve();
			};
			img.onerror = () => {
				resolve(); // Graceful failure continuation
			};
			img.src = imgUrl;
		});
	});

	await Promise.all(promises);
	return { images, textures };
}
