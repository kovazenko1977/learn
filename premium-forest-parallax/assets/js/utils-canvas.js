/**
 * Improved Perlin Noise Generator in JS
 *
 * Implements a classic improved 1D & 2D Perlin noise algorithm for organic and realistic wind animations,
 * avoiding the artificial feel of pure sin/cos formulas.
 */
class PerlinNoise {
	constructor() {
		this.p = new Uint8Array(256);
		for (let i = 0; i < 256; i++) {
			this.p[i] = Math.floor(Math.random() * 256);
		}
		// Duplicate to avoid wrap-around bounds checking
		this.permutation = new Uint8Array(512);
		for (let i = 0; i < 512; i++) {
			this.permutation[i] = this.p[i & 255];
		}
	}

	fade(t) {
		return t * t * t * (t * (t * 6 - 15) + 10);
	}

	lerp(t, a, b) {
		return a + t * (b - a);
	}

	grad(hash, x, y, z) {
		const h = hash & 15;
		const u = h < 8 ? x : y;
		const v = h < 4 ? y : h === 12 || h === 14 ? x : z;
		return ((h & 1) === 0 ? u : -u) + ((h & 2) === 0 ? v : -v);
	}

	noise(x, y = 0, z = 0) {
		const X = Math.floor(x) & 255;
		const Y = Math.floor(y) & 255;
		const Z = Math.floor(z) & 255;

		x -= Math.floor(x);
		y -= Math.floor(y);
		z -= Math.floor(z);

		const u = this.fade(x);
		const v = this.fade(y);
		const w = this.fade(z);

		const A = this.permutation[X] + Y;
		const AA = this.permutation[A] + Z;
		const AB = this.permutation[A + 1] + Z;
		const B = this.permutation[X + 1] + Y;
		const BA = this.permutation[B] + Z;
		const BB = this.permutation[B + 1] + Z;

		return this.lerp(w, this.lerp(v, this.lerp(u, this.grad(this.permutation[AA], x, y, z),
													  this.grad(this.permutation[BA], x - 1, y, z)),
									  this.lerp(u, this.grad(this.permutation[AB], x, y - 1, z),
													  this.grad(this.permutation[BB], x - 1, y - 1, z))),
						  this.lerp(v, this.lerp(u, this.grad(this.permutation[AA + 1], x, y, z - 1),
													  this.grad(this.permutation[BA + 1], x - 1, y, z - 1)),
									  this.lerp(u, this.grad(this.permutation[AB + 1], x, y - 1, z - 1),
													  this.grad(this.permutation[BB + 1], x - 1, y - 1, z - 1))));
	}
}

/**
 * Wind Simulation Engine powered by Perlin Noise
 */
class WindSystem {
	constructor(config) {
		this.config = config;
		this.noiseGenerator = new PerlinNoise();
		this.time = 0;
	}

	/**
	 * Calculate current frame wind force and angle deviation using Perlin Noise.
	 *
	 * @param {number} deltaTime Frame time interval.
	 * @returns {{forceX: number, forceY: number, angle: number}}
	 */
	update(deltaTime) {
		const freq = (this.config.wind_frequency || 2) * 0.0005;
		this.time += deltaTime * freq;

		// Calculate organic fluctuations
		const noiseVal = this.noiseGenerator.noise(this.time, 0, 0);
		const gustVal = this.noiseGenerator.noise(this.time * 2.5, 5.2, 0) * (this.config.wind_gusts || 3);
		const randomness = this.noiseGenerator.noise(this.time * 5.0, 12.8, 3.4) * (this.config.wind_randomness || 5);

		const baseStrength = this.config.wind_strength || 5;
		const totalStrength = baseStrength + gustVal + (randomness * 0.2);

		// Angle in radians
		const baseAngleRad = ((this.config.wind_direction || 180) * Math.PI) / 180;
		const angleDeviation = noiseVal * 0.5; // Up to ~30 degrees deviation
		const currentAngle = baseAngleRad + angleDeviation;

		return {
			forceX: Math.cos(currentAngle) * totalStrength,
			forceY: Math.sin(currentAngle) * totalStrength,
			angle: currentAngle,
			intensity: totalStrength
		};
	}
}

