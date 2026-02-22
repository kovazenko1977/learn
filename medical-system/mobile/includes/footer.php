</main>

<?php if (\Medical\Core\Auth::isLoggedIn()):
    $currentPage = basename($_SERVER['PHP_SELF']);
?>
    <nav class="md-bottom-nav">
        <a href="index.php" class="nav-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="home"></i></div>
            <span>Главная</span>
        </a>
        <a href="patients.php" class="nav-item <?php echo $currentPage === 'patients.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="users"></i></div>
            <span>Пациенты</span>
        </a>
        <a href="attendance.php" class="nav-item <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container"><i data-lucide="check-square"></i></div>
            <span>Прием</span>
        </a>
    </nav>
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
</script>
</body>
</html>
