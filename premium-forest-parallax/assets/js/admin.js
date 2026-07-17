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
});
