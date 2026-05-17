document.addEventListener('DOMContentLoaded', () => {
    console.log('Popup Manager: Script loaded');

    // Create overlay if it doesn't exist
    let overlay = document.querySelector('.popup-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'popup-overlay';
        overlay.innerHTML = `
            <div class="popup-content">
                <span class="popup-close">&times;</span>
                <div class="popup-body"></div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('.popup-close').addEventListener('click', () => {
            overlay.style.display = 'none';
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    }

    const popupBody = overlay.querySelector('.popup-body');
    const popupContent = overlay.querySelector('.popup-content');

    // Detect API path relative to the script location
    // This helps if the app is hosted in a subdirectory
    const scriptTag = document.currentScript;
    let apiBase = 'api/get_popup.php';

    if (scriptTag && scriptTag.src) {
        const url = new URL(scriptTag.src);
        const pathParts = url.pathname.split('/');
        pathParts.pop(); // remove popup.js
        pathParts.pop(); // remove js
        const baseDir = pathParts.join('/');
        apiBase = (baseDir ? baseDir + '/' : '') + 'api/get_popup.php';
    }

    console.log('Popup Manager: API Path -', apiBase);

    // Attach click events to buttons
    document.addEventListener('click', async (e) => {
        const button = e.target.closest('[data-popup-code]');
        if (!button) return;

        e.preventDefault();
        const code = button.getAttribute('data-popup-code');
        console.log('Popup Manager: Triggered code -', code);

        try {
            const response = await fetch(`${apiBase}?code=${code}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();
            console.log('Popup Manager: Received data -', data);

            if (data.error) {
                console.error('Popup Manager Error:', data.error);
                return;
            }

            // Fill content
            popupBody.innerHTML = ''; // Clear previous
            if (data.image) {
                const img = document.createElement('img');
                img.src = data.image;
                img.alt = data.title;
                popupBody.appendChild(img);
            }
            const h3 = document.createElement('h3');
            h3.textContent = data.title;
            popupBody.appendChild(h3);

            const p = document.createElement('p');
            p.style.whiteSpace = 'pre-wrap';
            p.textContent = data.text;
            popupBody.appendChild(p);

            // Set animation
            // Remove previous classes to restart animation
            popupContent.className = 'popup-content';
            void popupContent.offsetWidth; // Trigger reflow
            popupContent.classList.add('anim-' + data.animation);

            // Show popup
            overlay.style.display = 'flex';

        } catch (err) {
            console.error('Popup Manager: Fetch error:', err);
        }
    });
});
