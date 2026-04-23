const { createApp } = Vue;

createApp({
    data() {
        return {
            isLoggedIn: false,
            loginPassword: '',
            loginError: '',
            currentTab: 'registrations',
            registrations: [],
            settings: {
                form_fields: [],
                social: {},
                ui: {}
            }
        }
    },
    async mounted() {
        // Try to load data to see if already logged in
        await this.loadData();
    },
    methods: {
        async login() {
            try {
                const res = await fetch('api/admin.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: this.loginPassword })
                });
                const data = await res.json();
                if (data.success) {
                    this.isLoggedIn = true;
                    await this.loadData();
                } else {
                    this.loginError = data.message;
                }
            } catch (e) {
                this.loginError = 'Ошибка сервера';
            }
        },
        async loadData() {
            try {
                const res = await fetch('api/admin.php?action=get_data');
                if (res.status === 401) {
                    this.isLoggedIn = false;
                    return;
                }
                const data = await res.json();
                this.registrations = data.registrations;
                this.settings = data.settings;
                this.isLoggedIn = true;
            } catch (e) {
                console.error('Failed to load data', e);
            }
        },
        async saveSettings() {
            try {
                const res = await fetch('api/admin.php?action=update_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.settings)
                });
                const data = await res.json();
                if (data.success) {
                    alert('Настройки сохранены');
                }
            } catch (e) {
                alert('Ошибка сохранения');
            }
        },
        async deleteRegistration(id) {
            if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;
            try {
                const res = await fetch(`api/admin.php?action=delete_registration&id=${id}`);
                const data = await res.json();
                if (data.success) {
                    this.registrations = this.registrations.filter(r => r.id !== id);
                }
            } catch (e) {
                alert('Ошибка удаления');
            }
        },
        addField() {
            this.settings.form_fields.push({
                id: 'field_' + Date.now(),
                label: 'Новое поле',
                type: 'text',
                required: false
            });
        },
        removeField(index) {
            this.settings.form_fields.splice(index, 1);
        },
        exportCSV() {
            if (this.registrations.length === 0) return;

            // BOM for Excel Cyrillic support
            let csv = '\uFEFF';

            // Header
            const headers = ['Дата', ...this.settings.form_fields.map(f => f.label)];
            csv += headers.join(';') + '\n';

            // Rows
            this.registrations.forEach(reg => {
                const row = [reg.created_at];
                this.settings.form_fields.forEach(f => {
                    row.push(reg[f.id] || '');
                });
                csv += row.map(v => `"${v}"`).join(';') + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'registrations.csv';
            link.click();
        },
        logout() {
            // In a real app we'd clear session on server too
            this.isLoggedIn = false;
            location.reload();
        }
    }
}).mount('#app');
