/**
 * Premium Forest Parallax Admin JS
 */
document.addEventListener('DOMContentLoaded', function () {
	const tabs = document.querySelectorAll('.premium-forest-tab-link');
	const contents = document.querySelectorAll('.premium-forest-tab-content');

	tabs.forEach(tab => {
		tab.addEventListener('click', function (e) {
			e.preventDefault();

			const target = this.getAttribute('data-tab');

			tabs.forEach(t => t.classList.remove('active'));
			contents.forEach(c => c.classList.remove('active'));

			this.classList.add('active');
			const activeContent = document.getElementById('tab-' + target);
			if (activeContent) {
				activeContent.classList.add('active');
			}
		});
	});

	// WordPress native Media Uploader Integration
	const mediaButtons = document.querySelectorAll('.premium-media-upload');
	mediaButtons.forEach(button => {
		button.addEventListener('click', function (e) {
			e.preventDefault();
			const inputId = this.getAttribute('data-input');
			const inputField = document.getElementById(inputId);

			const customUploader = wp.media({
				title: 'Выбрать изображение',
				button: { text: 'Использовать' },
				multiple: false
			});

			customUploader.on('select', function () {
				const attachment = customUploader.state().get('selection').first().toJSON();
				if (inputField && attachment.url) {
					inputField.value = attachment.url;
				}
			});

			customUploader.open();
		});
	});

	// Interactive Real-Time Live Preview Simulator Handler
	const simulator = document.getElementById('premium-preview-simulator');
	const btnDesktop = document.getElementById('btn-preview-desktop');
	const btnMobile = document.getElementById('btn-preview-mobile');

	if (simulator && btnDesktop && btnMobile) {
		let currentMode = 'desktop'; // desktop or mobile

		const updateSimulatorOffsets = () => {
			const leftVal = document.getElementById(currentMode === 'desktop' ? 'offset_desktop_l' : 'offset_mobile_l').value || 0;
			const rightVal = document.getElementById(currentMode === 'desktop' ? 'offset_desktop_r' : 'offset_mobile_r').value || 0;
			const topVal = document.getElementById(currentMode === 'desktop' ? 'offset_desktop_t' : 'offset_mobile_t').value || 0;
			const bottomVal = document.getElementById(currentMode === 'desktop' ? 'offset_desktop_b' : 'offset_mobile_b').value || 0;

			// Translate simulator edges smoothly (with a division scale to fit mock size)
			const scale = 0.25; // 4px on real screen maps to 1px on mock preview
			simulator.querySelector('.sim-left').style.transform = `translateX(${-leftVal * scale}px)`;
			simulator.querySelector('.sim-right').style.transform = `translateX(${rightVal * scale}px)`;
			simulator.querySelector('.sim-top').style.transform = `translateY(${-topVal * scale}px)`;
			simulator.querySelector('.sim-bottom').style.transform = `translateY(${bottomVal * scale}px)`;
		};

		// Event listener on all numeric inputs
		document.querySelectorAll('.premium-offset-input').forEach(input => {
			input.addEventListener('input', updateSimulatorOffsets);
		});

		btnDesktop.addEventListener('click', function () {
			currentMode = 'desktop';
			btnDesktop.classList.add('active');
			btnMobile.classList.remove('active');
			simulator.style.width = '100%';
			simulator.style.height = '320px';
			updateSimulatorOffsets();
		});

		btnMobile.addEventListener('click', function () {
			currentMode = 'mobile';
			btnMobile.classList.add('active');
			btnDesktop.classList.remove('active');
			simulator.style.width = '240px';
			simulator.style.height = '350px';
			updateSimulatorOffsets();
		});

		// Initial render
		updateSimulatorOffsets();
	}
});