/**
 * Device Performance Profiler & Benchmark
 * Determines hardware capabilities and profiles: Ultra, High, Medium, Low, Lite.
 */
class DeviceProfiler {
	static async getProfile(enableProfiling) {
		if (!enableProfiling) {
			return 'High';
		}

		const startTime = performance.now();
		let iterations = 0;
		// Micro benchmark for JS operations
		while (performance.now() - startTime < 16) {
			iterations += Math.sqrt(Math.random() * 1000);
		}

		// Detect attributes
		const hasWebGL2 = (() => {
			try {
				const canvas = document.createElement('canvas');
				return !!(window.WebGL2RenderingContext && canvas.getContext('webgl2'));
			} catch (e) {
				return false;
			}
		})();

		const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
		const isRetina = window.devicePixelRatio > 1;
		const cpuCores = navigator.hardwareConcurrency || 4;

		// Frame timing check (simple FPS estimate placeholder)
		const frameRate = 60; // Assumed default screen refresh

		if (!hasWebGL2) {
			return 'Lite'; // Fallback immediately
		}

		if (isTouch) {
			if (cpuCores >= 8 && iterations > 20000) {
				return 'Medium';
			}
			return 'Low';
		}

		if (cpuCores >= 12 && iterations > 40000) {
			return 'Ultra';
		} else if (cpuCores >= 8 && iterations > 25000) {
			return 'High';
		} else if (cpuCores >= 4 && iterations > 15000) {
			return 'Medium';
		} else {
			return 'Low';
		}
	}
}

/**
 * 2D Canvas Fallback Renderer for low performance profile or browsers missing WebGL2
 */
class Canvas2DRenderer {
	constructor(canvas, config, loadedAssets) {
		this.canvas = canvas;
		this.ctx = canvas.getContext('2d');
		this.config = config;
		this.loadedAssets = loadedAssets; // Map of leaf types to Image objects
	}

	resize(width, height) {
		this.canvas.width = width;
		this.canvas.height = height;
	}

	clear() {
		this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
	}

	/**
	 * Draw edge layers and dynamic leaves.
	 */
	render(layers, leaves, particles, fogOpacity) {
		this.clear();

		const width = this.canvas.width;
		const height = this.canvas.height;

		// 1. Draw static Parallax Layers
		layers.forEach(layer => {
			this.ctx.save();
			this.ctx.globalAlpha = layer.opacity || 1.0;
			if (layer.blur && layer.blur > 0) {
				this.ctx.filter = `blur(${layer.blur}px)`;
			}
			// Draw decorative border framing the edge (drawn directly via Canvas2D)
			this.drawEdgeFraming(layer, width, height);
			this.ctx.restore();
		});

		// 2. Draw Falling Leaves
		leaves.forEach(leaf => {
			if (!leaf.active) return;
			const img = this.loadedAssets[leaf.type];
			if (!img) return;

			this.ctx.save();
			this.ctx.translate(leaf.x, leaf.y);
			this.ctx.rotate(leaf.rotation);
			this.ctx.globalAlpha = leaf.opacity;

			const w = leaf.size;
			const h = leaf.size * 1.2; // Leaf aspect ratio
			this.ctx.drawImage(img, -w / 2, -h / 2, w, h);
			this.ctx.restore();
		});

		// 3. Draw Floating Micro Particles
		if (this.config.particles_enabled === '1') {
			particles.forEach(p => {
				this.ctx.save();
				this.ctx.fillStyle = p.color;
				this.ctx.globalAlpha = p.opacity;
				this.ctx.beginPath();
				this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
				this.ctx.fill();
				this.ctx.restore();
			});
		}

		// 4. Draw Atmospheric Fog Overlay
		if (this.config.fog_enabled === '1' && fogOpacity > 0) {
			this.ctx.save();
			this.ctx.fillStyle = `rgba(240, 245, 240, ${fogOpacity})`;
			this.ctx.fillRect(0, 0, width, height);
			this.ctx.restore();
		}
	}

