const { createApp } = Vue;

createApp({
    data() {
        return {
            authenticated: false,
            pin: '',
            loading: false,
            error: '',
            files: [],
            stats: { totalFiles: 0, totalSize: 0 },
            selectedFile: null,
            uploadDescription: '',
            searchQuery: '',
            sortBy: 'date_desc',
            editingId: null,
            editDescription: '',
            toast: { show: false, message: '', type: '' }
        }
    },
    computed: {
        filteredFiles() {
            let result = [...this.files];

            // Search
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(f =>
                    f.name.toLowerCase().includes(query) ||
                    (f.description && f.description.toLowerCase().includes(query))
                );
            }

            // Sort
            result.sort((a, b) => {
                switch(this.sortBy) {
                    case 'date_desc': return new Date(b.uploadDate) - new Date(a.uploadDate);
                    case 'date_asc': return new Date(a.uploadDate) - new Date(b.uploadDate);
                    case 'name_asc': return a.name.localeCompare(b.name);
                    case 'size_desc': return b.size - a.size;
                    default: return 0;
                }
            });

            return result;
        }
    },
    methods: {
        async apiFetch(url, options = {}) {
            try {
                const response = await fetch(url, options);
                const data = await response.json();
                if (data.error === 'Unauthorized') {
                    this.authenticated = false;
                    return null;
                }
                return data;
            } catch (err) {
                this.showToast('Ошибка сети', 'error');
                return null;
            }
        },
        async checkAuth() {
            const data = await this.apiFetch('api/auth.php?action=check');
            if (data && data.authenticated) {
                this.authenticated = true;
                this.loadDashboard();
            }
        },
        async login() {
            if (this.pin.length !== 6) {
                this.error = 'ПИН-код должен состоять из 6 цифр';
                return;
            }
            this.loading = true;
            this.error = '';
            const data = await this.apiFetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pin: this.pin })
            });
            this.loading = false;
            if (data && data.success) {
                this.authenticated = true;
                this.loadDashboard();
            } else {
                this.error = data ? data.error : 'Ошибка входа';
            }
        },
        async logout() {
            await this.apiFetch('api/auth.php?action=logout');
            this.authenticated = false;
            this.pin = '';
        },
        async loadDashboard() {
            this.loadFiles();
            this.loadStats();
        },
        async loadFiles() {
            const data = await this.apiFetch('api/files.php?action=list');
            if (data) this.files = data;
        },
        async loadStats() {
            const data = await this.apiFetch('api/files.php?action=stats');
            if (data) this.stats = data;
        },
        handleFileChange(e) {
            this.selectedFile = e.target.files[0];
        },
        async uploadFile() {
            if (!this.selectedFile) return;
            this.loading = true;
            const formData = new FormData();
            formData.append('file', this.selectedFile);
            formData.append('description', this.uploadDescription);

            const data = await this.apiFetch('api/upload.php', {
                method: 'POST',
                body: formData
            });

            this.loading = false;
            if (data && data.success) {
                this.showToast('Файл успешно загружен', 'success');
                this.selectedFile = null;
                this.uploadDescription = '';
                this.$refs.fileInput.value = '';
                this.loadDashboard();
            } else {
                this.showToast(data ? data.error : 'Ошибка загрузки', 'error');
            }
        },
        async deleteFile(id) {
            if (!confirm('Вы уверены, что хотите удалить этот файл?')) return;
            const data = await this.apiFetch(`api/files.php?action=delete&id=${id}`);
            if (data && data.success) {
                this.showToast('Файл удален', 'success');
                this.loadDashboard();
            }
        },
        startEditing(file) {
            this.editingId = file.id;
            this.editDescription = file.description || '';
            this.$nextTick(() => {
                if (this.$refs.editInput && this.$refs.editInput[0]) {
                    this.$refs.editInput[0].focus();
                }
            });
        },
        async saveDescription(id) {
            const data = await this.apiFetch('api/files.php?action=update_description', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, description: this.editDescription })
            });
            if (data && data.success) {
                this.editingId = null;
                this.loadFiles();
            }
        },
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        isImage(type) {
            return type.startsWith('image/');
        },
        getFileEmoji(type) {
            if (type.includes('pdf')) return '📄';
            if (type.includes('zip') || type.includes('rar')) return '📦';
            if (type.includes('word') || type.includes('officedocument')) return '📝';
            if (type.includes('excel') || type.includes('sheet')) return '📊';
            return '📁';
        },
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Путь скопирован в буфер обмена', 'success');
            });
        },
        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => {
                this.toast.show = false;
            }, 3000);
        }
    },
    mounted() {
        this.checkAuth();
    }
}).mount('#app');
