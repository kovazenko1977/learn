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
            templates: [],
            chat: [],
            logs: [],
            toasts: [],
            activeShift: null,
            shifts: [],
            newMessage: '',
            versions: [],
            notifications: [],
            stats: {},
            settings: { clinic_name: 'Dental CRM' },
            modules: {},
            widgetSnippet: '',
            activeLog: 'system.log',
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
                { id: 'templates', label: 'Шаблоны', roles: ['admin', 'senior_admin'] },
                { id: 'users', label: 'Пользователи', roles: ['admin'] },
                { id: 'logs', label: 'Логи и статус', roles: ['admin', 'director', 'support'] },
                { id: 'settings', label: 'Настройки', roles: ['admin'] }
            ],
            help: {
                appointments: 'Управление расписанием приемов, проверка конфликтов времени врача и кабинетов.',
                patients: 'Единая база пациентов с медицинскими картами, историей изменений и тегами.',
                doctors: 'Список специалистов клиники, их специализации и привязка к кабинетам.',
                services: 'Каталог оказываемых услуг с указанием длительности и стоимости.',
                rooms: 'Учет лечебных кабинетов и их загрузки.',
                tasks: 'Внутренние задачи для персонала, связанные с пациентами или записями.',
                finance: 'Контроль доходов и расходов, управление кассовыми сменами.',
                analytics: 'Статистические отчеты по загрузке, популярности услуг и финансам.',
                documents: 'Генерация и хранение документов (договоры, рекомендации, заключения).',
                chat: 'Внутренний чат для оперативного общения сотрудников.',
                tags: 'Цветовые метки для сегментации пациентов и задач.',
                sources: 'Аналитика каналов привлечения пациентов (реклама, сайт и др.).',
                templates: 'Настройка шаблонов для быстрой подготовки документов.',
                users: 'Управление доступом сотрудников и их ролями в системе.',
                logs: 'Просмотр системных логов и состояния здоровья системы.',
                settings: 'Системные настройки клиники и управление модулями.'
            }
        }
    },
    computed: {
        menu() {
            if (!this.user) return [];
            return this.rawMenu.filter(m => m.roles.includes(this.user.role));
        }
    },
    methods: {
        toast(msg, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, msg, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3000);
        },
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
                    this.toast(this.error, 'error');
                    return null;
                }
                return result;
            } catch (e) {
                this.error = 'Ошибка соединения с сервером';
                this.toast(this.error, 'error');
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
        toggleTag(tagId) {
            if (!this.form.tags) this.form.tags = [];
            const index = this.form.tags.indexOf(tagId);
            if (index > -1) {
                this.form.tags.splice(index, 1);
            } else {
                this.form.tags.push(tagId);
            }
        },
        async logout() {
            await this.api('auth', { action: 'logout' });
            this.isLoggedIn = false;
            this.user = null;
        },
        async openPatientCard(patient) {
            const fullPatient = await this.api('patients', { id: patient.id });
            if (fullPatient) {
                this.openModal('patient', fullPatient);
            }
        },
        async checkAuth() {
            const result = await this.api('auth', { action: 'check' });
            if (result && result.isLoggedIn) {
                this.isLoggedIn = true;
                this.user = result.user;
                this.loadData();
            }
        },
        async loadWidgetSnippet() {
            const res = await this.api('widget');
            if (res) this.widgetSnippet = res.snippet;
        },
        async loadLogs(type = 'system.log') {
            this.activeLog = type;
            const res = await this.api('logs', { type });
            if (res) this.logs = res.content;
        },
        async loadData() {
            const loaders = [
                this.api('finance', { action: 'get_active_shift' }).then(res => this.activeShift = res),
                this.api('finance', { action: 'shifts_history' }).then(res => this.shifts = res || []),
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
                this.api('templates').then(res => this.templates = res || []),
                this.api('chat', { dialog_id: 'general' }).then(res => this.chat = res || []),
                this.api('analytics').then(res => this.stats = res || {})
            ];

            if (this.user.role === 'admin') {
                loaders.push(this.api('users').then(res => this.users = res || []));
                loaders.push(this.api('settings', { type: 'system' }).then(res => this.settings = res || { clinic_name: 'Dental CRM' }));
                loaders.push(this.api('settings', { type: 'modules' }).then(res => this.modules = res || {}));
                this.loadWidgetSnippet();
                this.loadLogs();
            }

            await Promise.allSettled(loaders);
        },
        openModal(type, item = null) {
            this.modal = type;
            this.form = item ? JSON.parse(JSON.stringify(item)) : { status: 'planned' };
            if (type === 'patient' && this.form.files) {
                this.form.files_raw = this.form.files.join('\n');
            }
            this.modalTitle = item ? 'Редактировать' : 'Добавить';
        },
        async saveForm() {
            const result = await this.api(this.modal, {}, 'POST', this.form);
            if (result && (result.success || result.id)) {
                this.toast('Сохранено успешно');
                this.modal = null;
                this.loadData();
            }
        },
        async saveItem(module, item) {
            const result = await this.api(module, {}, 'POST', item);
            if (result) {
                this.toast('Обновлено');
                this.loadData();
            }
        },
        async deleteItem(module, id) {
            if (confirm('Вы уверены?')) {
                const result = await this.api(module, { id }, 'DELETE');
                if (result) {
                    this.toast('Удалено');
                    this.loadData();
                }
            }
        },
        async saveSettings(type) {
            const data = type === 'modules' ? this.modules : this.settings;
            await this.api('settings', { type }, 'POST', data);
            alert('Настройки сохранены');
        },
        async resetData() {
            if (confirm('ВНИМАНИЕ! Вы уверены, что хотите удалить ВСЕ данные? Это действие необратимо.')) {
                const res = await this.api('settings', { action: 'reset_data' }, 'POST');
                if (res && res.success) {
                    alert('Данные удалены. Система будет перезагружена.');
                    location.reload();
                }
            }
        },
        async createBackup() {
            const res = await this.api('settings', { action: 'create_backup' }, 'POST');
            if (res && res.success) {
                alert('Бэкап создан в папке storage/backups/');
            }
        },
        exportCSV(module) {
            window.location.href = `api/index.php?module=${module}&action=export_csv`;
        },
        async seedData() {
            if (confirm('Загрузить демонстрационные данные? Текущие данные останутся.')) {
                const res = await this.api('settings', { action: 'seed_data' }, 'POST');
                if (res && res.success) {
                    alert('Демо-данные загружены.');
                    this.loadData();
                }
            }
        },
        async openShift() {
            if (confirm('Открыть кассовую смену?')) {
                await this.api('finance', { action: 'open_shift' }, 'POST');
                this.loadData();
            }
        },
        async closeShift() {
            if (confirm('Закрыть кассовую смену?')) {
                await this.api('finance', { action: 'close_shift' }, 'POST');
                this.loadData();
            }
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
        applyTemplate(template) {
            let content = template.content;
            const patient = this.patients.find(p => p.id === this.form.patient_id);
            if (patient) {
                content = content.replace(/{{patient_name}}/g, patient.full_name);
                content = content.replace(/{{date}}/g, new Date().toLocaleDateString());
            }
            this.form.content = content;
        },
        printDocument(doc) {
            const patient = this.patients.find(p => p.id === doc.patient_id);
            const win = window.open('', '_blank');
            win.document.write(`
                <html>
                    <head>
                        <title>${doc.title || doc.type}</title>
                        <style>
                            body { font-family: serif; padding: 40px; line-height: 1.6; }
                            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
                            .meta { margin-bottom: 30px; }
                            .content { white-space: pre-wrap; }
                            @media print { .no-print { display: none; } }
                        </style>
                    </head>
                    <body>
                        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
                            <button onclick="window.print()">Распечатать / Сохранить в PDF</button>
                        </div>
                        <div class="header">
                            <h1>${this.settings.clinic_name}</h1>
                            <p>${this.settings.clinic_address} | ${this.settings.clinic_phone}</p>
                        </div>
                        <div class="meta">
                            <p><strong>Документ:</strong> ${doc.title || doc.type}</p>
                            <p><strong>Пациент:</strong> ${patient ? patient.full_name : 'N/A'}</p>
                            <p><strong>Дата:</strong> ${new Date(doc.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="content">${doc.content}</div>
                    </body>
                </html>
            `);
            win.document.close();
        },
        statusColor(status) {
            return `status-${status}`;
        }
    },
    mounted() {
        this.checkAuth();
    }
}).mount('#app');
