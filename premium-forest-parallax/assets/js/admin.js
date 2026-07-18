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
});
