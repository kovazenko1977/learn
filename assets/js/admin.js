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
            templateName: '',
            statuses: [
                { id: 'new', name: 'Новые', color: 'bg-blue-500' },
                { id: 'assigned', name: 'В работе', color: 'bg-amber-500' },
                { id: 'completed', name: 'Выполнено', color: 'bg-green-500' },
                { id: 'rejected', name: 'Отклонено', color: 'bg-red-500' }
            ],
            themes: [
                { id: 'slate', name: 'Slate Night', colors: { primary: '#6366f1', bgMain: '#0f172a', bgGlass: 'rgba(15, 23, 42, 0.9)', bgCard: 'rgba(30, 41, 59, 0.5)', textMain: '#e2e8f0', border: 'rgba(255, 255, 255, 0.1)' } },
                { id: 'emerald', name: 'Emerald Forest', colors: { primary: '#10b981', bgMain: '#064e3b', bgGlass: 'rgba(6, 78, 59, 0.9)', bgCard: 'rgba(6, 95, 70, 0.5)', textMain: '#ecfdf5', border: 'rgba(16, 185, 129, 0.2)' } },
                { id: 'ruby', name: 'Ruby Wine', colors: { primary: '#e11d48', bgMain: '#4c0519', bgGlass: 'rgba(76, 5, 25, 0.9)', bgCard: 'rgba(136, 19, 55, 0.5)', textMain: '#fff1f2', border: 'rgba(225, 29, 72, 0.2)' } },
                { id: 'ocean', name: 'Deep Ocean', colors: { primary: '#0ea5e9', bgMain: '#0c4a6e', bgGlass: 'rgba(12, 74, 110, 0.9)', bgCard: 'rgba(7, 89, 133, 0.5)', textMain: '#f0f9ff', border: 'rgba(14, 165, 233, 0.2)' } },
                { id: 'purple', name: 'Royal Purple', colors: { primary: '#a855f7', bgMain: '#3b0764', bgGlass: 'rgba(59, 7, 100, 0.9)', bgCard: 'rgba(88, 28, 135, 0.5)', textMain: '#faf5ff', border: 'rgba(168, 85, 247, 0.2)' } },
                { id: 'gold', name: 'Cyber Gold', colors: { primary: '#f59e0b', bgMain: '#1c1917', bgGlass: 'rgba(28, 25, 23, 0.9)', bgCard: 'rgba(41, 37, 36, 0.5)', textMain: '#fef3c7', border: 'rgba(245, 158, 11, 0.3)' } },
                { id: 'minimal-light', name: 'Minimal Light', colors: { primary: '#0f172a', bgMain: '#f8fafc', bgGlass: 'rgba(255, 255, 255, 0.9)', bgCard: '#ffffff', textMain: '#0f172a', border: 'rgba(0, 0, 0, 0.1)' } },
                { id: 'coffee', name: 'Roasted Coffee', colors: { primary: '#a16207', bgMain: '#271b12', bgGlass: 'rgba(39, 27, 18, 0.9)', bgCard: 'rgba(63, 45, 33, 0.5)', textMain: '#fefce8', border: 'rgba(161, 98, 7, 0.2)' } },
                { id: 'nordic', name: 'Nordic Frost', colors: { primary: '#88c0d0', bgMain: '#2e3440', bgGlass: 'rgba(46, 52, 64, 0.9)', bgCard: 'rgba(59, 66, 82, 0.5)', textMain: '#eceff4', border: 'rgba(136, 192, 208, 0.2)' } },
                { id: 'dracula', name: 'Dracula', colors: { primary: '#bd93f9', bgMain: '#282a36', bgGlass: 'rgba(40, 42, 54, 0.9)', bgCard: 'rgba(68, 71, 90, 0.5)', textMain: '#f8f8f2', border: 'rgba(189, 147, 249, 0.2)' } },
                { id: 'synthwave', name: 'Synthwave', colors: { primary: '#ff79c6', bgMain: '#2b213a', bgGlass: 'rgba(43, 33, 58, 0.9)', bgCard: 'rgba(58, 44, 78, 0.5)', textMain: '#f8f8f2', border: 'rgba(255, 121, 198, 0.2)' } },
                { id: 'midnight', name: 'True Midnight', colors: { primary: '#3b82f6', bgMain: '#000000', bgGlass: 'rgba(0, 0, 0, 0.95)', bgCard: 'rgba(15, 23, 42, 0.5)', textMain: '#ffffff', border: 'rgba(255, 255, 255, 0.05)' } },
                { id: 'matcha', name: 'Soft Matcha', colors: { primary: '#65a30d', bgMain: '#f7fee7', bgGlass: 'rgba(247, 254, 231, 0.9)', bgCard: '#ffffff', textMain: '#1a2e05', border: 'rgba(101, 163, 13, 0.1)' } },
                { id: 'rose', name: 'Rose Quartz', colors: { primary: '#db2777', bgMain: '#fff1f2', bgGlass: 'rgba(255, 241, 242, 0.9)', bgCard: '#ffffff', textMain: '#4c0519', border: 'rgba(219, 39, 119, 0.1)' } },
                { id: 'amber', name: 'Amber Glow', colors: { primary: '#d97706', bgMain: '#451a03', bgGlass: 'rgba(69, 26, 3, 0.9)', bgCard: 'rgba(120, 53, 15, 0.5)', textMain: '#fffbeb', border: 'rgba(217, 119, 6, 0.2)' } },
                { id: 'indigo', name: 'Indigo Dream', colors: { primary: '#4f46e5', bgMain: '#1e1b4b', bgGlass: 'rgba(30, 27, 75, 0.9)', bgCard: 'rgba(49, 46, 129, 0.5)', textMain: '#e0e7ff', border: 'rgba(79, 70, 229, 0.2)' } },
                { id: 'gray-modern', name: 'Gray Modern', colors: { primary: '#18181b', bgMain: '#f4f4f5', bgGlass: 'rgba(255, 255, 255, 0.9)', bgCard: '#ffffff', textMain: '#18181b', border: 'rgba(0, 0, 0, 0.05)' } },
                { id: 'teal', name: 'Teal Lagoon', colors: { primary: '#0d9488', bgMain: '#042f2e', bgGlass: 'rgba(4, 47, 46, 0.9)', bgCard: 'rgba(19, 78, 74, 0.5)', textMain: '#f0fdfa', border: 'rgba(13, 148, 136, 0.2)' } },
                { id: 'orange', name: 'Vivid Orange', colors: { primary: '#ea580c', bgMain: '#431407', bgGlass: 'rgba(67, 20, 7, 0.9)', bgCard: 'rgba(124, 45, 18, 0.5)', textMain: '#fff7ed', border: 'rgba(234, 88, 12, 0.2)' } },
                { id: 'sky', name: 'Sky High', colors: { primary: '#0284c7', bgMain: '#f0f9ff', bgGlass: 'rgba(240, 249, 255, 0.9)', bgCard: '#ffffff', textMain: '#082f49', border: 'rgba(2, 132, 199, 0.1)' } },
                { id: 'pink', name: 'Cyber Pink', colors: { primary: '#f472b6', bgMain: '#1e0714', bgGlass: 'rgba(30, 7, 20, 0.9)', bgCard: 'rgba(62, 11, 40, 0.5)', textMain: '#fdf2f8', border: 'rgba(244, 114, 182, 0.2)' } },
                { id: 'lime', name: 'Acid Lime', colors: { primary: '#bef264', bgMain: '#1a2e05', bgGlass: 'rgba(26, 46, 5, 0.9)', bgCard: 'rgba(32, 45, 8, 0.5)', textMain: '#f7fee7', border: 'rgba(190, 242, 100, 0.2)' } },
                { id: 'chocolate', name: 'Dark Chocolate', colors: { primary: '#78350f', bgMain: '#1c1917', bgGlass: 'rgba(28, 25, 23, 0.9)', bgCard: 'rgba(41, 37, 36, 0.5)', textMain: '#fef3c7', border: 'rgba(120, 53, 15, 0.2)' } },
                { id: 'royal', name: 'Royal Blue', colors: { primary: '#2563eb', bgMain: '#1e1b4b', bgGlass: 'rgba(30, 27, 75, 0.9)', bgCard: 'rgba(49, 46, 129, 0.5)', textMain: '#f0f9ff', border: 'rgba(37, 99, 235, 0.2)' } },
                { id: 'sepia', name: 'Sepia Memory', colors: { primary: '#92400e', bgMain: '#fef3c7', bgGlass: 'rgba(254, 243, 199, 0.9)', bgCard: '#fffbeb', textMain: '#451a03', border: 'rgba(146, 64, 14, 0.1)' } }
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
                { id: 'dashboard', name: 'Аналитика', icon: 'bar-chart-3' },
                { id: 'tasks', name: 'Заявки', icon: 'layout-kanban' }
            ];
            if (this.user.role === 'admin') {
                m.push({ id: 'forms', name: 'Конструктор', icon: 'form-input' });
                m.push({ id: 'departments', name: 'Отделы', icon: 'building-2' });
                m.push({ id: 'users', name: 'Персонал', icon: 'users' });
                m.push({ id: 'settings', name: 'Настройки', icon: 'settings' });
            } else if (this.user.role === 'head') {
                m.push({ id: 'users', name: 'Мой отдел', icon: 'users' });
            }
            m.push({ id: 'profile', name: 'Профиль', icon: 'user' });
            this.menu = m;
        },
        async loadData(isBackground = false) {
            if (!this.token) return;
            if (!isBackground) this.loading = true;
            this.tasks = await this.api('api/tasks.php');
            this.settings = await this.api('api/settings.php');
            if (!this.settings.departments) this.settings.departments = [];

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
                await fetch('api/demo_data.php');
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
            window.location.href = 'admin.php?export=csv';
            },
            startVoice(target, field) {
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
                    if (typeof target[field] === 'string') {
                        target[field] += ' ' + transcript;
                    } else {
                        target[field] = transcript;
                    }
                };
                recognition.start();
        }
    },
    mounted() {
        if (this.token) {
            this.updateMenu();
            this.loadData();
        }
        lucide.createIcons();
    },
    watch: {
        tab() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}).mount('#app');
