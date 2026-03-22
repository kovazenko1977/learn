if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').then((registration) => {
      console.log('SW registered: ', registration);
    }).catch((registrationError) => {
      console.log('SW registration failed: ', registrationError);
    });
  });
}

let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  // Trigger immediately as requested
  showInstallPromotion();
});

// Force check for installation state on load
window.addEventListener('load', () => {
    if (!isInStandaloneMode()) {
        // If it's not iOS (which has its own check), we might still want to show a generic prompt
        // if the browser supports beforeinstallprompt it will trigger above.
        // For browsers that don't support it, we could show a generic message.
    }
});

function showInstallPromotion() {
    const promptEl = document.createElement('div');
    promptEl.className = 'install-prompt';
    promptEl.style.animation = 'slideUp 0.5s forwards';
    promptEl.innerHTML = `
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/img/icon-192.png" width="50" style="border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            <div style="flex: 1;">
                <p style="margin: 0; font-weight: bold; font-size: 16px;">Установите Жанну</p>
                <p style="margin: 3px 0 0; font-size: 13px; opacity: 0.9;">Для более удобного общения и работы с задачами</p>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <button id="install-btn" style="background: var(--accent-color); border: none; color: white; border-radius: 12px; padding: 8px 12px; font-weight: 600; font-size: 13px;">Установить</button>
                <button id="close-prompt" style="background: none; border: none; color: #8e8e93; font-size: 12px;">Позже</button>
            </div>
        </div>
    `;
    document.body.appendChild(promptEl);

    document.getElementById('install-btn').addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User response to the install prompt: ${outcome}`);
            deferredPrompt = null;
        }
        promptEl.remove();
    });

    document.getElementById('close-prompt').addEventListener('click', () => {
        promptEl.remove();
    });
}

function triggerInstall() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
    } else {
        alert('Пожалуйста, воспользуйтесь стандартным меню браузера ("Установить" или "Добавить на экран «Домой»").');
    }
}

// Advanced Mobile Detection
const getMobileOS = () => {
    const ua = navigator.userAgent;
    if (/android/i.test(ua)) return "Android";
    if (/iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) return "iOS";
    return "Other";
};

// Detects if device is in standalone mode
const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone) || window.matchMedia('(display-mode: standalone)').matches;

window.addEventListener('load', () => {
    const os = getMobileOS();
    const isStandalone = isInStandaloneMode();

    if (!isStandalone) {
        if (os === "iOS") {
            showIosPrompt();
        } else if (os === "Android") {
            // Android prompt is handled by beforeinstallprompt, but we can force it if needed
            if (!deferredPrompt) {
                // Wait a bit more for the event
                setTimeout(() => { if(!deferredPrompt) showGenericInstallGuide(); }, 3000);
            }
        }
    }
});

function showIosPrompt() {
    const iosPrompt = document.createElement('div');
    iosPrompt.className = 'install-prompt-ios';
    iosPrompt.style.animation = 'slideUp 0.5s forwards';
    iosPrompt.innerHTML = `
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="assets/img/icon-192.png" width="50" style="border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            <div style="flex: 1;">
                <p style="margin: 0; font-weight: bold; font-size: 16px;">Установите Жанну v3</p>
                <p style="margin: 3px 0 0; font-size: 13px; opacity: 0.9;">Нажмите <img src="assets/img/ios-share.png" height="18" style="vertical-align: middle;"> и <strong>"На экран «Домой»"</strong></p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #7360f2; font-weight: bold; font-size: 14px;">ОК</button>
        </div>
    `;
    document.body.appendChild(iosPrompt);
}

function showGenericInstallGuide() {
    // For cases where beforeinstallprompt didn't fire yet or is not supported
    showInstallPromotion();
}
