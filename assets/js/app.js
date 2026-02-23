document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.querySelector('.start-button');
    const sidebar = document.getElementById('sidebar');

    if (startButton && sidebar) {
        startButton.addEventListener('click', function(e) {
            sidebar.classList.toggle('active');
            e.stopPropagation();
        });
    }

    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== startButton) {
            sidebar.classList.remove('active');
        }

        // Icon error handling
        document.querySelectorAll('.desktop-icon img').forEach(img => {
            img.onerror = function() {
                this.style.display = 'none';
                const fallback = document.createElement('div');
                fallback.style.fontSize = '24px';
                fallback.innerHTML = '📁'; // Default fallback
                this.parentNode.insertBefore(fallback, this);
            };
        });

        // Desktop icon selection
        const icons = document.querySelectorAll('.desktop-icon');
        icons.forEach(icon => {
            if (icon.contains(e.target)) {
                icons.forEach(i => i.classList.remove('selected'));
                icon.classList.add('selected');
            } else if (!e.target.closest('.desktop-icon')) {
                icon.classList.remove('selected');
            }
        });
    });
});
