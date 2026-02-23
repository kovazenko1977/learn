document.addEventListener('DOMContentLoaded', function() {
    // Mobile Detection
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;
    if (isMobile) {
        document.body.classList.add('mobile-mode');
    }

    const startButton = document.querySelector('.start-button');
    const sidebar = document.getElementById('sidebar');

    if (startButton && sidebar) {
        startButton.addEventListener('click', function(e) {
            sidebar.classList.toggle('active');
            e.stopPropagation();
        });
    }

    // PWA Install Prompt handling
    let deferredPrompt;
    const installBanner = document.getElementById('install-banner');
    const installBtn = document.getElementById('install-btn');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (isMobile && installBanner) {
            installBanner.style.display = 'flex';
        }
    });

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installBanner.style.display = 'none';
                }
                deferredPrompt = null;
            }
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
