/**
 * Leaf Entity Model containing all physical parameters
 * satisfying the rigorous project instructions.
 */
class LeafEntity {
	constructor(width, height, config, type) {
		this.type = type;
		this.config = config;
		this.active = true;

		this.depth = Math.random() * 6 + 1; // 1 to 7 depth range

		const minSize = parseFloat(config.leaf_size_min || 30);
		const maxSize = parseFloat(config.leaf_size_max || 60);
		this.size = minSize + Math.random() * (maxSize - minSize);

		// Adjust physics on depth (farther means smaller)
		const depthFactor = 1 / (this.depth * 0.5 + 0.5);
		this.size *= depthFactor;

		this.opacity = parseFloat((config.leaf_opacity || 90) / 100);
		this.windInfluence = Math.random() * 0.8 + 0.2;
		this.gravity = (0.2 + Math.random() * 0.15) * depthFactor;
		this.drag = 0.985;

		this.lifetime = Math.floor(Math.random() * 500);
		this.maxLifetime = 1000 + Math.random() * 1000;

		// Swaying Mode Position Anchors
		this.mode = config.leaf_mode || 'falling'; // falling, swaying
		this.side = ['left', 'right', 'top'][Math.floor(Math.random() * 3)];
		this.initPosition(width, height);
	}

	initPosition(width, height) {
		if (this.mode === 'swaying') {
			// Anchored to borders of screen
			if (this.side === 'left') {
				this.anchorX = Math.random() * 120;
				this.anchorY = Math.random() * height;
			} else if (this.side === 'right') {
				this.anchorX = width - Math.random() * 120;
				this.anchorY = Math.random() * height;
			} else {
				this.anchorX = Math.random() * width;
				this.anchorY = Math.random() * 80;
			}
			this.x = this.anchorX;
			this.y = this.anchorY;
			this.baseRotation = Math.random() * Math.PI * 2;
			this.rotation = this.baseRotation;
			this.vx = 0;
			this.vy = 0;
		} else {
			// Falling Mode Spawning
			this.x = Math.random() * width;
			this.y = -Math.random() * 200 - 50;
			this.vx = (Math.random() - 0.5) * 1.5;
			const depthFactor = 1 / (this.depth * 0.5 + 0.5);
			this.vy = (Math.random() * 1.5 + 1.0) * depthFactor;
			this.rotation = Math.random() * Math.PI * 2;
			this.angularVelocity = (Math.random() - 0.5) * 0.05;
		}
	}

	/**
	 * Compute next physical step frame.
	 */
	update(wind, width, height) {
		this.lifetime++;

		if (this.mode === 'swaying') {
			// Static Swaying Logic: gentle oscillation around the edges
			const freq = 0.015 * (this.config.wind_frequency || 2);
			const swayAmount = 10 + (wind.intensity * 2.0);

			this.x = this.anchorX + Math.sin(this.lifetime * freq + this.depth) * swayAmount + (wind.forceX * this.windInfluence * 1.2);
			this.y = this.anchorY + Math.cos(this.lifetime * freq * 0.8 + this.depth) * (swayAmount * 0.5) + (wind.forceY * this.windInfluence * 0.8);

			// Oscillate rotation
			const rotSpeed = 0.01 * (this.config.wind_rotation || 10);
			this.rotation = this.baseRotation + Math.sin(this.lifetime * freq * 1.2) * 0.15 * rotSpeed;
		} else {
			// Standard Falling Physics
			this.vy += this.gravity;

			const windX = wind.forceX * this.windInfluence;
			const windY = wind.forceY * this.windInfluence;

			this.vx += windX * 0.1;
			this.vy += windY * 0.05;

			this.vx *= this.drag;
			this.vy *= this.drag;

			this.x += this.vx;
			this.y += this.vy;

			this.angularVelocity += Math.sin(this.lifetime * 0.05) * 0.002 + (this.vx * 0.001);
			this.angularVelocity *= 0.96;
			this.rotation += this.angularVelocity;

			// Recycle falling leaf once offscreen
			if (this.y > height + 100 || this.x < -100 || this.x > width + 100 || this.lifetime > this.maxLifetime) {
				this.reset(width);
			}
		}
	}

