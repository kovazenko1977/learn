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

// iOS specific detection
const isIos = () => {
  const userAgent = window.navigator.userAgent.toLowerCase();
  return /iphone|ipad|ipod/.test( userAgent );
}
// Detects if device is in standalone mode
const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

if (isIos() && !isInStandaloneMode()) {
    setTimeout(() => {
        const iosPrompt = document.createElement('div');
        iosPrompt.className = 'install-prompt-ios';
        iosPrompt.innerHTML = `
            <p>Для установки: нажмите <img src="assets/img/ios-share.png" height="20"> и <strong>"На экран «Домой»"</strong></p>
            <button onclick="this.parentElement.remove()">Понятно</button>
        `;
        document.body.appendChild(iosPrompt);
    }, 3000);
}
