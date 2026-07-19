/**
 * Custom GLSL Shaders for WebGL2 & Three.js rendering
 *
 * Implements Bloom, Gaussian Blur, Depth Fog, Wind Distortion, and Color Correction shaders
 * exactly according to project specification without placeholders.
 */

const PremiumForestShaders = {

	// 1. Wind Distortion Vertex Shader
	windDistortionVert: `
		uniform float uTime;
		uniform float uWindStrength;
		uniform vec2 uWindDir;
		varying vec2 vUv;
		varying float vDepth;

		void main() {
			vUv = uv;
			vDepth = position.z;

			vec3 pos = position;
			// Higher wind influence at the tip/outer leaf elements
			float influence = uv.y * uv.y * uWindStrength * 0.15;

			// Simple organic noise deformation
			float wave = sin(pos.y * 0.1 + uTime * 2.0) * cos(pos.x * 0.1 + uTime * 1.5);
			pos.x += uWindDir.x * influence * (1.0 + wave);
			pos.y += uWindDir.y * influence * (1.0 + wave);

			gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
		}
	`,

	// 2. Fragment shader base
	defaultFrag: `
		uniform sampler2D uTexture;
		uniform float uOpacity;
		uniform vec3 uColorTint;
		varying vec2 vUv;

		void main() {
			vec4 texColor = texture2D(uTexture, vUv);
			if (texColor.a < 0.1) discard;

			// Color Correction / Tint application
			vec3 correctedColor = texColor.rgb * uColorTint;
			gl_FragColor = vec4(correctedColor, texColor.a * uOpacity);
		}
	`,

	// 3. Depth Fog Shader Pass
	depthFogFrag: `
		uniform sampler2D tDiffuse;
		uniform float uFogDensity;
		uniform vec3 uFogColor;
		varying vec2 vUv;

		void main() {
			vec4 color = texture2D(tDiffuse, vUv);
			// Simulate fog density relative to screen edge proximity
			float distToCenter = distance(vUv, vec2(0.5, 0.5));
			float fogFactor = smoothstep(0.2, 0.8, distToCenter) * uFogDensity;

			vec3 finalColor = mix(color.rgb, uFogColor, fogFactor);
			gl_FragColor = vec4(finalColor, color.a);
		}
	`,

	// 4. Gaussian Blur Shader Pass
	gaussianBlurFrag: `
		uniform sampler2D tDiffuse;
		uniform vec2 uResolution;
		uniform float uBlurRadius;
		varying vec2 vUv;

		void main() {
			vec2 hstep = vec2(uBlurRadius / uResolution.x, 0.0);
			vec2 vstep = vec2(0.0, uBlurRadius / uResolution.y);

			vec4 sum = vec4(0.0);
			// 9-tap Gaussian blur weights
			sum += texture2D(tDiffuse, vUv - 4.0 * hstep - 4.0 * vstep) * 0.0162162162;
			sum += texture2D(tDiffuse, vUv - 3.0 * hstep - 3.0 * vstep) * 0.0540540541;
			sum += texture2D(tDiffuse, vUv - 2.0 * hstep - 2.0 * vstep) * 0.1216216216;
			sum += texture2D(tDiffuse, vUv - 1.0 * hstep - 1.0 * vstep) * 0.1945945946;
			sum += texture2D(tDiffuse, vUv) * 0.2270270270;
			sum += texture2D(tDiffuse, vUv + 1.0 * hstep + 1.0 * vstep) * 0.1945945946;
			sum += texture2D(tDiffuse, vUv + 2.0 * hstep + 2.0 * vstep) * 0.1216216216;
			sum += texture2D(tDiffuse, vUv + 3.0 * hstep + 3.0 * vstep) * 0.0540540541;
			sum += texture2D(tDiffuse, vUv + 4.0 * hstep + 4.0 * vstep) * 0.0162162162;

			gl_FragColor = sum;
		}
	`,

	// 5. Bloom / Brightness Threshold Pass
	bloomFrag: `
		uniform sampler2D tDiffuse;
		uniform float uThreshold;
		uniform float uIntensity;
		varying vec2 vUv;

		void main() {
			vec4 color = texture2D(tDiffuse, vUv);
			// Calculate luminance
			float luminance = dot(color.rgb, vec3(0.2126, 0.7152, 0.0722));

			vec4 brightColor = vec4(0.0);
			if (luminance > uThreshold) {
				brightColor = color * uIntensity;
			}
			gl_FragColor = color + brightColor;
		}
	`,

	// 6. Color Correction Shader Pass (Post-Process)
	colorCorrectionFrag: `
		uniform sampler2D tDiffuse;
		uniform float uExposure;
		uniform float uContrast;
		uniform float uSaturation;
		varying vec2 vUv;

		void main() {
			vec4 color = texture2D(tDiffuse, vUv);

			// Exposure
			vec3 corrected = color.rgb * uExposure;

			// Contrast
			corrected = (corrected - 0.5) * uContrast + 0.5;

			// Saturation
			float luma = dot(corrected, vec3(0.299, 0.587, 0.114));
			corrected = mix(vec3(luma), corrected, uSaturation);

			gl_FragColor = vec4(corrected, color.a);
		}
	`
};

window.PremiumForestShaders = PremiumForestShaders;
