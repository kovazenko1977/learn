(function() {
    function initNewsShortcodes() {
        const containers = document.querySelectorAll('[data-news-section]');

        let baseUrl = '';
        const script = document.querySelector('script[src*="shortcode.js"]');
        if (script) {
            const src = script.getAttribute('src');
            if (src.indexOf('http') === 0) {
                baseUrl = new URL(src).origin + new URL(src).pathname.replace('assets/js/shortcode.js', '');
            } else {
                baseUrl = src.replace('assets/js/shortcode.js', '');
            }
        }

        containers.forEach(container => {
            if (container.dataset.initialized_v3) return;
            container.dataset.initialized_v3 = 'true';

            const sectionId = container.getAttribute('data-news-section');
            const apiUrl = container.getAttribute('data-api-url') || (baseUrl + 'api/shortcode.php');

            let currentParams = {};

            function loadData(params = {}) {
                currentParams = {...currentParams, ...params};
                const url = new URL(apiUrl, window.location.origin.includes('localhost') || window.location.origin.includes('0.0.0.0') ? window.location.origin : (baseUrl.startsWith('http') ? baseUrl : window.location.origin));
                const finalUrl = baseUrl.startsWith('http') ? new URL('api/shortcode.php', baseUrl) : url;

                finalUrl.searchParams.set('id', sectionId);
                Object.keys(currentParams).forEach(key => {
                    if (currentParams[key] !== undefined) finalUrl.searchParams.set(key, currentParams[key]);
                });

                const body = new FormData();
                if (currentParams.section_pass) body.append('section_pass', currentParams.section_pass);
                if (currentParams.action === 'reaction') body.append('news_id', currentParams.news_id);

                const fetchOptions = {
                    method: currentParams.action === 'reaction' || currentParams.section_pass ? 'POST' : 'GET'
                };
                if (fetchOptions.method === 'POST') fetchOptions.body = body;

                fetch(finalUrl, fetchOptions)
                    .then(response => response.text())
                    .then(html => {
                        if (currentParams.action === 'reaction') {
                            const data = JSON.parse(html);
                            if (data.success) {
                                const btn = container.querySelector(`.news-reaction-btn[data-id="${currentParams.news_id}"]`);
                                if (btn) {
                                    btn.classList.toggle('active', data.active);
                                    btn.querySelector('i').className = data.active ? 'bi bi-heart-fill' : 'bi bi-heart';
                                    btn.querySelector('.reaction-count').textContent = data.count;
                                }
                            }
                            currentParams.action = undefined;
                        } else {
                            container.innerHTML = html;
                            attachEvents();
                        }
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
                        currentParams.news_id = undefined;
                        loadData({ news_id: undefined });
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
                        loadData({ action: 'reaction', news_id: btn.dataset.id });
                    };
                });

                // Password
                const passBtn = container.querySelector('.news-pass-btn');
                if (passBtn) {
                    passBtn.onclick = () => {
                        const val = container.querySelector('.news-pass-input').value;
                        loadData({ section_pass: val });
                    };
                }

                // Accessibility
                container.querySelectorAll('.acc-btn').forEach(btn => {
                    btn.onclick = () => {
                        const action = btn.dataset.action;
                        const wrap = container.querySelector('.news-section-wrapper');
                        let currentSize = parseInt(window.getComputedStyle(wrap).fontSize);
                        if (action === 'font-inc') wrap.style.fontSize = (currentSize + 2) + 'px';
                        if (action === 'font-dec') wrap.style.fontSize = (currentSize - 2) + 'px';
                        if (action === 'theme-toggle') wrap.classList.toggle('news-dark-theme');
                    };
                });

                // Table of Contents
                const toc = container.querySelector('.news-toc');
                const content = container.querySelector('.news-content');
                if (toc && content) {
                    const headers = content.querySelectorAll('h1, h2, h3');
                    const list = toc.querySelector('.toc-list');
                    headers.forEach((h, i) => {
                        const id = 'h-' + i;
                        h.id = id;
                        const li = document.createElement('li');
                        li.style.paddingLeft = (parseInt(h.tagName[1]) - 1) * 15 + 'px';
                        const a = document.createElement('a');
                        a.href = '#' + id;
                        a.textContent = h.textContent;
                        a.onclick = (e) => {
                            e.preventDefault();
                            h.scrollIntoView({ behavior: 'smooth' });
                        };
                        li.appendChild(a);
                        list.appendChild(li);
                    });
                    if (headers.length === 0) toc.style.display = 'none';
                }

                // Reading Progress
                const progress = container.querySelector('.news-progress-bar');
                if (progress) {
                    window.onscroll = () => {
                        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                        const scrolled = (winScroll / height) * 100;
                        progress.style.width = scrolled + "%";
                    };
                }

                // Scroll to top
                const scrollTop = container.querySelector('.news-scroll-top');
                if (scrollTop) {
                    window.addEventListener('scroll', () => {
                        scrollTop.style.display = window.scrollY > 300 ? 'flex' : 'none';
                    });
                    scrollTop.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                // Copy link
                container.querySelectorAll('.news-copy-link-btn').forEach(btn => {
                    btn.onclick = () => {
                        navigator.clipboard.writeText(btn.dataset.url).then(() => alert('Ссылка скопирована!'));
                    };
                });

                // External Link Tracking
                container.querySelectorAll('.news-ext-link').forEach(link => {
                    link.onclick = (e) => {
                        const url = link.dataset.url;
                        const newsId = link.dataset.news;
                        console.log(`Link clicked: ${url} in news ${newsId}`);
                        // Optionally call an API to track clicks
                    };
                });

                // QR Code (Placeholder for real implementation, using a public API)
                container.querySelectorAll('.news-qr-btn').forEach(btn => {
                    btn.onclick = () => {
                        const url = btn.dataset.url;
                        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;
                        const win = window.open("", "QR Code", "width=250,height=250");
                        win.document.write(`<img src="${qrUrl}" style="margin:20px auto; display:block;">`);
                    };
                });

                // Subscribe form
                const subForm = container.querySelector('.news-subscribe-form');
                if (subForm) {
                    subForm.onsubmit = (e) => {
                        e.preventDefault();
                        const email = subForm.querySelector('input').value;
                        const msg = container.querySelector('.news-subscribe-msg');
                        msg.textContent = 'Отправка...';

                        const subBody = new FormData();
                        subBody.append('email', email);
                        subBody.append('section_id', sectionId);

                        fetch(baseUrl + 'api/subscribe.php', {
                            method: 'POST',
                            body: subBody
                        })
                        .then(r => r.json())
                        .then(data => {
                            msg.textContent = data.message;
                            if (data.success) subForm.style.display = 'none';
                        });
                    };
                }
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
