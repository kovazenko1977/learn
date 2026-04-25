const { createApp } = Vue;

createApp({
    data() {
        return {
            user: null,
            tab: 'tasks',
            tasks: [],
            usersList: [],
            settings: {
                form_fields: [],
                categories: [],
                storage_mode: 'json'
            },
            loginForm: { username: '', password: '' },
            loginError: '',
            statuses: [
                { id: 'new', label: 'Новые' },
                { id: 'assigned', label: 'Назначены' },
                { id: 'in_work', label: 'В работе' },
                { id: 'completed', label: 'Выполнены' },
                { id: 'rejected', label: 'Отклонены' }
            ],
            draggedTask: null,
            searchQuery: '',
            filterPriority: '',
            selectedTask: null,
            comments: [],
            history: [],
            newComment: '',
            isDarkMode: false,
            mobileMenu: false,
            profileForm: { currentPassword: '', newPassword: '' },
            selectedTaskIds: []
        }
    },
    computed: {
        menuItems() {
            return [
                { id: 'tasks', label: 'Заявки', show: true },
                { id: 'users', label: 'Пользователи', show: this.isAdmin },
                { id: 'settings', label: 'Настройки', show: this.isAdmin },
                { id: 'analytics', label: 'Аналитика', show: true },
                { id: 'profile', label: 'Профиль', show: true }
            ];
        },
        isAdmin() { return this.user?.role === 'admin'; },
        canAssign() { return ['admin', 'head'].includes(this.user?.role); },
        executors() { return this.usersList.filter(u => u.role === 'executor'); },
        executorsLoad() {
            if (!this.usersList.length) return [];
            const load = this.executors.map(u => {
                const count = this.tasks.filter(t => t.executor_id == u.id && t.status !== 'completed').length;
                return { id: u.id, name: u.full_name, count: count };
            });
            const max = Math.max(...load.map(l => l.count), 1);
            return load.map(l => ({ ...l, percent: (l.count / max) * 100 }));
        },
        avgCompletionTime() {
            const completed = this.tasks.filter(t => t.status === 'completed' && t.completed_at);
            if (!completed.length) return 0;
            const total = completed.reduce((acc, t) => {
                const diff = new Date(t.completed_at) - new Date(t.created_at);
                return acc + diff;
            }, 0);
            return Math.round(total / completed.length / (1000 * 60 * 60)); // hours
        },
        slaCompliance() {
            const completed = this.tasks.filter(t => t.status === 'completed');
            if (!completed.length) return 0;
            const inTime = completed.filter(t => new Date(t.completed_at) <= new Date(t.deadline)).length;
            return Math.round((inTime / completed.length) * 100);
        }
    },
    methods: {
        async init() {
            const res = await fetch('api/auth.php?action=me');
            if (res.ok) {
                this.user = await res.json();
                this.fetchData();
            }
        },
        async login() {
            this.loginError = '';
            const res = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.loginForm)
            });
            if (res.ok) {
                const data = await res.json();
                this.user = data.user;
                localStorage.setItem('token', data.token);
                this.fetchData();
            } else {
                this.loginError = 'Неверный логин или пароль';
            }
        },
        async logout() {
            await fetch('api/auth.php?action=logout', { method: 'POST' });
            this.user = null;
            localStorage.removeItem('token');
        },
        async fetchData() {
            const headers = { 'Authorization': `Bearer ${localStorage.getItem('token')}` };
            const [tasksRes, settingsRes] = await Promise.all([
                fetch('api/tasks.php', { headers }),
                fetch('api/settings.php', { headers })
            ]);
            this.tasks = await tasksRes.json();
            this.settings = await settingsRes.json();
            if (this.isAdmin || this.user.role === 'head') {
                const usersRes = await fetch('api/register.php');
                this.usersList = await usersRes.json();
            }
        },
        filteredTasks(status) {
            return this.tasks.filter(t => {
                const matchesStatus = t.status === status;
                const matchesSearch = t.title.toLowerCase().includes(this.searchQuery.toLowerCase()) || String(t.id).includes(this.searchQuery);
                const matchesPriority = !this.filterPriority || t.priority === this.filterPriority;
                return matchesStatus && matchesSearch && matchesPriority;
            });
        },
        onDragStart(e, task) {
            this.draggedTask = task;
            e.dataTransfer.effectAllowed = 'move';
        },
        async onDrop(e, status) {
            if (!this.draggedTask) return;
            const task = this.draggedTask;
            const oldStatus = task.status;
            task.status = status;

            const payload = { id: task.id, status: status };

            const res = await fetch('api/tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(payload)
            });

            if (!res.ok) {
                task.status = oldStatus;
                alert('Ошибка при смене статуса');
            }
            this.draggedTask = null;
        },
        async assignTask(task, userId) {
            const oldExecutor = task.executor_id;
            task.executor_id = userId;
            const newStatus = task.status === 'new' ? 'assigned' : task.status;

            const res = await fetch('api/tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    id: task.id,
                    executor_id: userId,
                    status: newStatus
                })
            });

            if (res.ok) {
                task.status = newStatus;
                const executor = this.usersList.find(u => u.id == userId);
                task.executor_name = executor ? executor.full_name : null;
            } else {
                task.executor_id = oldExecutor;
                alert('Ошибка при назначении исполнителя');
            }
        },
        async saveSettings() {
            const res = await fetch('api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(this.settings)
            });
            if (res.ok) alert('Настройки сохранены');
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
        },
        isLate(deadline) {
            if (!deadline) return false;
            return new Date(deadline) < new Date();
        },
        async openTask(task) {
            this.selectedTask = task;
            const [commRes, histRes] = await Promise.all([
                fetch(`api/tasks.php?action=comments&task_id=${task.id}`, {
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
                }),
                fetch(`api/tasks.php?action=history&task_id=${task.id}`, {
                    headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
                })
            ]);
            this.comments = await commRes.json();
            this.history = await histRes.json();
        },
        async updatePassword() {
            const res = await fetch('api/auth.php?action=change_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(this.profileForm)
            });
            if (res.ok) {
                alert('Пароль успешно изменен');
                this.profileForm = { currentPassword: '', newPassword: '' };
            } else {
                const err = await res.json();
                alert('Ошибка: ' + (err.error || 'Неизвестная ошибка'));
            }
        },
        selectAll(status) {
            this.selectedTaskIds = this.filteredTasks(status).map(t => t.id);
        },
        deselectAll() {
            this.selectedTaskIds = [];
        },
        async massAssign(userId) {
            if (!userId || this.selectedTaskIds.length === 0) return;
            const promises = this.selectedTaskIds.map(id => {
                const task = this.tasks.find(t => t.id == id);
                return this.assignTask(task, userId);
            });
            await Promise.all(promises);
            this.selectedTaskIds = [];
        },
        async addComment() {
            if (!this.newComment.trim()) return;
            const res = await fetch('api/tasks.php?action=comment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({ task_id: this.selectedTask.id, text: this.newComment })
            });
            if (res.ok) {
                this.comments.push({
                    text: this.newComment,
                    user_name: this.user.full_name,
                    created_at: new Date().toISOString()
                });
                this.newComment = '';
            }
        },
        priorityLabel(p) {
            const labels = { low: 'Низкий', medium: 'Средний', high: 'Высокий', urgent: 'Критический' };
            return labels[p] || p;
        },
        statusLabel(s) {
            const labels = { new: 'Новая', assigned: 'Назначена', in_work: 'В работе', completed: 'Выполнена', rejected: 'Отклонена' };
            return labels[s] || s;
        },
        priorityColor(p) {
            const colors = { low: 'text-blue-500', medium: 'text-amber-500', high: 'text-orange-500', urgent: 'text-red-500' };
            return colors[p] || 'text-slate-500';
        },
        exportCSV() {
            let csv = "\uFEFFID;Заголовок;Статус;Приоритет;Категория;Создан\n";
            this.tasks.forEach(t => {
                csv += `${t.id};${t.title};${t.status};${t.priority};${t.category};${t.created_at}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `tasks_export_${new Date().toISOString().slice(0,10)}.csv`;
            link.click();
        }
    },
    mounted() {
        this.init();
    }
}).mount('#app');
