const { createApp } = Vue;

const translations = {
    ru: {
        tasks: 'Заявки',
        kanban: 'Канбан',
        analytics: 'Аналитика',
        constructor: 'Конструктор',
        settings: 'Настройки',
        logout: 'Выход',
        taskList: 'Список заявок',
        id: 'ID',
        title: 'Заголовок',
        status: 'Статус',
        priority: 'Приоритет',
        executor: 'Исполнитель',
        search: 'Поиск...',
        allPriorities: 'Все приоритеты',
        selected: 'Выбрано',
        assign: 'Назначить...',
        kanbanBoard: 'Канбан-доска',
        allTasks: 'Всего заявок',
        completed: 'Выполнено',
        slaBreaches: 'SLA Нарушения',
        avgTime: 'Среднее время',
        userLoad: 'Нагрузка на сотрудников',
        taskStatuses: 'Статусы заявок',
        fieldConstructor: 'Конструктор полей',
        addField: 'Добавить поле',
        saveForm: 'Сохранить форму',
        systemSettings: 'Системные настройки',
        storageMode: 'Режим хранения',
        userManagement: 'Управление пользователями',
        addUser: 'Добавить пользователя',
        description: 'Описание',
        comments: 'Комментарии',
        history: 'История',
        changedStatus: 'изменил статус на',
        notAssigned: 'Не назначен',
        new: 'Новые',
        assigned: 'Назначены',
        in_work: 'В работе',
        rejected: 'Отклонено',
        save: 'Сохранить'
    },
    en: {
        tasks: 'Tasks',
        kanban: 'Kanban',
        analytics: 'Analytics',
        constructor: 'Constructor',
        settings: 'Settings',
        logout: 'Logout',
        taskList: 'Task List',
        id: 'ID',
        title: 'Title',
        status: 'Status',
        priority: 'Priority',
        executor: 'Executor',
        search: 'Search...',
        allPriorities: 'All priorities',
        selected: 'Selected',
        assign: 'Assign...',
        kanbanBoard: 'Kanban Board',
        allTasks: 'Total Tasks',
        completed: 'Completed',
        slaBreaches: 'SLA Breaches',
        avgTime: 'Avg Time',
        userLoad: 'Employee Load',
        taskStatuses: 'Task Statuses',
        fieldConstructor: 'Field Constructor',
        addField: 'Add Field',
        saveForm: 'Save Form',
        systemSettings: 'System Settings',
        storageMode: 'Storage Mode',
        userManagement: 'User Management',
        addUser: 'Add User',
        description: 'Description',
        comments: 'Comments',
        history: 'History',
        changedStatus: 'changed status to',
        notAssigned: 'Not assigned',
        new: 'New',
        assigned: 'Assigned',
        in_work: 'In Progress',
        rejected: 'Rejected',
        save: 'Save'
    }
};

createApp({
    data() {
        return {
            user: null,
            tab: 'tasks',
            lang: localStorage.getItem('lang') || 'ru',
            darkMode: localStorage.getItem('darkMode') === 'true',
            tasks: [],
            settings: {
                categories: [],
                priorities: [],
                form_fields: [],
                storage_mode: 'json',
                mysql: { host: '', db: '', user: '', pass: '' }
            },
            usersList: [],
            stats: null,
            selectedTask: null,
            comments: [],
            history: [],
            newComment: '',
            searchQuery: '',
            filterPriority: '',
            selectedTaskIds: [],
            loginForm: { username: '', password: '' },
            loginError: '',
            showForgotModal: false,
            forgotForm: { username: '' },
            forgotSuccess: false,
            forgotMessage: '',
            showUserModal: false,
            userForm: { username: '', password: '', full_name: '', role: 'executor' },
            profileForm: { current_password: '', new_password: '', full_name: '', phone: '' },
            draggedTask: null,
            statuses: [
                { id: 'new', label: 'new' },
                { id: 'assigned', label: 'assigned' },
                { id: 'in_work', label: 'in_work' },
                { id: 'completed', label: 'completed' },
                { id: 'rejected', label: 'rejected' }
            ]
        };
    },
    computed: {
        t() {
            return translations[this.lang];
        },
        filteredTasks() {
            return this.tasks.filter(t => {
                const title = t.title || '';
                const matchesSearch = title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                    t.id.toString().includes(this.searchQuery);
                const matchesPriority = this.filterPriority ? t.priority === this.filterPriority : true;
                return matchesSearch && matchesPriority;
            });
        }
    },
    methods: {
        async init() {
            if (this.darkMode) document.documentElement.classList.add('dark');
            const res = await fetch('api/auth.php?action=me');
            const user = await res.json();
            if (user) {
                this.user = user;
                this.loadData();
            }
        },
        toggleLang() {
            this.lang = this.lang === 'ru' ? 'en' : 'ru';
            localStorage.setItem('lang', this.lang);
        },
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            if (this.darkMode) document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        },
        async login() {
            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
            return this.t[s] || s;
        },
        getUserName(id) {
            return this.usersList.find(u => u.id == id)?.full_name || this.t.notAssigned;
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