	reset(width) {
		this.x = Math.random() * width;
		this.y = -100;
		this.vx = (Math.random() - 0.5) * 1.5;
		this.vy = Math.random() * 1.5 + 1.0;
		this.lifetime = 0;
		this.active = Math.random() > 0.4;
	}
}

/**
 * Particle Entity Model (floating pollen/dust/fireflies)
 */
class ParticleEntity {
	constructor(width, height, config) {
		this.config = config;
		this.x = Math.random() * width;
		this.y = Math.random() * height;
		this.size = Math.random() * parseFloat(config.particles_size || 4) + 1;
		this.vx = (Math.random() - 0.5) * parseFloat(config.particles_speed || 2) * 0.5;
		this.vy = (Math.random() - 0.5) * parseFloat(config.particles_speed || 2) * 0.5;
		this.opacity = Math.random() * 0.6 + 0.2;

		const type = config.particles_type || 'pollen';
		if (type === 'glowing') {
			this.color = 'rgba(255, 230, 120, ' + this.opacity + ')';
		} else if (type === 'dust') {
			this.color = 'rgba(220, 220, 220, ' + (this.opacity * 0.5) + ')';
		} else {
			this.color = 'rgba(180, 210, 150, ' + this.opacity + ')';
		}
	}

	update(wind, width, height) {
		this.x += this.vx + wind.forceX * 0.05;
		this.y += this.vy + wind.forceY * 0.05;

		if (this.x < 0) this.x = width;
		if (this.x > width) this.x = 0;
		if (this.y < 0) this.y = height;
		if (this.y > height) this.y = 0;
	}
}

/**
 * Core Physics Engine & Parallax Edge Controller
 */
class ForestPhysicsEngine {
	constructor(canvas, config) {
		this.canvas = canvas;
		this.config = config;

		this.width = canvas.clientWidth || window.innerWidth;
		this.height = canvas.clientHeight || window.innerHeight;

		this.windSystem = new window.PremiumForestParallax.WindSystem(config);
		this.leaves = [];
		this.particles = [];
		this.layers = [];

		// Interactive states
		this.mouse = { x: 0, y: 0, targetX: 0, targetY: 0 };
		this.orientation = { gamma: 0, beta: 0 };

		this.init();
	}

	init() {
		// 1. Initialize static parallax layered edge parameters (Strictly 7 Layers)
		const layerCount = parseInt(this.config.parallax_layers || 7);
		for (let i = 0; i < layerCount; i++) {
			this.layers.push({
				depth: i + 1,
				opacity: 1.0 - (i * 0.08),
				scale: 1.0 + (i * 0.05),
				blur: i * 1.5,
				offsetX: 0,
				offsetY: 0
			});
		}

		// 2. Spawn Falling Leaves Entities
		const leavesCount = parseInt(this.config.leaf_count || 30);
		const leafTypes = this.getLeafTypesFromSet();

		for (let i = 0; i < leavesCount; i++) {
			const type = leafTypes[Math.floor(Math.random() * leafTypes.length)];
			this.leaves.push(new LeafEntity(this.width, this.height, this.config, type));
		}

		// 3. Spawn Floating Particles
		if (this.config.particles_enabled === '1') {
			const pCount = parseInt(this.config.particles_count || 50);
			for (let i = 0; i < pCount; i++) {
				this.particles.push(new ParticleEntity(this.width, this.height, this.config));
			}
		}

		// 4. Register Mouse, Touch & Gyro Event Listeners
		this.registerInteractionEvents();
	}

	getLeafTypesFromSet() {
		if (this.config.custom_leaf_image) {
			return ['custom_leaf'];
		}
		const set = this.config.leaf_svg_set || 'mixed';
		const allTypes = ['birch1', 'birch2', 'birch3', 'oak1', 'oak2', 'oak3', 'linden1', 'linden2', 'linden3', 'maple1', 'maple2', 'maple3', 'aspen1', 'aspen2', 'aspen3'];

		if (set === 'mixed') return allTypes;
		return allTypes.filter(t => t.startsWith(set));
	}

