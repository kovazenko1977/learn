(function() {
    const scriptSrc = document.currentScript.src;
    const baseUrl = scriptSrc.substring(0, scriptSrc.lastIndexOf('/js/'));

    async function showPopup(code) {
        try {
            const response = await fetch(`${baseUrl}/api/get_popup.php?code=${encodeURIComponent(code)}`);
            if (!response.ok) return;
            const popup = await response.json();
            if (!popup) return;

            createPopupElement(popup);
        } catch (e) {
            console.error('Popup error:', e);
        }
    }

    function createPopupElement(popup) {
        const overlay = document.createElement('div');
        overlay.className = 'wes-popup-overlay';

        const content = document.createElement('div');
        content.className = `wes-popup-content wes-popup-anim-${popup.animation || 'fade'}`;

        if (popup.image) {
            const img = document.createElement('img');
            img.src = popup.image;
            img.className = 'wes-popup-image';
            content.appendChild(img);
        }

        const closeBtn = document.createElement('button');
        closeBtn.className = 'wes-popup-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => overlay.remove();
        content.appendChild(closeBtn);

        const title = document.createElement('h3');
        title.textContent = popup.title;
        content.appendChild(title);

        const text = document.createElement('p');
        text.textContent = popup.content;
        content.appendChild(text);

        overlay.appendChild(content);
        overlay.onclick = (e) => {
            if (e.target === overlay) overlay.remove();
        };

        document.body.appendChild(overlay);
    }

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-popup-code]');
        if (trigger) {
            e.preventDefault();
            showPopup(trigger.dataset.popupCode);
        }
    });
})();
