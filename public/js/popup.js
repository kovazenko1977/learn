document.addEventListener('DOMContentLoaded', () => {
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

    // Attach click events to buttons
    document.querySelectorAll('[data-popup-code]').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const code = button.getAttribute('data-popup-code');

            try {
                const response = await fetch(`/api/get_popup.php?code=${code}`);
                const data = await response.json();

                if (data.error) {
                    console.error(data.error);
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
                popupContent.className = 'popup-content anim-' + data.animation;

                // Show popup
                overlay.style.display = 'flex';

            } catch (err) {
                console.error('Error fetching popup:', err);
            }
        });
    });
});
