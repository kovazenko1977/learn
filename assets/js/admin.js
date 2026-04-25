const { createApp, ref, onMounted, computed } = Vue;

createApp({
    setup() {
        const token = ref(localStorage.getItem('token'));
        const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
        const loginForm = ref({ username: '', password: '' });
        const error = ref('');
        const activeTab = ref('dashboard');
        const tasks = ref([]);
        const stats = ref({});
        const search = ref('');
        const usersList = ref([]);
        const settings = ref({});

        const selectedTask = ref(null);
        const newComment = ref('');

        const statusTranslations = {
            'New': 'Новые',
            'In Work': 'В работе',
            'Completed': 'Выполнено',
            'Rejected': 'Отклонено'
        };

        const availableTabs = [
            { id: 'dashboard', name: 'Аналитика', icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
            { id: 'kanban', name: 'Канбан-доска', icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2" /></svg>' },
            { id: 'tasks', name: 'Все задачи', icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>' },
            { id: 'users', name: 'Пользователи', icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>' },
            { id: 'forms', name: 'Формы', icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>' }
        ];

        const login = async () => {
            const resp = await fetch('api/auth.php', {
                method: 'POST',
                body: JSON.stringify(loginForm.value)
            });
            const data = await resp.json();
            if (data.token) {
                token.value = data.token;
                user.value = data.user;
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                fetchData();
            } else {
                error.value = data.error;
            }
        };

        const logout = () => {
            token.value = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        };

        const fetchData = async () => {
            if (!token.value) return;

            const headers = { 'Authorization': `Bearer ${token.value}` };

            const [tasksResp, statsResp, usersResp, settingsResp] = await Promise.all([
                fetch('api/tasks.php', { headers }),
                fetch('api/dashboard.php', { headers }),
                fetch('api/users.php', { headers }),
                fetch('api/settings.php', { headers })
            ]);

            tasks.value = await tasksResp.json();
            stats.value = await statsResp.json();
            usersList.value = await usersResp.json();
            settings.value = await settingsResp.json();

            if (selectedTask.value) {
                selectedTask.value = tasks.value.find(t => t.id == selectedTask.value.id);
            }
        };

        const dragTask = (ev, task) => {
            ev.dataTransfer.setData('taskId', task.id);
        };

        const dropTask = async (ev, status) => {
            const taskId = ev.dataTransfer.getData('taskId');
            await updateStatus(taskId, status);
        };

        const updateStatus = async (taskId, status) => {
            const resp = await fetch('api/tasks.php?action=update_status', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token.value}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: taskId, status })
            });
            if (resp.ok) fetchData();
        }

        const priorityClass = (p) => {
            if (p === 'High') return 'bg-red-500/20 text-red-400 border border-red-500/50';
            if (p === 'Medium') return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/50';
            return 'bg-blue-500/20 text-blue-400 border border-blue-500/50';
        };

        const statusColor = (s) => {
            if (s === 'New') return 'bg-blue-500';
            if (s === 'In Work') return 'bg-yellow-500';
            if (s === 'Completed') return 'bg-green-500';
            return 'bg-red-500';
        };

        const formatDate = (d) => {
            if (!d) return '-';
            return new Date(d).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
        };

        const assignTask = async (taskId, executorId) => {
            await fetch('api/tasks.php?action=assign', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token.value}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: taskId, executor_id: executorId })
            });
            fetchData();
        };

        const removeField = (index) => {
            settings.value.form_fields.splice(index, 1);
        };

        const addField = () => {
            settings.value.form_fields.push({ id: 'f_' + Date.now(), label: 'Новое поле', type: 'text', required: false });
        };

        const saveSettings = async () => {
            await fetch('api/settings.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token.value}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(settings.value)
            });
            alert('Настройки сохранены!');
        };

        const viewTask = (task) => {
            selectedTask.value = task;
        };

        const updateTaskStatus = async (task) => {
            await updateStatus(task.id, task.status);
        };

        const addComment = async () => {
            if (!newComment.value.trim()) return;
            await fetch('api/tasks.php?action=add_comment', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token.value}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: selectedTask.value.id, comment: newComment.value })
            });
            newComment.value = '';
            fetchData();
        };

        const filteredTasks = computed(() => {
            if (!search.value) return tasks.value;
            const s = search.value.toLowerCase();
            return tasks.value.filter(t =>
                t.fields?.f_subject?.toLowerCase().includes(s) ||
                t.fields?.f_description?.toLowerCase().includes(s) ||
                t.id.toString().includes(s)
            );
        });

        onMounted(() => {
            if (token.value) fetchData();
            setInterval(fetchData, 30000); // Auto refresh
        });

        return {
            token, user, loginForm, error, activeTab, tasks, stats, search, usersList, settings,
            selectedTask, newComment,
            availableTabs, statusTranslations,
            login, logout, dragTask, dropTask, priorityClass, statusColor, formatDate, filteredTasks,
            assignTask, removeField, addField, saveSettings, viewTask, updateTaskStatus, addComment
        }
    }
}).mount('#app');