	registerInteractionEvents() {
		// Track Mouse Coordinate Vectors
		window.addEventListener('mousemove', (e) => {
			this.mouse.targetX = (e.clientX - window.innerWidth / 2) * 0.1;
			this.mouse.targetY = (e.clientY - window.innerHeight / 2) * 0.1;
		});

		window.addEventListener('touchmove', (e) => {
			if (e.touches.length > 0) {
				const touch = e.touches[0];
				this.mouse.targetX = (touch.clientX - window.innerWidth / 2) * 0.15;
				this.mouse.targetY = (touch.clientY - window.innerHeight / 2) * 0.15;
			}
		});

		// Dynamic Mobile Device Orientation Gyroscope Interactions (If not disabled by admin to save battery)
		if (this.config.mobile_disable_gyro !== '1') {
			window.addEventListener('deviceorientation', (e) => {
				if (e.gamma !== null && e.beta !== null) {
					this.orientation.gamma = e.gamma; // Horizontal tilt
					this.orientation.beta = e.beta;   // Vertical tilt
				}
			});
		}
	}

	resize(width, height) {
		const oldWidth = this.width;
		const oldHeight = this.height;
		this.width = width;
		this.height = height;

		// Adapt swaying leaf anchors dynamically to new screen borders on resize
		this.leaves.forEach(leaf => {
			if (leaf.mode === 'swaying') {
				if (leaf.side === 'right' && oldWidth > 0) {
					leaf.anchorX = width - (oldWidth - leaf.anchorX);
				} else if (leaf.side === 'left' && oldWidth > 0) {
					// Left side anchor remains relatively the same
					leaf.anchorX = Math.min(leaf.anchorX, 120);
				} else if (leaf.side === 'top' && oldWidth > 0) {
					leaf.anchorX = (leaf.anchorX / oldWidth) * width;
				}

				if (oldHeight > 0 && leaf.side !== 'top') {
					leaf.anchorY = (leaf.anchorY / oldHeight) * height;
				}
			}
		});
	}

	/**
	 * Main Engine tick update loop
	 *
	 * @param {number} deltaTime Frame time interval.
	 * @returns {{ wind: object, layers: Array, leaves: Array, particles: Array }}
	 */
	update(deltaTime) {
		// 1. Process Wind Simulation forces via Perlin Noise
		const windState = this.windSystem.update(deltaTime);

		// 2. Smoothly interpolate Mouse / Gyro inertia with configured smoothing factor
		const inertia = parseFloat(this.config.parallax_inertia || 8) * 0.01;
		this.mouse.x += (this.mouse.targetX - this.mouse.x) * inertia;
		this.mouse.y += (this.mouse.targetY - this.mouse.y) * inertia;

		// Calculate combined mouse & gyroscope tilt offset
		const gyroOffsetX = this.orientation.gamma * 2.0;
		const gyroOffsetY = (this.orientation.beta - 45) * 2.0; // Assume 45 degree viewing tilt baseline

		const finalOffsetX = this.mouse.x + gyroOffsetX;
		const finalOffsetY = this.mouse.y + gyroOffsetY;

		// 3. Apply Multi-Layer Parallax displacement vectors
		const speedMultiplier = parseFloat(this.config.parallax_speed || 5) * 0.1;

		this.layers.forEach((layer) => {
			// Offset increases deep with the depth coefficient
			const displacementFactor = layer.depth * speedMultiplier;
			layer.offsetX = finalOffsetX * displacementFactor;
			layer.offsetY = finalOffsetY * displacementFactor;
		});

		// 4. Update falling physical leaf entities
		this.leaves.forEach(leaf => {
			leaf.update(windState, this.width, this.height);
		});

		// 5. Update micro floating particles
		this.particles.forEach(p => {
			p.update(windState, this.width, this.height);
		});

		return {
			wind: windState,
			layers: this.layers,
			leaves: this.leaves,
			particles: this.particles
		};
	}
}

window.PremiumForestParallax = window.PremiumForestParallax || {};
window.PremiumForestParallax.ForestPhysicsEngine = ForestPhysicsEngine;
