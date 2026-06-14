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
                storage_mode: 'json',
                sla_rules: {
                    low: 72,
                    medium: 48,
                    high: 24,
                    urgent: 4
                },
                mysql: { host: 'localhost', db: '', user: '', pass: '' }
            },
            stats: null,
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
            profileForm: { currentPassword: '', newPassword: '', full_name: '', phone: '' },
            userForm: { username: '', password: '', role: 'executor', full_name: '', phone: '' },
            showUserModal: false,
            selectedTaskIds: [],
            showForgotModal: false,
            forgotForm: { username: '' },
            forgotSuccess: false,
            forgotMessage: ''
        }
    },
    computed: {
        filteredTasks() {
            return this.tasks.filter(t => {
                const matchesSearch = t.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                     t.description.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesPriority = !this.filterPriority || t.priority === this.filterPriority;
                return matchesSearch && matchesPriority;
            });
        }
    },
    methods: {
        async init() {
            const res = await fetch('api/auth.php?action=me');
            this.user = await res.json();
            if (this.user) {
                this.loadData();
                this.profileForm.full_name = this.user.full_name;
                this.profileForm.phone = this.user.phone;
            }
        },
        async login() {
            this.loginError = '';
            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    body: JSON.stringify(this.loginForm)
                });
                if (res.ok) {
                    const data = await res.json();
                    this.user = data.user;
                    localStorage.setItem('token', data.token);
                    this.loadData();
                } else {
                    const err = await res.json();
                    this.loginError = err.error;
                }
            } catch (e) {
                this.loginError = 'Ошибка соединения';
            }
        },
        async logout() {
            await fetch('api/auth.php?action=logout', { method: 'POST' });
            this.user = null;
            localStorage.removeItem('token');
        },
        async forgotPassword() {
            const res = await fetch('api/auth.php?action=forgot_password', {
                method: 'POST',
                body: JSON.stringify(this.forgotForm)
            });
            const data = await res.json();
            if (res.ok) {
                this.forgotSuccess = true;
                this.forgotMessage = data.message;
            } else {
                alert(data.error);
            }
        },
        async loadData() {
            const [tasksRes, settingsRes, usersRes, statsRes] = await Promise.all([
                fetch('api/tasks.php'),
                fetch('api/settings.php'),
                fetch('api/auth.php?action=users'),
                (this.user.role === 'admin' || this.user.role === 'head') ? fetch('api/analytics.php') : Promise.resolve(null)
            ]);

            this.tasks = await tasksRes.json();
            this.settings = await settingsRes.json();
            if (usersRes.ok) this.usersList = await usersRes.json();
            if (statsRes && statsRes.ok) this.stats = await statsRes.json();
        },
        async selectTask(task) {
            this.selectedTask = task;
            const [commRes, histRes] = await Promise.all([
                fetch(`api/tasks.php?action=comments&task_id=${task.id}`),
                fetch(`api/tasks.php?action=history&task_id=${task.id}`)
            ]);
            this.comments = await commRes.json();
            this.history = await histRes.json();
        },
        async addComment() {
            if (!this.newComment) return;
            await fetch('api/tasks.php?action=comment', {
                method: 'POST',
                body: JSON.stringify({ task_id: this.selectedTask.id, text: this.newComment })
            });
            this.newComment = '';
            this.selectTask(this.selectedTask);
        },
        async updateTaskStatus(task, status) {
            await fetch('api/tasks.php', {
                method: 'POST',
                body: JSON.stringify({ id: task.id, status: status })
            });
            this.loadData();
            if (this.selectedTask && this.selectedTask.id === task.id) {
                this.selectTask({ ...task, status });
            }
        },
        async assignTask(task, userId) {
            await fetch('api/tasks.php', {
                method: 'POST',
                body: JSON.stringify({ id: task.id, executor_id: userId, status: 'assigned' })
            });
            this.loadData();
        },
        async massAssign(executorId) {
            if (!executorId || this.selectedTaskIds.length === 0) return;
            await fetch('api/tasks.php?action=mass_assign', {
                method: 'POST',
                body: JSON.stringify({ ids: this.selectedTaskIds, executor_id: executorId })
            });
            this.selectedTaskIds = [];
            this.loadData();
        },
        async saveSettings() {
            await fetch('api/settings.php', {
                method: 'POST',
                body: JSON.stringify(this.settings)
            });
            alert('Настройки сохранены');
        },
        async registerUser() {
            await fetch('api/auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify(this.userForm)
            });
            this.showUserModal = false;
            this.loadData();
        },
        async updateProfile() {
            const res = await fetch('api/auth.php?action=update_profile', {
                method: 'POST',
                body: JSON.stringify(this.profileForm)
            });
            if (res.ok) alert('Профиль обновлен');
            else {
                const err = await res.json();
                alert(err.error);
            }
        },
        exportData() {
            window.location.href = 'api/analytics.php?action=export';
        },
        addField() {
            this.settings.form_fields.push({ id: Date.now(), label: 'Новое поле', type: 'text', required: false });
        },
        removeField(index) {
            this.settings.form_fields.splice(index, 1);
        },
        onDragStart(task) {
            this.draggedTask = task;
        },
        async onDrop(status) {
            if (this.draggedTask) {
                await this.updateTaskStatus(this.draggedTask, status);
                this.draggedTask = null;
            }
        },
        getPriorityClass(p) {
            return `priority-${p}`;
        },
        getStatusLabel(s) {
            return this.statuses.find(st => st.id === s)?.label || s;
        },
        getUserName(id) {
            return this.usersList.find(u => u.id == id)?.full_name || 'Не назначен';
        },
        toggleTaskSelection(id) {
            const index = this.selectedTaskIds.indexOf(id);
            if (index > -1) this.selectedTaskIds.splice(index, 1);
            else this.selectedTaskIds.push(id);
        }
    },
    mounted() {
        this.init();
    }
}).mount('#app');
