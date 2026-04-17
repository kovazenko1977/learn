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
        async api(module, action = '', method = 'GET', data = null) {
            this.error = '';
            const url = `api/index.php?module=${module}&action=${action}`;
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
            const result = await this.api('auth', 'login', 'POST', this.loginForm);
            if (result && result.success) {
                this.isLoggedIn = true;
                this.user = result.user;
                this.loadData();
            }
        },
        async logout() {
            await this.api('auth', 'logout');
            this.isLoggedIn = false;
            this.user = null;
        },
        async checkAuth() {
            const result = await this.api('auth', 'check');
            if (result && result.isLoggedIn) {
                this.isLoggedIn = true;
                this.user = result.user;
                this.loadData();
            }
        },
        async loadData() {
            this.patients = await this.api('patients') || [];
            this.doctors = await this.api('doctors') || [];
            this.services = await this.api('services') || [];
            this.rooms = await this.api('rooms') || [];
            this.appointments = await this.api('appointments') || [];
            if (this.user.role === 'admin') {
                this.users = await this.api('users') || [];
            }
        },
        openModal(type, item = null) {
            this.modal = type;
            this.form = item ? JSON.parse(JSON.stringify(item)) : { status: 'planned' };
            this.modalTitle = item ? 'Редактировать' : 'Добавить';
        },
        async saveForm() {
            const result = await this.api(this.modal, '', 'POST', this.form);
            if (result && (result.success || result.id)) {
                this.modal = null;
                this.loadData();
            }
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
        statusColor(status) {
            return `status-${status}`;
        }
    },
    mounted() {
        this.checkAuth();
    }
}).mount('#app');
