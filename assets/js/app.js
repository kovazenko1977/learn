const { createApp } = Vue;

createApp({
    data() {
        return {
            authenticated: false,
            loginCode: '',
            loading: false,
            loginError: '',
            news: [],
            searchQuery: '',
            statusFilter: 'all',
            showEditor: false,
            showEmbedModal: false,
            showSettingsModal: false,
            accessCodeInput: '',
            editingItem: {},
            quill: null,
            toast: null,
            baseUrl: window.location.origin + window.location.pathname.replace('index.php', '')
        }
    },
    computed: {
        filteredNews() {
            return this.news.filter(item => {
                const matchesSearch = item.title.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesStatus = this.statusFilter === 'all' || item.status === this.statusFilter;
                return matchesSearch && matchesStatus;
            });
        },
        embedCode() {
            return `<div id="news-feed"></div>\n<script src="${this.baseUrl}api/public.php?js"></script>`;
        }
    },
    methods: {
        async checkAuth() {
            const res = await fetch('api/auth.php?action=check');
            const data = await res.json();
            this.authenticated = data.authenticated;
            if (this.authenticated) this.fetchNews();
        },
        async login() {
            if (this.loginCode.length !== 6) {
                this.loginError = 'Введите 6 цифр';
                return;
            }
            this.loading = true;
            this.loginError = '';
            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: this.loginCode })
                });
                if (res.ok) {
                    this.authenticated = true;
                    this.fetchNews();
                } else {
                    const data = await res.json();
                    this.loginError = data.error || 'Ошибка входа';
                }
            } catch (e) {
                this.loginError = 'Ошибка сервера';
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            await fetch('api/auth.php?action=logout');
            this.authenticated = false;
            this.news = [];
        },
        async fetchNews() {
            const res = await fetch('api/news.php');
            if (res.ok) {
                this.news = await res.json();
            } else if (res.status === 401) {
                this.authenticated = false;
            }
        },
        initQuill() {
            if (this.quill) return;
            this.quill = new Quill('#editor-container', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        ['link', 'image'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });
        },
        openEditor(item = null) {
            if (item) {
                this.editingItem = { ...item };
            } else {
                this.editingItem = {
                    title: '',
                    content: '',
                    image: '',
                    date: new Date().toISOString().slice(0, 16),
                    status: 'published'
                };
            }
            this.showEditor = true;
            this.$nextTick(() => {
                this.initQuill();
                this.quill.root.innerHTML = this.editingItem.content || '';
            });
        },
        closeEditor() {
            this.showEditor = false;
            if (this.quill) {
                this.quill.root.innerHTML = '';
            }
        },
        async saveNews() {
            if (!this.editingItem.title) {
                this.showToast('Введите заголовок', 'error');
                return;
            }

            this.editingItem.content = this.quill.root.innerHTML;

            this.loading = true;
            const method = this.editingItem.id ? 'PUT' : 'POST';
            try {
                const res = await fetch('api/news.php', {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.editingItem)
                });
                if (res.ok) {
                    this.showToast('Сохранено успешно', 'success');
                    this.closeEditor();
                    this.fetchNews();
                } else {
                    this.showToast('Ошибка при сохранении', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка сети', 'error');
            } finally {
                this.loading = false;
            }
        },
        async deleteNews(id) {
            if (!confirm('Вы уверены, что хотите удалить эту новость?')) return;
            try {
                const res = await fetch(`api/news.php?id=${id}`, { method: 'DELETE' });
                if (res.ok) {
                    this.showToast('Удалено', 'success');
                    this.fetchNews();
                }
            } catch (e) {
                this.showToast('Ошибка при удалении', 'error');
            }
        },
        async uploadImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            this.loading = true;
            try {
                const res = await fetch('api/upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (res.ok) {
                    this.editingItem.image = data.url;
                } else {
                    this.showToast(data.error || 'Ошибка загрузки', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка сети', 'error');
            } finally {
                this.loading = false;
            }
        },
        async updateSettings() {
            if (this.accessCodeInput.length !== 6) {
                this.showToast('Код должен быть из 6 цифр', 'error');
                return;
            }
            this.loading = true;
            try {
                const res = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ access_code: this.accessCodeInput })
                });
                if (res.ok) {
                    this.showToast('Код обновлен', 'success');
                    this.accessCodeInput = '';
                    this.showSettingsModal = false;
                } else {
                    this.showToast('Ошибка обновления', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка сети', 'error');
            } finally {
                this.loading = false;
            }
        },
        async deleteAllNews() {
            if (!confirm('Вы уверены, что хотите УДАЛИТЬ ВСЕ НОВОСТИ? Это действие необратимо.')) return;
            this.loading = true;
            try {
                const res = await fetch('api/service.php?action=delete_all', { method: 'POST' });
                if (res.ok) {
                    this.showToast('Все новости удалены', 'success');
                    this.fetchNews();
                    this.showSettingsModal = false;
                }
            } finally {
                this.loading = false;
            }
        },
        async cleanupFiles() {
            this.loading = true;
            try {
                const res = await fetch('api/service.php?action=cleanup', { method: 'POST' });
                const data = await res.json();
                if (res.ok) {
                    this.showToast(`Очищено файлов: ${data.deleted_count}`, 'success');
                }
            } finally {
                this.loading = false;
            }
        },
        formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('ru-RU') + ' ' + d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        },
        truncate(text, length) {
            // Remove HTML tags for preview
            const plainText = text.replace(/<[^>]*>/g, '');
            if (plainText.length <= length) return plainText;
            return plainText.substring(0, length) + '...';
        },
        isFuture(dateStr) {
            return new Date(dateStr) > new Date();
        },
        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => { this.toast = null; }, 3000);
        },
        copyEmbedCode() {
            navigator.clipboard.writeText(this.embedCode);
            this.showToast('Код скопирован!', 'success');
        }
    },
    mounted() {
        this.checkAuth();
    }
}).mount('#app');
