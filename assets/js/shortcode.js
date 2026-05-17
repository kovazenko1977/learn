(function() {
    function initNewsShortcodes() {
        const containers = document.querySelectorAll('[data-news-section]');

        let baseUrl = '';
        const script = document.querySelector('script[src*="shortcode.js"]');
        if (script) {
            const src = script.getAttribute('src');
            // Support both relative and absolute paths
            if (src.indexOf('http') === 0) {
                baseUrl = new URL(src).origin + new URL(src).pathname.replace('assets/js/shortcode.js', '');
            } else {
                baseUrl = src.replace('assets/js/shortcode.js', '');
            }
        }

        containers.forEach(container => {
            if (container.dataset.initialized) return;
            container.dataset.initialized = 'true';

            const sectionId = container.getAttribute('data-news-section');
            const apiUrl = container.getAttribute('data-api-url') || (baseUrl + 'api/shortcode.php');

            function loadData(params = {}) {
                const url = new URL(apiUrl, window.location.origin.includes('localhost') || window.location.origin.includes('0.0.0.0') ? window.location.origin : (baseUrl.startsWith('http') ? baseUrl : window.location.origin));

                // If baseUrl is absolute, use it
                const finalUrl = baseUrl.startsWith('http') ? new URL('api/shortcode.php', baseUrl) : url;

                finalUrl.searchParams.set('id', sectionId);
                Object.keys(params).forEach(key => finalUrl.searchParams.set(key, params[key]));

                fetch(finalUrl)
                    .then(response => response.text())
                    .then(html => {
                        container.innerHTML = html;
                        attachEvents();
                    })
                    .catch(error => {
                        console.error('Error loading news:', error);
                        container.innerHTML = '<p style="color:red">Ошибка загрузки контента.</p>';
                    });
            }

            function attachEvents() {
                // Pagination
                container.querySelectorAll('.news-page-link').forEach(link => {
                    link.onclick = (e) => {
                        e.preventDefault();
                        loadData({ page: link.dataset.page });
                        container.scrollIntoView({ behavior: 'smooth' });
                    };
                });

                // Single View
                container.querySelectorAll('[data-news-id]').forEach(el => {
                    el.onclick = (e) => {
                        if (e.target.closest('a') && !e.target.closest('.news-more-btn')) return;
                        e.preventDefault();
                        loadData({ news_id: el.dataset.newsId });
                        container.scrollIntoView({ behavior: 'smooth' });
                    };
                });

                // Back to list
                container.querySelectorAll('.news-back-link').forEach(link => {
                    link.onclick = (e) => {
                        e.preventDefault();
                        loadData();
                    };
                });

                // Search
                const searchInput = container.querySelector('.news-search-input');
                if (searchInput) {
                    let debounceTimer;
                    searchInput.oninput = () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            loadData({ q: searchInput.value });
                        }, 500);
                    };
                }

                // Tags
                container.querySelectorAll('.news-tag').forEach(tag => {
                    tag.onclick = (e) => {
                        e.preventDefault();
                        loadData({ tag: tag.dataset.tag });
                    };
                });

                // Reactions
                container.querySelectorAll('.news-reaction-btn').forEach(btn => {
                    btn.onclick = (e) => {
                        e.preventDefault();
                        const formData = new FormData();
                        formData.append('news_id', btn.dataset.id);

                        const reactionUrl = new URL(apiUrl, baseUrl.startsWith('http') ? baseUrl : window.location.origin);
                        if (baseUrl.startsWith('http')) {
                             reactionUrl.pathname = (new URL(baseUrl).pathname + 'api/shortcode.php').replace('//', '/');
                        }

                        fetch(reactionUrl.href + '?action=reaction&id=' + sectionId, {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                btn.classList.toggle('active', data.active);
                                const icon = btn.querySelector('i');
                                icon.className = data.active ? 'bi bi-heart-fill' : 'bi bi-heart';
                                btn.querySelector('.reaction-count').textContent = data.count;
                            }
                        });
                    };
                });
            }

            loadData();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNewsShortcodes);
    } else {
        initNewsShortcodes();
    }

    const observer = new MutationObserver(initNewsShortcodes);
    observer.observe(document.body, { childList: true, subtree: true });
})();
