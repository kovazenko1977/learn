/**
 * WebGL2 Renderer powered by Three.js
 *
 * Combines dynamic 3D leaf models, Edge branch layers, custom GLSL post-processing effects,
 * Bloom, Gaussian Blur, Depth Fog, and interactive Lighting.
 */
class WebGLRenderer {
	constructor(canvas, config, loadedAssets) {
		this.canvas = canvas;
		this.config = config;
		this.loadedAssets = loadedAssets; // Map of leaf types to loaded textures

		this.scene = null;
		this.camera = null;
		this.renderer = null;

		// Post processing meshes/passes
		this.composer = null;
		this.renderTarget = null;
		this.passes = [];

		this.leafMeshes = [];
		this.branchGroups = [];
		this.particleSystem = null;

		this.init();
	}

	init() {
		const width = this.canvas.clientWidth || window.innerWidth;
		const height = this.canvas.clientHeight || window.innerHeight;

		// 1. Scene & Camera Setup (Orthographic Camera to overlay 2.5D layer space)
		this.scene = new THREE.Scene();
		this.camera = new THREE.OrthographicCamera(
			-width / 2, width / 2,
			height / 2, -height / 2,
			1, 1000
		);
		this.camera.position.z = 100;

		// 2. WebGLRenderer Setup
		this.renderer = new THREE.WebGLRenderer({
			canvas: this.canvas,
			alpha: true,
			antialias: true,
			powerPreference: "high-performance"
		});
		this.renderer.setSize(width, height, false);
		this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

		// 3. Ambient & Soft Spot Lights
		const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
		this.scene.add(ambientLight);

		if (this.config.light_soft_light === '1') {
			const dirLight = new THREE.DirectionalLight(0xffffe0, 1.2);
			dirLight.position.set(200, 400, 200);
			this.scene.add(dirLight);
		}

		// 4. Create WebGL Post-Processing Passes using Custom GLSL Shaders
		this.setupPostProcessing(width, height);
	}

	setupPostProcessing(width, height) {
		// Custom render passes constructed manually utilizing standard THREE.ShaderMaterial
		this.renderTarget = new THREE.WebGLRenderTarget(width, height, {
			minFilter: THREE.LinearFilter,
			magFilter: THREE.LinearFilter,
			format: THREE.RGBAFormat
		});

		// Create post processing pass chain manually
		const orthoCamera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
		const quadGeometry = new THREE.PlaneGeometry(2, 2);

		// Material for Bloom
		this.bloomMaterial = new THREE.ShaderMaterial({
			vertexShader: `varying vec2 vUv; void main() { vUv = uv; gl_Position = vec4(position, 1.0); }`,
			fragmentShader: window.PremiumForestShaders.bloomFrag,
			uniforms: {
				tDiffuse: { value: null },
				uThreshold: { value: 0.6 },
				uIntensity: { value: 0.8 }
			}
		});

		// Material for Blur
		this.blurMaterial = new THREE.ShaderMaterial({
			vertexShader: `varying vec2 vUv; void main() { vUv = uv; gl_Position = vec4(position, 1.0); }`,
			fragmentShader: window.PremiumForestShaders.gaussianBlurFrag,
			uniforms: {
				tDiffuse: { value: null },
				uResolution: { value: new THREE.Vector2(width, height) },
				uBlurRadius: { value: parseFloat(this.config.fog_blur || 10) }
			}
		});

		// Material for Depth Fog
		this.fogMaterial = new THREE.ShaderMaterial({
			vertexShader: `varying vec2 vUv; void main() { vUv = uv; gl_Position = vec4(position, 1.0); }`,
			fragmentShader: window.PremiumForestShaders.depthFogFrag,
			uniforms: {
				tDiffuse: { value: null },
				uFogDensity: { value: parseFloat((this.config.fog_opacity || 30) / 100) },
				uFogColor: { value: new THREE.Color(0xf0f5f0) }
			}
		});

		// Create scene passes
		this.bloomQuad = new THREE.Mesh(quadGeometry, this.bloomMaterial);
		this.blurQuad = new THREE.Mesh(quadGeometry, this.blurMaterial);
		this.fogQuad = new THREE.Mesh(quadGeometry, this.fogMaterial);

		this.postScene = new THREE.Scene();
		this.postCamera = orthoCamera;
	}

