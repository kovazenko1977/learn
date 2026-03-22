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

function showInstallPromotion() {
    const promptEl = document.createElement('div');
    promptEl.className = 'install-prompt';
    promptEl.innerHTML = `
        <div class="install-prompt-content">
            <p>Установите приложение для быстрого доступа</p>
            <button id="install-btn">Установить</button>
            <button id="close-prompt">Закрыть</button>
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

// iOS specific detection
const isIos = () => {
  const userAgent = window.navigator.userAgent.toLowerCase();
  return /iphone|ipad|ipod/.test( userAgent );
}
// Detects if device is in standalone mode
const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

if (isIos() && !isInStandaloneMode()) {
    // Show iOS prompt immediately on load
    window.addEventListener('DOMContentLoaded', () => {
        const iosPrompt = document.createElement('div');
        iosPrompt.className = 'install-prompt-ios';
        iosPrompt.style.animation = 'slideUp 0.5s forwards';
        iosPrompt.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="assets/img/icon-192.png" width="50" style="border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                <div style="flex: 1;">
                    <p style="margin: 0; font-weight: bold; font-size: 16px;">Установите Жанну</p>
                    <p style="margin: 3px 0 0; font-size: 13px; opacity: 0.9;">Нажмите <img src="assets/img/ios-share.png" height="18" style="vertical-align: middle;"> и <strong>"На экран «Домой»"</strong></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #007aff; font-weight: bold; font-size: 14px;">ОК</button>
            </div>
        `;
        document.body.appendChild(iosPrompt);
    });
}
