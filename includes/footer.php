        <div style="text-align: center; padding: 20px; font-size: 11px; opacity: 0.5; color: var(--win-text-secondary); margin-bottom: 20px;">
            Разработчик wes.by
        </div>
    </main>
    <div id="pwa-install-banner">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; background:rgba(255,255,255,0.2); border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <i data-lucide="download-cloud"></i>
            </div>
            <div>
                <div style="font-weight:700; font-size:14px;">Установить ХОП</div>
                <div style="font-size:12px; opacity:0.8;">Добавьте на главный экран</div>
            </div>
        </div>
        <div style="display:flex; gap:8px;">
            <button id="pwa-install-btn" class="btn-primary" style="background:white; color:var(--win-accent); padding:8px 16px; font-size:12px;">Установить</button>
            <button id="pwa-close-btn" style="background:none; border:none; color:white; cursor:pointer; padding:4px;"><i data-lucide="x" style="width:18px;"></i></button>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // PWA Install Logic
        let deferredPrompt;
        const pwaBanner = document.getElementById('pwa-install-banner');
        const installBtn = document.getElementById('pwa-install-btn');
        const closeBtn = document.getElementById('pwa-close-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;

            // Don't show if already in standalone mode
            if (window.matchMedia('(display-mode: standalone)').matches) {
                return;
            }

            // Show the banner if on mobile or if we want to be proactive
            if (window.innerWidth < 992) {
                pwaBanner.style.display = 'flex';
            }
        });

        // Optional: Proactive check for iOS or other browsers where beforeinstallprompt doesn't fire
        window.addEventListener('load', () => {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;

            if (isIOS && !isStandalone && window.innerWidth < 992) {
                // For iOS we could show instructions, but let's stick to the banner for now
                // just changing the text if it's iOS
                const desc = pwaBanner.querySelector('div > div:last-child');
                if (desc) desc.textContent = 'Нажмите "Поделиться" и "На экран Домой"';
                const installBtn = document.getElementById('pwa-install-btn');
                if (installBtn) installBtn.style.display = 'none'; // iOS doesn't support programmatic install

                // Show after a delay
                setTimeout(() => {
                    pwaBanner.style.display = 'flex';
                }, 3000);
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    pwaBanner.style.display = 'none';
                }
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                pwaBanner.style.display = 'none';
            });
        }

        // Loading indicator logic
        window.addEventListener('beforeunload', function() {
            document.getElementById('loading-overlay').style.opacity = '1';
            document.getElementById('loading-overlay').style.pointerEvents = 'all';
        });

        // Hide loading on page load
        window.addEventListener('load', function() {
            document.getElementById('loading-overlay').style.opacity = '0';
            document.getElementById('loading-overlay').style.pointerEvents = 'none';
        });

        // Form submission loading
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loading-overlay').style.opacity = '1';
                document.getElementById('loading-overlay').style.pointerEvents = 'all';
            });
        });
    </script>
</body>
</html>