	resize(width, height) {
		this.camera.left = -width / 2;
		this.camera.right = width / 2;
		this.camera.top = height / 2;
		this.camera.bottom = -height / 2;
		this.camera.updateProjectionMatrix();

		this.renderer.setSize(width, height, false);
		this.renderTarget.setSize(width, height);
		this.blurMaterial.uniforms.uResolution.value.set(width, height);
	}

	/**
	 * Prepare static and dynamic layers for the scene
	 */
	setupScene(layers, maxLeavesCount, particlesArray) {
		// 1. Build edge branches framework with lush procedural foliage
		const baseWidth = parseFloat(this.config.edge_branches_width || 180);
		const density = parseInt(this.config.edge_branches_density || 8);
		const leafColor = new THREE.Color(this.config.edge_branches_color || '#3d6a24');
		const leafColorShadow = new THREE.Color(this.config.edge_branches_color2 || '#2f541c');

		layers.forEach(layer => {
			const group = new THREE.Group();
			group.position.z = -layer.depth * 10;

			if (this.config.edge_branches_enabled !== '0') {
				const customBranchTex = this.loadedAssets['custom_branch'];

				if (customBranchTex) {
					// Draw Left & Right forest borders using custom branch texture
					const branchGeo = new THREE.PlaneGeometry(baseWidth, baseWidth);
					const branchMat = new THREE.MeshBasicMaterial({
						map: customBranchTex,
						transparent: true,
						side: THREE.DoubleSide
					});

					// Left side
					for (let i = 0; i <= density; i++) {
						const y = -window.innerHeight / 2 + (window.innerHeight / density) * i;
						const branchMesh = new THREE.Mesh(branchGeo, branchMat);
						branchMesh.position.set(-window.innerWidth / 2 + baseWidth / 2, y, 0);
						branchMesh.name = "left_branch_" + i;
						group.add(branchMesh);
					}

					// Right side
					for (let i = 0; i <= density; i++) {
						const y = -window.innerHeight / 2 + (window.innerHeight / density) * i;
						const branchMesh = new THREE.Mesh(branchGeo, branchMat);
						branchMesh.position.set(window.innerWidth / 2 - baseWidth / 2, y, 0);
						branchMesh.scale.x = -1; // Mirror horizontally
						branchMesh.name = "right_branch_" + i;
						group.add(branchMesh);
					}
				} else {
					// Procedural twig branch geometry setup
					const branchMat = new THREE.LineBasicMaterial({ color: 0x1b320f, linewidth: 4 });
					const leafMat = new THREE.MeshBasicMaterial({
						color: layer.depth % 2 === 0 ? leafColor : leafColorShadow,
						side: THREE.DoubleSide
					});

					const leafGeo = new THREE.PlaneGeometry(24, 12);

					// Create stems on Left side
					for (let i = 0; i <= density; i++) {
						const y = -window.innerHeight / 2 + (window.innerHeight / density) * i;
						const branchLength = baseWidth * (0.6 + Math.sin(i * 1.5) * 0.3) * (1 / (layer.depth * 0.15 + 0.55));

						const points = [];
						points.push(new THREE.Vector3(-window.innerWidth / 2, y, 0));
						points.push(new THREE.Vector3(-window.innerWidth / 2 + branchLength * 0.5, y + Math.sin(i) * 30, 0));
						points.push(new THREE.Vector3(-window.innerWidth / 2 + branchLength, y + Math.cos(i) * 30, 0));

						const curve = new THREE.QuadraticBezierCurve3(points[0], points[1], points[2]);
						const bGeo = new THREE.BufferGeometry().setFromPoints(curve.getPoints(10));
						const branchLine = new THREE.Line(bGeo, branchMat);
						group.add(branchLine);

						// Add leaves along the curves
						for (let j = 2; j <= 6; j++) {
							const t = j / 6;
							const p = curve.getPoint(t);
							const leafMesh = new THREE.Mesh(leafGeo, leafMat);
							leafMesh.position.copy(p);
							leafMesh.rotation.z = Math.sin(i + j) * 0.5 + (j * 0.3);
							group.add(leafMesh);
						}
					}

					// Create stems on Right side
					for (let i = 0; i <= density; i++) {
						const y = -window.innerHeight / 2 + (window.innerHeight / density) * i;
						const branchLength = baseWidth * (0.6 + Math.cos(i * 1.5) * 0.3) * (1 / (layer.depth * 0.15 + 0.55));

						const points = [];
						points.push(new THREE.Vector3(window.innerWidth / 2, y, 0));
						points.push(new THREE.Vector3(window.innerWidth / 2 - branchLength * 0.5, y + Math.cos(i) * 30, 0));
						points.push(new THREE.Vector3(window.innerWidth / 2 - branchLength, y + Math.sin(i) * 30, 0));

						const curve = new THREE.QuadraticBezierCurve3(points[0], points[1], points[2]);
						const bGeo = new THREE.BufferGeometry().setFromPoints(curve.getPoints(10));
						const branchLine = new THREE.Line(bGeo, branchMat);
						group.add(branchLine);

						// Add leaves
						for (let j = 2; j <= 6; j++) {
							const t = j / 6;
							const p = curve.getPoint(t);
							const leafMesh = new THREE.Mesh(leafGeo, leafMat);
							leafMesh.position.copy(p);
							leafMesh.rotation.z = Math.cos(i + j) * 0.5 - (j * 0.3);
							group.add(leafMesh);
						}
					}
				}
			}

			this.scene.add(group);
			this.branchGroups.push({ group, layer });
		});

		// 2. Prepare Leaf Mesh Pool
		const leafGeo = new THREE.PlaneGeometry(1, 1);
		for (let i = 0; i < maxLeavesCount; i++) {
			const leafMat = new THREE.ShaderMaterial({
				vertexShader: window.PremiumForestShaders.windDistortionVert,
				fragmentShader: window.PremiumForestShaders.defaultFrag,
				uniforms: {
					uTexture: { value: null },
					uOpacity: { value: 1.0 },
					uColorTint: { value: new THREE.Color(1, 1, 1) },
					uTime: { value: 0 },
					uWindStrength: { value: 0 },
					uWindDir: { value: new THREE.Vector2(0, 0) }
				},
				transparent: true,
				depthWrite: false
			});

			const mesh = new THREE.Mesh(leafGeo, leafMat);
			mesh.visible = false;
			this.scene.add(mesh);
			this.leafMeshes.push(mesh);
		}

		// 3. Prepare Floating Particles Buffer
		if (this.config.particles_enabled === '1' && particlesArray.length > 0) {
			const particleGeo = new THREE.BufferGeometry();
			const count = particlesArray.length;
			const positions = new Float32Array(count * 3);
			const colors = new Float32Array(count * 3);

			particlesArray.forEach((p, idx) => {
				positions[idx * 3] = p.x - window.innerWidth / 2;
				positions[idx * 3 + 1] = window.innerHeight / 2 - p.y;
				positions[idx * 3 + 2] = -50; // Middle layer depth

				// Assign soft yellow/amber pollen color values
				colors[idx * 3] = 0.95;
				colors[idx * 3 + 1] = 0.95;
				colors[idx * 3 + 2] = 0.75;
			});

			particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
			particleGeo.setAttribute('color', new THREE.BufferAttribute(colors, 3));

			const pMaterial = new THREE.PointsMaterial({
				size: parseFloat(this.config.particles_size || 4),
				vertexColors: true,
				transparent: true,
				opacity: 0.8
			});

			this.particleSystem = new THREE.Points(particleGeo, pMaterial);
			this.scene.add(this.particleSystem);
		}
	}

