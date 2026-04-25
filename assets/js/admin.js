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
            settings: { form_fields: [] },
            selectedTask: null,
            userModal: null,
            menu: [
                { id: 'dashboard', name: 'Аналитика', icon: 'bar-chart-3' },
                { id: 'tasks', name: 'Заявки', icon: 'layout-kanban' },
                { id: 'forms', name: 'Конструктор', icon: 'form-input' },
                { id: 'users', name: 'Персонал', icon: 'users' },
                { id: 'settings', name: 'Настройки', icon: 'settings' }
            ],
            statuses: [
                { id: 'new', name: 'Новые', color: 'bg-blue-500' },
                { id: 'assigned', name: 'В работе', color: 'bg-amber-500' },
                { id: 'completed', name: 'Выполнено', color: 'bg-green-500' },
                { id: 'rejected', name: 'Отклонено', color: 'bg-red-500' }
            ]
        }
    },
    computed: {
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
        },
        async loadData() {
            if (!this.token) return;
            this.tasks = await this.api('api/tasks.php');
            this.settings = await this.api('api/settings.php');
            this.allUsers = await this.api('api/register.php');
            this.executors = this.allUsers.filter(u => u.role === 'executor' || u.role === 'admin' || u.role === 'head');
            this.$nextTick(() => lucide.createIcons());
        },
        filteredTasks(statusId) {
            return this.tasks.filter(t => t.status === statusId);
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
            const depts = { '1': 'Сантехника', '2': 'Электрика', '3': 'Оборудование', '4': 'Мебель' };
            return depts[id] || 'Прочее';
        },
        openTask(task) {
            this.selectedTask = { ...task };
            this.$nextTick(() => lucide.createIcons());
        },
        async updateTask() {
            await this.api('api/tasks.php', {
                method: 'POST',
                body: JSON.stringify(this.selectedTask)
            });
            this.loadData();
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
            alert('Настройки сохранены');
        },
        openUserModal(user) {
            this.userModal = user ? { ...user, password: '' } : { username: '', full_name: '', role: 'employee', password: '' };
        },
        async saveUser() {
            await this.api('api/register.php', {
                method: 'POST',
                body: JSON.stringify(this.userModal)
            });
            this.userModal = null;
            this.loadData();
        },
        async deleteUser(id) {
            if (confirm('Удалить сотрудника?')) {
                await this.api(`api/register.php?id=${id}`, { method: 'DELETE' });
                this.loadData();
            }
        },
        exportCSV() {
            window.location.href = 'admin.php?export=csv';
        }
    },
    mounted() {
        if (this.token) this.loadData();
        lucide.createIcons();
    },
    watch: {
        tab() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}).mount('#app');
