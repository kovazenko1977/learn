</main>

<?php if (\Medical\Core\Auth::isLoggedIn()):
    $currentPage = basename($_SERVER['PHP_SELF']);
?>
    <nav class="md-bottom-nav">
        <a href="index.php" class="nav-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="home"></i></div>
            <span>Обзор</span>
        </a>
        <a href="patients.php" class="nav-item <?php echo ($currentPage === 'patients.php' || $currentPage === 'patient_details.php' || $currentPage === 'patient_form.php') ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="users"></i></div>
            <span>Реестр</span>
        </a>
        <a href="attendance.php" class="nav-item <?php echo ($currentPage === 'attendance.php' || $currentPage === 'schedule_assign.php') ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="calendar"></i></div>
            <span>График</span>
        </a>
        <a href="cashier.php" class="nav-item <?php echo $currentPage === 'cashier.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="wallet"></i></div>
            <span>Касса</span>
        </a>
        <a href="booking.php" class="nav-item <?php echo $currentPage === 'booking.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="home"></i></div>
            <span>Бронь</span>
        </a>
        <button onclick="document.getElementById('mobile-more-menu').style.display='flex'" class="nav-item" style="background: none; border: none; padding: 0;">
            <div class="nav-icon-container"><i data-lucide="more-horizontal"></i></div>
            <span>Еще</span>
        </button>
    </nav>

    <!-- More Menu (Bottom Sheet Style) -->
    <div id="mobile-more-menu" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 4000; align-items: flex-end;">
        <div style="width: 100%; background: #fff; border-radius: 24px 24px 0 0; padding: 16px; animation: mdSlideUp 0.3s ease-out;">
            <div style="width: 32px; height: 4px; background: #eee; border-radius: 2px; margin: 0 auto 16px;"></div>

            <a href="analytics.php" style="display: flex; align-items: center; gap: 16px; padding: 12px; text-decoration: none; color: inherit;">
                <div style="width: 40px; height: 40px; background: #F3EDF7; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--md-primary);"><i data-lucide="bar-chart-2"></i></div>
                <div style="font-weight: 500;">Аналитика</div>
            </a>
            <a href="settings.php" style="display: flex; align-items: center; gap: 16px; padding: 12px; text-decoration: none; color: inherit;">
                <div style="width: 40px; height: 40px; background: #F3EDF7; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--md-primary);"><i data-lucide="settings"></i></div>
                <div style="font-weight: 500;">Настройки</div>
            </a>
            <a href="lab_results.php" style="display: flex; align-items: center; gap: 16px; padding: 12px; text-decoration: none; color: inherit;">
                <div style="width: 40px; height: 40px; background: #F3EDF7; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: var(--md-primary);"><i data-lucide="microscope"></i></div>
                <div style="font-weight: 500;">Анализы (поиск)</div>
            </a>

            <hr style="border: 0; border-top: 1px solid #CAC4D0; margin: 8px 0;">

            <a href="login.php?logout=1" style="display: flex; align-items: center; gap: 16px; padding: 12px; text-decoration: none; color: var(--md-error);">
                <div style="width: 40px; height: 40px; background: #F9DEDC; border-radius: 20px; display: flex; align-items: center; justify-content: center;"><i data-lucide="log-out"></i></div>
                <div style="font-weight: 500;">Выйти из системы</div>
            </a>

            <button onclick="document.getElementById('mobile-more-menu').style.display='none'" class="md-btn md-btn-primary" style="width: 100%; margin-top: 16px; border-radius: 24px; height: 48px;">Закрыть</button>
        </div>
    </div>
<?php endif; ?>

<div id="pwa-install-banner" style="display: none; position: fixed; bottom: 80px; left: 16px; right: 16px; background: #fff; padding: 16px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 2000; border: 1px solid #eee;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <div style="width: 40px; height: 40px; background: #0078d4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
            <i data-lucide="activity"></i>
        </div>
        <div style="flex-grow: 1;">
            <div style="font-weight: 500; font-size: 15px;">Установить WES МЕД</div>
            <div style="font-size: 13px; color: #666;">Добавьте приложение на главный экран</div>
        </div>
        <button onclick="document.getElementById('pwa-install-banner').style.display='none'" style="background: none; border: none; color: #999;">
            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        </button>
    </div>
    <button id="pwa-install-btn" class="md-btn md-btn-primary" style="width: 100%; border-radius: 8px; height: 40px;">Установить</button>
</div>

<div id="ios-install-hint" style="display: none; position: fixed; bottom: 80px; left: 16px; right: 16px; background: #fff; padding: 16px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 2000; border: 1px solid #eee;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <div style="width: 40px; height: 40px; background: #0078d4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
            <i data-lucide="activity"></i>
        </div>
        <div style="flex-grow: 1;">
            <div style="font-weight: 500; font-size: 15px;">Установить WES МЕД</div>
            <div style="font-size: 13px; color: #666;">Нажмите <i data-lucide="share" style="width: 16px; height: 16px; vertical-align: middle;"></i> а затем «На экран "Домой"»</div>
        </div>
        <button onclick="document.getElementById('ios-install-hint').style.display='none'" style="background: none; border: none; color: #999;">
            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        </button>
    </div>
</div>

<script>
    lucide.createIcons();

    let deferredPrompt;
    const installBanner = document.getElementById('pwa-install-banner');
    const installBtn = document.getElementById('pwa-install-btn');
    const iosHint = document.getElementById('ios-install-hint');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        installBanner.style.display = 'block';
    });

    installBtn.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            installBanner.style.display = 'none';
        }
    });

    // iOS detection
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;

    if (isIos && !isStandalone) {
        // Show iOS hint after a delay
        setTimeout(() => {
            iosHint.style.display = 'block';
        }, 2000);
    }

    // Mobile Toast System
    window.showToast = function(message, type = 'info') {
        const container = document.getElementById('mobile-toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'md-card';
        toast.style.cssText = `
            margin: 8px 16px;
            padding: 12px 16px;
            background: ${type === 'error' ? '#F9DEDC' : '#EADDFF'};
            color: ${type === 'error' ? '#410E0B' : '#21005D'};
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            animation: mdSlideUp 0.3s ease-out;
            font-size: 14px;
        `;

        const icon = type === 'error' ? 'alert-circle' : 'info';
        toast.innerHTML = `
            <i data-lucide="${icon}" style="width:20px; height:20px;"></i>
            <span style="flex-grow:1;">${message}</span>
        `;

        container.appendChild(toast);
        lucide.createIcons();

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    };

    // Form Preloader
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            if (form.checkValidity && !form.checkValidity()) return;
            const p = document.getElementById('global-preloader');
            if (p) p.style.display = 'flex';
        });
    });
</script>

<div id="mobile-toast-container" style="position: fixed; bottom: 90px; left: 0; width: 100%; z-index: 3000; pointer-events: none;"></div>

<style>
    @keyframes mdSlideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>
</body>
</html>