	/**
	 * Draw branch border frame on screen edges with detailed leafy branches
	 */
	drawEdgeFraming(layer, width, height) {
		const ctx = this.ctx;
		if (this.config.edge_branches_enabled === '0') {
			return;
		}

		const baseWidth = parseFloat(this.config.edge_branches_width || 180);
		const density = parseInt(this.config.edge_branches_density || 8);
		const color1 = this.config.edge_branches_color || '#3d6a24';
		const color2 = this.config.edge_branches_color2 || '#2f541c';
		const swaySpeed = parseFloat(this.config.edge_branches_sway_speed || 10) * 0.1;
		const swayAmp = parseFloat(this.config.edge_branches_sway_amplitude || 15);

		// Calculate organic sway offset based on Perlin wind and time
		const time = performance.now() * 0.001 * swaySpeed;
		const sway = Math.sin(time + layer.depth) * swayAmp * (layer.depth * 0.2 + 0.4);

		// Draw decorative branch trunks and leafy stems on Left and Right borders
		ctx.save();

		// 1. Draw Left forest border
		ctx.fillStyle = layer.depth % 2 === 0 ? color1 : color2;
		ctx.strokeStyle = '#1b320f';
		ctx.lineWidth = 3;

		for (let i = 0; i <= density; i++) {
			const yAnchor = (height / density) * i;
			const branchLength = baseWidth * (0.6 + Math.sin(i * 1.7) * 0.3) * (1 / (layer.depth * 0.15 + 0.55));

			// Main branch stem
			ctx.beginPath();
			ctx.moveTo(0, yAnchor);
			const ctrlX = branchLength * 0.5 + sway;
			const ctrlY = yAnchor + Math.cos(time + i) * 20;
			const endX = branchLength + sway;
			const endY = yAnchor + Math.sin(time + i) * 20;

			ctx.quadraticCurveTo(ctrlX, ctrlY, endX, endY);
			ctx.stroke();

			// Draw multiple green leaves hanging on the branch
			for (let j = 2; j <= 6; j++) {
				const leafRatio = j / 6;
				const lx = endX * leafRatio;
				const ly = yAnchor + (endY - yAnchor) * leafRatio;

				ctx.save();
				ctx.translate(lx, ly);
				ctx.rotate(Math.sin(time * 1.5 + i + j) * 0.2 + (j * 0.5));

				// Draw a leaf shape
				ctx.beginPath();
				ctx.ellipse(0, 0, 15, 8, 0, 0, Math.PI * 2);
				ctx.fill();
				ctx.stroke();
				ctx.restore();
			}
		}

		// 2. Draw Right forest border
		ctx.fillStyle = layer.depth % 2 === 0 ? color2 : color1;
		for (let i = 0; i <= density; i++) {
			const yAnchor = (height / density) * i;
			const branchLength = baseWidth * (0.6 + Math.cos(i * 1.7) * 0.3) * (1 / (layer.depth * 0.15 + 0.55));

			ctx.beginPath();
			ctx.moveTo(width, yAnchor);
			const ctrlX = width - (branchLength * 0.5) + sway;
			const ctrlY = yAnchor + Math.sin(time * 0.8 + i) * 20;
			const endX = width - branchLength + sway;
			const endY = yAnchor + Math.cos(time * 0.8 + i) * 20;

			ctx.quadraticCurveTo(ctrlX, ctrlY, endX, endY);
			ctx.stroke();

			// Draw leaves
			for (let j = 2; j <= 6; j++) {
				const leafRatio = j / 6;
				const lx = width - (width - endX) * leafRatio;
				const ly = yAnchor + (endY - yAnchor) * leafRatio;

				ctx.save();
				ctx.translate(lx, ly);
				ctx.rotate(Math.cos(time * 1.5 + i + j) * 0.2 - (j * 0.5));

				ctx.beginPath();
				ctx.ellipse(0, 0, 15, 8, 0, 0, Math.PI * 2);
				ctx.fill();
				ctx.stroke();
				ctx.restore();
			}
		}

		ctx.restore();
	}
}

// Export classes to global window scope for loader consumption
window.PremiumForestParallax = window.PremiumForestParallax || {};
window.PremiumForestParallax.PerlinNoise = PerlinNoise;
window.PremiumForestParallax.WindSystem = WindSystem;
window.PremiumForestParallax.DeviceProfiler = DeviceProfiler;
window.PremiumForestParallax.Canvas2DRenderer = Canvas2DRenderer;
