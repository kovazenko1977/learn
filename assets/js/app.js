const { createApp } = Vue;

createApp({
    data() {
        return {
            isLoggedIn: false,
            user: null,
            view: 'appointments',
            mobileMenu: false,
            loginForm: { login: '', password: '' },
            error: '',
            patients: [],
            doctors: [],
            services: [],
            rooms: [],
            appointments: [],
            users: [],
            tasks: [],
            finance: [],
            tags: [],
            sources: [],
            documents: [],
            chat: [],
            newMessage: '',
            versions: [],
            settings: { clinic_name: 'Dental CRM' },
            modal: null,
            modalTitle: '',
            form: {},
            rawMenu: [
                { id: 'appointments', label: 'Расписание', roles: ['admin', 'senior_admin', 'manager', 'doctor', 'patient'] },
                { id: 'patients', label: 'Пациенты', roles: ['admin', 'senior_admin', 'manager', 'doctor'] },
                { id: 'doctors', label: 'Врачи', roles: ['admin', 'senior_admin'] },
                { id: 'services', label: 'Услуги', roles: ['admin', 'senior_admin'] },
                { id: 'rooms', label: 'Кабинеты', roles: ['admin', 'senior_admin'] },
                { id: 'tasks', label: 'Задачи', roles: ['admin', 'senior_admin', 'manager', 'doctor'] },
                { id: 'finance', label: 'Финансы', roles: ['admin', 'director'] },
                { id: 'analytics', label: 'Аналитика', roles: ['admin', 'director', 'marketing'] },
                { id: 'documents', label: 'Документы', roles: ['admin', 'doctor', 'patient'] },
                { id: 'chat', label: 'Чат', roles: ['admin', 'senior_admin', 'manager', 'doctor', 'patient'] },
                { id: 'tags', label: 'Теги', roles: ['admin', 'marketing'] },
                { id: 'sources', label: 'Источники', roles: ['admin', 'marketing'] },
                { id: 'users', label: 'Пользователи', roles: ['admin'] },
                { id: 'settings', label: 'Настройки', roles: ['admin'] }
            ]
        }
    },
    computed: {
        menu() {
            if (!this.user) return [];
            return this.rawMenu.filter(m => m.roles.includes(this.user.role));
        }
    },
    methods: {
        async api(module, params = {}, method = 'GET', data = null) {
            this.error = '';
            const query = new URLSearchParams({ module, ...params }).toString();
            const url = `api/index.php?${query}`;
            const options = { method, headers: { 'Content-Type': 'application/json' } };
            if (data) options.body = JSON.stringify(data);

            try {
                const response = await fetch(url, options);
                const result = await response.json();
                if (result.error) {
                    this.error = result.error;
                    return null;
                }
                return result;
            } catch (e) {
                this.error = 'Ошибка соединения с сервером';
                return null;
            }
        },
        async login() {
            const result = await this.api('auth', { action: 'login' }, 'POST', this.loginForm);
            if (result && result.success) {
                this.isLoggedIn = true;
                this.user = result.user;
                this.loadData();
            }
        },
        async logout() {
            await this.api('auth', { action: 'logout' });
            this.isLoggedIn = false;
            this.user = null;
        },
        async checkAuth() {
            const result = await this.api('auth', { action: 'check' });
            if (result && result.isLoggedIn) {
                this.isLoggedIn = true;
                this.user = result.user;
                this.loadData();
            }
        },
        async loadData() {
            const loaders = [
                this.api('patients').then(res => this.patients = res || []),
                this.api('doctors').then(res => this.doctors = res || []),
                this.api('services').then(res => this.services = res || []),
                this.api('rooms').then(res => this.rooms = res || []),
                this.api('appointments').then(res => this.appointments = res || []),
                this.api('tasks').then(res => this.tasks = res || []),
                this.api('finance').then(res => this.finance = res || []),
                this.api('tags').then(res => this.tags = res || []),
                this.api('sources').then(res => this.sources = res || []),
                this.api('documents').then(res => this.documents = res || []),
                this.api('chat', { dialog_id: 'general' }).then(res => this.chat = res || [])
            ];

            if (this.user.role === 'admin') {
                loaders.push(this.api('users').then(res => this.users = res || []));
                loaders.push(this.api('settings').then(res => this.settings = res || { clinic_name: 'Dental CRM' }));
            }

            await Promise.allSettled(loaders);
        },
        openModal(type, item = null) {
            this.modal = type;
            this.form = item ? JSON.parse(JSON.stringify(item)) : { status: 'planned' };
            this.modalTitle = item ? 'Редактировать' : 'Добавить';
        },
        async saveForm() {
            const result = await this.api(this.modal, {}, 'POST', this.form);
            if (result && (result.success || result.id)) {
                this.modal = null;
                this.loadData();
            }
        },
        async saveItem(module, item) {
            await this.api(module, {}, 'POST', item);
            this.loadData();
        },
        async deleteItem(module, id) {
            if (confirm('Вы уверены?')) {
                await this.api(module, { id }, 'DELETE');
                this.loadData();
            }
        },
        async saveSettings() {
            await this.api('settings', {}, 'POST', this.settings);
            alert('Настройки сохранены');
        },
        async sendMessage() {
            if (!this.newMessage.trim()) return;
            await this.api('chat', {}, 'POST', { text: this.newMessage, dialog_id: 'general' });
            this.newMessage = '';
            const result = await this.api('chat', { dialog_id: 'general' });
            this.chat = result || [];
        },
        editItem(type, item) {
            this.openModal(type, item);
        },
        getPatientName(id) {
            const p = this.patients.find(x => x.id === id);
            return p ? p.full_name : 'Неизвестен';
        },
        getDoctorName(id) {
            const d = this.doctors.find(x => x.id === id);
            return d ? d.full_name : 'Неизвестен';
        },
        getServiceName(id) {
            const s = this.services.find(x => x.id === id);
            return s ? s.name : '';
        },
        async showHistory(module, id) {
            this.modal = 'versions';
            this.modalTitle = 'История изменений';
            const res = await this.api('versions', { entity: module, id });
            this.versions = res || [];
        },
        async restoreVersion(v) {
            if (confirm('Восстановить эту версию?')) {
                await this.api(v.entity, {}, 'POST', v.data);
                this.modal = null;
                this.loadData();
            }
        },
        statusColor(status) {
            return `status-${status}`;
        }
    },
    mounted() {
        this.checkAuth();
    }
}).mount('#app');
