const { createApp } = Vue;

createApp({
    data() {
        return {
            token: localStorage.getItem('crm_token') || null,
            user: JSON.parse(localStorage.getItem('crm_user')) || {},
            loginForm: { username: '', password: '' },
            loading: false,
            error: null,
            tab: 'tasks',
            tasks: [],
            executors: [],
            allUsers: [],
            settings: { form_fields: [], departments: [] },
            backups: [],
            clearPeriod: { start: '', end: '' },
            selectedTask: null,
            taskModalTab: 'details', // 'details' or 'chat'
            statusComment: '',
            chatMessage: '',
            userModal: null,
            deptModal: null,
            menu: [],
            autoRefreshTimer: null,
            searchQuery: '',
            userSearch: '',
            deptSearch: '',
            taskFilter: { department_id: '', priority: '', date_start: '', date_end: '' },
            viewMode: 'kanban',
            templateName: '',
            statuses: [
                { id: 'new', name: 'Новые', color: 'bg-blue-500' },
                { id: 'assigned', name: 'В работе', color: 'bg-amber-500' },
                { id: 'completed', name: 'Выполнено', color: 'bg-green-500' },
                { id: 'rejected', name: 'Отклонено', color: 'bg-red-500' }
            ],
            themes: [
                { id: 'ultra-dark', name: 'Midnight Pro', colors: { primary: '#6366f1', bgMain: '#020617', bgGlass: 'rgba(15, 23, 42, 0.8)', bgCard: 'rgba(30, 41, 59, 0.4)', textMain: '#f8fafc', textDim: '#94a3b8', border: 'rgba(255, 255, 255, 0.05)' } },
                { id: 'corporate', name: 'Business Light', colors: { primary: '#2563eb', bgMain: '#f8fafc', bgGlass: 'rgba(255, 255, 255, 0.9)', bgCard: '#ffffff', textMain: '#0f172a', textDim: '#64748b', border: 'rgba(0, 0, 0, 0.05)' } },
                { id: 'emerald-pro', name: 'Emerald Glass', colors: { primary: '#10b981', bgMain: '#022c22', bgGlass: 'rgba(6, 78, 59, 0.8)', bgCard: 'rgba(6, 95, 70, 0.4)', textMain: '#f0fdf4', textDim: '#a7f3d0', border: 'rgba(16, 185, 129, 0.1)' } },
                { id: 'crimson', name: 'Crimson Velvet', colors: { primary: '#e11d48', bgMain: '#450a0a', bgGlass: 'rgba(127, 29, 29, 0.8)', bgCard: 'rgba(153, 27, 27, 0.4)', textMain: '#fef2f2', textDim: '#fecaca', border: 'rgba(225, 29, 72, 0.1)' } },
                { id: 'ocean-pro', name: 'Ocean Depth', colors: { primary: '#0ea5e9', bgMain: '#082f49', bgGlass: 'rgba(12, 74, 110, 0.8)', bgCard: 'rgba(7, 89, 133, 0.4)', textMain: '#f0f9ff', textDim: '#bae6fd', border: 'rgba(14, 165, 233, 0.1)' } },
                { id: 'luxury-gold', name: 'Luxury Gold', colors: { primary: '#fbbf24', bgMain: '#1c1917', bgGlass: 'rgba(41, 37, 36, 0.8)', bgCard: 'rgba(68, 64, 60, 0.4)', textMain: '#fef3c7', textDim: '#fcd34d', border: 'rgba(251, 191, 36, 0.1)' } },
                { id: 'cyberpunk', name: 'Neon Night', colors: { primary: '#f472b6', bgMain: '#170621', bgGlass: 'rgba(46, 16, 101, 0.8)', bgCard: 'rgba(88, 28, 135, 0.4)', textMain: '#fdf2f8', textDim: '#f9a8d4', border: 'rgba(244, 114, 182, 0.1)' } },
                { id: 'nordic-frost', name: 'Nordic Frost', colors: { primary: '#88c0d0', bgMain: '#2e3440', bgGlass: 'rgba(59, 66, 82, 0.8)', bgCard: 'rgba(76, 86, 106, 0.4)', textMain: '#eceff4', textDim: '#d8dee9', border: 'rgba(136, 192, 208, 0.1)' } },
                { id: 'matcha-zen', name: 'Matcha Zen', colors: { primary: '#65a30d', bgMain: '#f7fee7', bgGlass: 'rgba(236, 252, 203, 0.9)', bgCard: '#ffffff', textMain: '#1a2e05', textDim: '#4d7c0f', border: 'rgba(101, 163, 13, 0.1)' } },
                { id: 'minimal-gray', name: 'Modern Zinc', colors: { primary: '#18181b', bgMain: '#fafafa', bgGlass: 'rgba(255, 255, 255, 0.95)', bgCard: '#ffffff', textMain: '#09090b', textDim: '#71717a', border: 'rgba(0, 0, 0, 0.1)' } }
            ]
        }
    },
    computed: {
        googleFontUrl() {
            const font = this.settings.font_family || 'Inter';
            return `https://fonts.googleapis.com/css2?family=${font.replace(/ /g, '+')}:wght@300;400;500;600;700&display=swap`;
        },
        themeColors() {
            const themeId = this.settings.active_theme || 'slate';
            return this.themes.find(t => t.id === themeId)?.colors || this.themes[0].colors;
        },
        currentMenuName() {
            return this.menu.find(m => m.id === this.tab)?.name || 'CRM';
        },
        stats() {
            return [
                { label: 'Всего заявок', value: this.tasks.length, trend: 12 },
                { label: 'В работе', value: this.tasks.filter(t => t.status === 'assigned').length, trend: -5 },
                { label: 'Выполнено', value: this.tasks.filter(t => t.status === 'completed').length, trend: 8 },
                { label: 'Просрочено', value: this.tasks.filter(t => new Date(t.deadline) < new Date() && t.status !== 'completed').length, trend: 0 }
            ]
        },
        deptStats() {
            return this.tasks.reduce((acc, t) => {
                acc[t.department_id] = (acc[t.department_id] || 0) + 1;
                return acc;
            }, {});
        },
        filteredUsers() {
            return this.allUsers.filter(u =>
                u.full_name.toLowerCase().includes(this.userSearch.toLowerCase()) ||
                u.username.toLowerCase().includes(this.userSearch.toLowerCase())
            );
        },
        filteredDepts() {
            return this.settings.departments.filter(d =>
                d.name.toLowerCase().includes(this.deptSearch.toLowerCase())
            );
        },
        filteredTasksTable() {
            return this.tasks.filter(t => {
                const matchSearch = !this.searchQuery ||
                                   t.id.toString().includes(this.searchQuery) ||
                                   t.description.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchDept = !this.taskFilter.department_id || t.department_id == this.taskFilter.department_id;
                const matchPriority = !this.taskFilter.priority || t.priority == this.taskFilter.priority;

                let matchDate = true;
                if (this.taskFilter.date_start) matchDate = matchDate && t.created_at >= this.taskFilter.date_start;
                if (this.taskFilter.date_end) matchDate = matchDate && t.created_at <= this.taskFilter.date_end + ' 23:59:59';

                return matchSearch && matchDept && matchPriority && matchDate;
            });
        }
    },
    methods: {
        async api(url, options = {}) {
            if (this.token) {
                options.headers = {
                    ...options.headers,
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                };
            }
            const res = await fetch(url, options);
            if (res.status === 401) this.logout();
            return res.json();
        },
        async login() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.loginForm)
                });
                const result = await res.json();
                if (result.token) {
                    this.token = result.token;
                    this.user = result.user;
                    localStorage.setItem('crm_token', this.token);
                    localStorage.setItem('crm_user', JSON.stringify(this.user));
                    this.updateMenu();
                    this.loadData();
                } else {
                    this.error = result.error || 'Ошибка входа';
                }
            } catch (e) {
                this.error = 'Ошибка сервера';
            } finally {
                this.loading = false;
            }
        },
        logout() {
            this.token = null;
            localStorage.removeItem('crm_token');
            localStorage.removeItem('crm_user');
            this.menu = [];
        },
        updateMenu() {
            const m = [
                { id: 'dashboard', name: 'Аналитика', icon: 'layout-dashboard' },
                { id: 'tasks', name: 'Заявки', icon: 'kanban' }
            ];
            if (this.user.role === 'admin') {
                m.push({ id: 'forms', name: 'Конструктор', icon: 'file-text' });
                m.push({ id: 'departments', name: 'Отделы', icon: 'building' });
                m.push({ id: 'users', name: 'Персонал', icon: 'users' });
                m.push({ id: 'settings', name: 'Настройки', icon: 'settings' });
            } else if (this.user.role === 'head') {
                m.push({ id: 'users', name: 'Мой отдел', icon: 'users' });
            }
            m.push({ id: 'profile', name: 'Профиль', icon: 'user' });
            this.menu = m;
            this.$nextTick(() => lucide.createIcons());
        },
        async loadData(isBackground = false) {
            if (!this.token) return;
            if (!isBackground) this.loading = true;
            this.tasks = await this.api('api/tasks.php');
            this.settings = await this.api('api/settings.php');
            if (!this.settings.departments) this.settings.departments = [];

            this.applyGlobalStyles();

            this.allUsers = await this.api('api/register.php');
            if (this.user.role === 'head') {
                this.allUsers = this.allUsers.filter(u => u.department_id == this.user.department_id);
            }

            this.executors = this.allUsers.filter(u => u.role === 'executor' || u.role === 'admin' || u.role === 'head');
            if (this.user.role === 'admin') {
                this.backups = await this.api('api/admin_actions.php?action=list_backups');
            }
            this.$nextTick(() => lucide.createIcons());
            this.loading = false;
            this.setupAutoRefresh();
        },
        setupAutoRefresh() {
            if (this.autoRefreshTimer) clearInterval(this.autoRefreshTimer);
            const interval = parseInt(this.settings.refresh_interval || 30) * 1000;
            this.autoRefreshTimer = setInterval(() => {
                if (this.token && !document.hidden && this.tab === 'tasks') {
                    this.loadData(true);
                }
            }, interval);
        },
        filteredTasks(statusId) {
            return this.tasks.filter(t => {
                const matchStatus = t.status === statusId;
                const matchSearch = !this.searchQuery ||
                                   t.id.toString().includes(this.searchQuery) ||
                                   t.description.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchDept = !this.taskFilter.department_id || t.department_id == this.taskFilter.department_id;
                const matchPriority = !this.taskFilter.priority || t.priority == this.taskFilter.priority;

                let matchDate = true;
                if (this.taskFilter.date_start) matchDate = matchDate && t.created_at >= this.taskFilter.date_start;
                if (this.taskFilter.date_end) matchDate = matchDate && t.created_at <= this.taskFilter.date_end + ' 23:59:59';

                return matchStatus && matchSearch && matchDept && matchPriority && matchDate;
            });
        },
        priorityClass(p) {
            if (p === 'high') return 'bg-red-500/20 text-red-400 border border-red-500/30';
            if (p === 'medium') return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
            return 'bg-slate-500/20 text-slate-400 border border-slate-500/30';
        },
        statusColor(s) {
            return this.statuses.find(x => x.id === s)?.color || 'bg-slate-500';
        },
        deptName(id) {
            const d = this.settings.departments?.find(x => x.id == id);
            return d ? d.name : 'Прочее';
        },
        openTask(task) {
            this.selectedTask = { ...task };
            this.taskModalTab = 'details';
            this.statusComment = '';
            this.chatMessage = '';
            this.$nextTick(() => lucide.createIcons());
        },
        async updateTaskStatus(newStatus) {
            if (this.user.role !== 'admin' && !this.statusComment) {
                alert('Пожалуйста, оставьте комментарий при смене статуса');
                return;
            }
            const res = await this.api('api/tasks.php', {
                method: 'POST',
                body: JSON.stringify({
                    id: this.selectedTask.id,
                    status: newStatus,
                    comment: this.statusComment
                })
            });
            if (res.success) {
                this.statusComment = '';
                this.loadData();
                this.selectedTask = this.tasks.find(t => t.id === this.selectedTask.id);
            } else {
                alert(res.error || 'Ошибка обновления статуса');
            }
        },
        async assignExecutor() {
            const exec = this.executors.find(e => e.id == this.selectedTask.executor_id);
            await this.api('api/tasks.php', {
                method: 'POST',
                body: JSON.stringify({
                    id: this.selectedTask.id,
                    executor_id: this.selectedTask.executor_id,
                    executor_name: exec ? exec.full_name : ''
                })
            });
            this.loadData();
        },
        async sendChatMessage() {
            if (!this.chatMessage) return;
            const res = await this.api('api/tasks.php', {
                method: 'POST',
                body: JSON.stringify({
                    id: this.selectedTask.id,
                    new_message: this.chatMessage
                })
            });
            if (res.success) {
                this.chatMessage = '';
                this.loadData();
                this.selectedTask = this.tasks.find(t => t.id === this.selectedTask.id);
            }
        },
        addField() {
            if (!this.settings.form_fields) this.settings.form_fields = [];
            this.settings.form_fields.push({ id: Date.now(), label: '', type: 'text', required: false });
        },
        removeField(index) {
            this.settings.form_fields.splice(index, 1);
        },
        async saveSettings() {
            await this.api('api/settings.php', {
                method: 'POST',
                body: JSON.stringify(this.settings)
            });
            // Update root variables immediately
            const themeId = this.settings.active_theme || 'slate';
            const colors = this.themes.find(t => t.id === themeId)?.colors;
            if (colors) {
                Object.entries(colors).forEach(([k, v]) => {
                    document.documentElement.style.setProperty(`--${k}`, v);
                });
            }
            alert('Настройки сохранены');
        },
        async saveFormAsTemplate() {
            if (!this.templateName) {
                alert('Введите название шаблона');
                return;
            }
            await this.api('api/settings.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'save_template',
                    name: this.templateName,
                    fields: this.settings.form_fields
                })
            });
            this.templateName = '';
            this.loadData();
            alert('Шаблон сохранен');
        },
        applyTemplate(tpl) {
            this.settings.form_fields = [...tpl.fields];
        },
        async deleteTemplate(id) {
            if (confirm('Удалить этот шаблон?')) {
                this.settings.form_templates = this.settings.form_templates.filter(t => t.id !== id);
                await this.saveSettings();
            }
        },
        applyGlobalStyles() {
            // Apply Font
            if (this.settings.font_family) {
                const fontUrl = `https://fonts.googleapis.com/css2?family=${this.settings.font_family.replace(/ /g, '+')}:wght@300;400;500;600;700&display=swap`;
                document.getElementById('google-font').href = fontUrl;
                document.body.style.fontFamily = `'${this.settings.font_family}', sans-serif`;
            }
            if (this.settings.font_size) {
                document.querySelector('.text-base-custom').style.fontSize = this.settings.font_size;
            }

            // Apply Theme
            const themeId = this.settings.active_theme || 'slate';
            const colors = this.themes.find(t => t.id === themeId)?.colors;
            if (colors) {
                Object.entries(colors).forEach(([k, v]) => {
                    document.documentElement.style.setProperty(`--${k}`, v);
                });
            }
        },
        openUserModal(user) {
            this.userModal = user ? { ...user, password: '' } : { username: '', full_name: '', role: 'employee', password: '', department_id: this.user.role === 'head' ? this.user.department_id : '' };
        },
        async saveUser() {
            await this.api('api/register.php', {
                method: 'POST',
                body: JSON.stringify(this.userModal)
            });
            this.userModal = null;
            this.loadData();
        },
        async createBackup() {
            const res = await this.api('api/admin_actions.php?action=backup');
            if (res.success) alert('Бэкап создан: ' + res.backup_file);
            this.loadData();
        },
        async restoreBackup(filename) {
            if (confirm('ВНИМАНИЕ: Текущие данные будут заменены данными из бэкапа. Продолжить?')) {
                const res = await this.api('api/admin_actions.php?action=restore', {
                    method: 'POST',
                    body: JSON.stringify({ filename })
                });
                if (res.success) {
                    alert('Данные восстановлены. Страница будет перезагружена.');
                    location.reload();
                }
            }
        },
        async clearAllData() {
            if (confirm('Вы уверены, что хотите УДАЛИТЬ ВСЕ ЗАЯВКИ? Это действие необратимо.')) {
                await this.api('api/admin_actions.php?action=clear_all');
                this.loadData();
            }
        },
        async clearDataPeriod() {
            if (confirm(`Удалить данные за период ${this.clearPeriod.start} - ${this.clearPeriod.end}?`)) {
                await this.api('api/admin_actions.php?action=clear_period', {
                    method: 'POST',
                    body: JSON.stringify(this.clearPeriod)
                });
                this.loadData();
            }
        },
        async cleanupTemp() {
            if (confirm('Очистить все загруженные файлы?')) {
                await this.api('api/admin_actions.php?action=cleanup_temp');
                alert('Файлы очищены');
            }
        },
        async deleteUser(id) {
            const hasTasks = this.tasks.some(t => t.created_by == id || t.executor_id == id);
            if (hasTasks) {
                alert('Нельзя удалить сотрудника, у которого есть связанные заявки. Рекомендуется просто сменить ему пароль или роль.');
                return;
            }
            if (confirm('Удалить сотрудника?')) {
                await this.api(`api/register.php?id=${id}`, { method: 'DELETE' });
                this.loadData();
            }
        },
        openDeptModal(dept) {
            this.deptModal = dept ? { ...dept } : { id: Date.now(), name: '', description: '' };
        },
        async saveDept() {
            const index = this.settings.departments.findIndex(d => d.id === this.deptModal.id);
            if (index > -1) this.settings.departments[index] = this.deptModal;
            else this.settings.departments.push(this.deptModal);
            await this.saveSettings();
            this.deptModal = null;
        },
        async deleteDept(id) {
            if (confirm('Удалить отдел?')) {
                this.settings.departments = this.settings.departments.filter(d => d.id !== id);
                await this.saveSettings();
            }
        },
        async generateDemo() {
            if (confirm('Заполнить систему демо-данными?')) {
                await this.api('api/demo_data.php');
                this.loadData();
                alert('Демо-данные успешно созданы');
            }
        },
        async updateProfile() {
            const res = await this.api('api/register.php', {
                method: 'POST',
                body: JSON.stringify(this.user)
            });
            if (res.success) alert('Профиль обновлен');
        },
        exportCSV() {
            window.location.href = 'admin.php?export=csv&token=' + encodeURIComponent(this.token);
            },
        printTask(id) {
            window.open(`api/report.php?id=${id}&token=${encodeURIComponent(this.token)}`, '_blank');
        },
            startVoice(field) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) {
                    alert('Ваш браузер не поддерживает голосовой ввод');
                    return;
                }
                const recognition = new SpeechRecognition();
                recognition.lang = 'ru-RU';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    if (typeof this[field] === 'string') {
                        this[field] += ' ' + transcript;
                    } else {
                        this[field] = transcript;
                    }
                };
                recognition.start();
        }
    },
    async mounted() {
        if (this.token) {
            try {
                const userData = await this.api('api/auth.php?action=me');
                if (userData && userData.id) {
                    this.user = userData;
                    localStorage.setItem('crm_user', JSON.stringify(this.user));
                    this.updateMenu();
                }
            } catch (e) {
                this.logout();
            }
            this.updateMenu();
            this.loadData();
        }
        lucide.createIcons();

        // Boot loader handling
        setTimeout(() => {
            const loader = document.getElementById('boot-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 500);
            }
        }, 3000);
    },
    watch: {
        tab() {
            this.$nextTick(() => lucide.createIcons());
        },
        'settings.active_theme'() {
            this.applyGlobalStyles();
        },
        'settings.font_family'() {
            this.applyGlobalStyles();
        },
        'settings.font_size'() {
            this.applyGlobalStyles();
        }
    }
}).mount('#app');