	render(layers, leaves, particles, windState, time) {
		const width = this.canvas.width;
		const height = this.canvas.height;

		// 1. Sync branch/border groups with parallax state
		this.branchGroups.forEach(item => {
			const offset = item.layer.offsetY || 0;
			const scale = item.layer.scale || 1.0;
			item.group.position.y = offset;
			item.group.scale.set(scale, scale, 1.0);
		});

		// 2. Sync active falling leaves meshes
		this.leafMeshes.forEach(mesh => { mesh.visible = false; });

		leaves.forEach((leaf, idx) => {
			if (!leaf.active || idx >= this.leafMeshes.length) return;
			const mesh = this.leafMeshes[idx];
			const tex = this.loadedAssets[leaf.type];

			if (tex) {
				mesh.material.uniforms.uTexture.value = tex;
				mesh.material.uniforms.uOpacity.value = leaf.opacity;
				mesh.material.uniforms.uTime.value = time;
				mesh.material.uniforms.uWindStrength.value = windState.intensity;
				mesh.material.uniforms.uWindDir.value.set(windState.forceX, windState.forceY);

				if (this.config.leaf_random_color === '1') {
					// Use organic green-orange variations
					mesh.material.uniforms.uColorTint.value.setRGB(0.9, 0.7, 0.4);
				} else {
					mesh.material.uniforms.uColorTint.value.setRGB(1.0, 1.0, 1.0);
				}

				// Map coordinate systems: HTML5 Top-Left to WebGL center origin
				mesh.position.set(
					leaf.x - width / 2,
					height / 2 - leaf.y,
					-leaf.depth * 5
				);
				mesh.rotation.z = leaf.rotation;
				mesh.scale.set(leaf.size, leaf.size * 1.2, 1);
				mesh.visible = true;
			}
		});

		// 3. Update particle dynamic buffers
		if (this.particleSystem && this.config.particles_enabled === '1') {
			const positions = this.particleSystem.geometry.attributes.position.array;
			particles.forEach((p, idx) => {
				if (idx * 3 < positions.length) {
					positions[idx * 3] = p.x - width / 2;
					positions[idx * 3 + 1] = height / 2 - p.y;
				}
			});
			this.particleSystem.geometry.attributes.position.needsUpdate = true;
		}

		// 4. Post-processing shader chain rendering
		this.renderer.setRenderTarget(this.renderTarget);
		this.renderer.render(this.scene, this.camera);

		// Post-processing shaders sequentially (Bloom/Blur/Depth Fog)
		// Set default scene render pass output texture as source
		let currentSource = this.renderTarget.texture;

		// Setup rendering on the post-processing Ortho camera Scene
		this.renderer.setRenderTarget(null);

		// Multi-Pass Chain Step A: Gaussian Blur Pass
		if (this.config.webgl_blur_pass === '1') {
			this.blurMaterial.uniforms.tDiffuse.value = currentSource;
			this.postScene.add(this.blurQuad);
			this.renderer.render(this.postScene, this.postCamera);
			this.postScene.remove(this.blurQuad);
		}

		// Multi-Pass Chain Step B: Bloom & Fog Layer Pass
		if (this.config.webgl_bloom_pass === '1') {
			this.bloomMaterial.uniforms.tDiffuse.value = currentSource;
			this.postScene.add(this.bloomQuad);
			this.renderer.render(this.postScene, this.postCamera);
			this.postScene.remove(this.bloomQuad);
		} else if (this.config.webgl_fog_pass === '1') {
			this.fogMaterial.uniforms.tDiffuse.value = currentSource;
			this.postScene.add(this.fogQuad);
			this.renderer.render(this.postScene, this.postCamera);
			this.postScene.remove(this.fogQuad);
		} else {
			// Plain pass through to the screen
			this.renderer.setRenderTarget(null);
			this.renderer.render(this.scene, this.camera);
		}
	}
}

window.PremiumForestParallax = window.PremiumForestParallax || {};
window.PremiumForestParallax.WebGLRenderer = WebGLRenderer;
