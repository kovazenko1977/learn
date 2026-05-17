(function() {
    function initNewsShortcodes() {
        const containers = document.querySelectorAll('[data-news-section]');

        // Find the base URL of the current script to determine where the API is
        let baseUrl = '';
        const script = document.querySelector('script[src*="shortcode.js"]');
        if (script) {
            const src = script.getAttribute('src');
            baseUrl = src.replace('assets/js/shortcode.js', '');
        }

        containers.forEach(container => {
            if (container.dataset.loaded) return;

            const sectionId = container.getAttribute('data-news-section');
            const apiUrl = container.getAttribute('data-api-url') || (baseUrl + 'api/shortcode.php');

            function loadPage(page) {
                const url = new URL(apiUrl, window.location.origin);
                url.searchParams.set('id', sectionId);
                if (page) url.searchParams.set('page', page);

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        container.innerHTML = html;
                        container.dataset.loaded = 'true';

                        // Handle pagination clicks
                        container.querySelectorAll('.news-page-link').forEach(link => {
                            link.onclick = (e) => {
                                e.preventDefault();
                                loadPage(link.dataset.page);
                                container.scrollIntoView({ behavior: 'smooth' });
                            };
                        });
                    })
                    .catch(error => {
                        console.error('Error loading news section:', error);
                        container.innerHTML = '<p style="color:red">Ошибка загрузки контента. Проверьте путь к API.</p>';
                    });
            }

            loadPage();
        });
    }

    // Run on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNewsShortcodes);
    } else {
        initNewsShortcodes();
    }

    // Re-run if content changes (for dynamic sites)
    const observer = new MutationObserver(initNewsShortcodes);
    observer.observe(document.body, { childList: true, subtree: true });
})();
