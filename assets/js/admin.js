const { createApp } = Vue;

createApp({
    data() {
        return {
            isLoggedIn: false,
            loginPassword: '',
            loginError: '',
            currentTab: 'registrations',
            searchQuery: '',
            filterStatus: 'all',
            sortBy: 'created_at',
            sortDesc: true,
            selectedIds: [],
            bulkDiscount: 10,
            csrfToken: '',
            passChange: { old: '', new: '' },
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
    computed: {
        filteredRegistrations() {
            let filtered = [...this.registrations];

            // Filter by status
            if (this.filterStatus !== 'all') {
                filtered = filtered.filter(r => r.status === this.filterStatus);
            }

            // Filter by search query
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(r => {
                    return Object.values(r).some(val =>
                        String(val).toLowerCase().includes(query)
                    );
                });
            }

            // Sorting
            filtered.sort((a, b) => {
                let valA = a[this.sortBy];
                let valB = b[this.sortBy];

                if (this.sortBy === 'discount' || this.sortBy === 'new_discount') {
                    valA = parseInt(valA) || 0;
                    valB = parseInt(valB) || 0;
                }

                if (valA < valB) return this.sortDesc ? 1 : -1;
                if (valA > valB) return this.sortDesc ? -1 : 1;
                return 0;
            });

            return filtered;
        },
        stats() {
            const total = this.registrations.length;
            const pending = this.registrations.filter(r => r.status === 'pending').length;
            const approved = this.registrations.filter(r => r.status === 'approved');
            const approvedCount = approved.length;

            const totalDiscount = approved.reduce((sum, r) => sum + (parseInt(r.discount) || 0), 0);
            const avgDiscount = approvedCount > 0 ? Math.round(totalDiscount / approvedCount) : 0;

            return {
                total,
                pending,
                approved: approvedCount,
                avgDiscount
            };
        },
        isAllSelected() {
            return this.filteredRegistrations.length > 0 && this.selectedIds.length === this.filteredRegistrations.length;
        }
    },
    methods: {
        setSort(field) {
            if (this.sortBy === field) {
                this.sortDesc = !this.sortDesc;
            } else {
                this.sortBy = field;
                this.sortDesc = false;
            }
        },
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
                    this.csrfToken = data.csrf_token;
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
                this.registrations = data.registrations.map(r => ({...r, new_discount: 10}));
                this.settings = data.settings;
                this.csrfToken = data.csrf_token;
                this.isLoggedIn = true;
            } catch (e) {
                console.error('Failed to load data', e);
            }
        },
        async changePassword() {
            if (!this.passChange.old || !this.passChange.new) return alert('Заполните все поля');
            try {
                const res = await fetch('api/admin.php?action=change_password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        old_password: this.passChange.old,
                        new_password: this.passChange.new
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Пароль успешно изменен');
                    this.passChange = { old: '', new: '' };
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Ошибка сервера');
            }
        },
        async saveSettings() {
            try {
                const res = await fetch('api/admin.php?action=update_settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
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
        async approveRegistration(reg) {
            try {
                const res = await fetch(`api/admin.php?action=approve_registration&id=${reg.id}&discount=${reg.new_discount}&csrf_token=${this.csrfToken}`);
                const data = await res.json();
                if (data.success) {
                    reg.status = 'approved';
                    reg.discount = reg.new_discount;
                }
            } catch (e) {
                alert('Ошибка одобрения');
            }
        },
        async updateDiscount(reg) {
            try {
                const res = await fetch(`api/admin.php?action=update_discount&id=${reg.id}&discount=${reg.discount}&csrf_token=${this.csrfToken}`);
                const data = await res.json();
                if (!data.success) alert('Ошибка обновления');
            } catch (e) {
                alert('Ошибка обновления');
            }
        },
        async bulkAction(type) {
            if (type === 'delete' && !confirm(`Вы уверены, что хотите удалить ${this.selectedIds.length} записей?`)) return;

            try {
                const res = await fetch('api/admin.php?action=bulk_action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        type,
                        ids: this.selectedIds,
                        discount: this.bulkDiscount
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (type === 'delete') {
                        this.registrations = this.registrations.filter(r => !this.selectedIds.includes(r.id));
                    } else {
                        await this.loadData();
                    }
                    this.selectedIds = [];
                }
            } catch (e) {
                alert('Ошибка операции');
            }
        },
        toggleAll() {
            if (this.isAllSelected) {
                this.selectedIds = [];
            } else {
                this.selectedIds = this.filteredRegistrations.map(r => r.id);
            }
        },
        async deleteRegistration(id) {
            if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;
            try {
                const res = await fetch(`api/admin.php?action=delete_registration&id=${id}&csrf_token=${this.csrfToken}`);
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
        async logout() {
            try {
                await fetch('api/admin.php?action=logout');
                this.isLoggedIn = false;
                location.reload();
            } catch (e) {
                location.reload();
            }
        }
    }
}).mount('#app');
