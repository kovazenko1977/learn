const { createApp } = Vue;

createApp({
    data() {
        return {
            tab: 'dashboard',
            user: null,
            users: [],
            tasks: [],
            settings: { categories: [], priorities: [], form_fields: [], departments: [] },
            stats: { total: 0, completed: 0, in_work: 0, overdue: 0 },
            loading: false,
            searchQuery: '',

            // Modals and Forms
            showUserModal: false,
            userForm: { id: '', username: '', full_name: '', role: 'Executor', department: '', password: '' },
            showTaskModal: false,
            selectedTask: null,
            commentText: ''
        }
    },
    computed: {
        isAdmin() { return this.user && this.user.role === 'Administrator'; },
        filteredTasks() {
            if (!this.searchQuery) return this.tasks;
            const q = this.searchQuery.toLowerCase();
            return this.tasks.filter(t =>
                t.title.toLowerCase().includes(q) ||
                t.id.toLowerCase().includes(q) ||
                t.created_by_name.toLowerCase().includes(q)
            );
        }
    },
    async mounted() {
        await this.checkAuth();
        if (this.user && this.user.id) {
            await this.fetchSettings();
            await this.fetchData();
        }
    },
    methods: {
        async checkAuth() {
            const token = localStorage.getItem('crm_token');
            if (!token) { window.location.href = 'index.php'; return; }
            try {
                const res = await fetch('api/auth.php?action=me', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await res.json();
                if (data.success) {
                    this.user = data.user;
                } else { window.location.href = 'index.php'; }
            } catch (e) { window.location.href = 'index.php'; }
        },
        async fetchData() {
            this.loading = true;
            const token = localStorage.getItem('crm_token');
            const [tasksRes, usersRes] = await Promise.all([
                fetch('api/tasks.php', { headers: { 'Authorization': `Bearer ${token}` } }),
                this.isAdmin ? fetch('api/users.php', { headers: { 'Authorization': `Bearer ${token}` } }) : Promise.resolve({ json: () => [] })
            ]);
            this.tasks = await tasksRes.json();
            if (this.isAdmin) this.users = await usersRes.json();
            this.calculateStats();
            this.loading = false;
        },
        async fetchSettings() {
            const res = await fetch('api/settings.php');
            this.settings = await res.json();
        },
        calculateStats() {
            this.stats.total = this.tasks.length;
            this.stats.completed = this.tasks.filter(t => t.status === 'Completed').length;
            this.stats.in_work = this.tasks.filter(t => t.status === 'In Work' || t.status === 'Assigned').length;
            this.stats.overdue = this.tasks.filter(t => t.sla_status === 'overdue').length;
        },
        logout() {
            localStorage.removeItem('crm_token');
            window.location.href = 'index.php';
        },
        // Task Actions
        async updateTaskStatus(task, status) {
            const token = localStorage.getItem('crm_token');
            task.status = status;
            await fetch('api/tasks.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: JSON.stringify(task)
            });
            this.calculateStats();
        },
        async addComment() {
            if (!this.commentText) return;
            const token = localStorage.getItem('crm_token');
            await fetch('api/tasks.php?action=comment', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: JSON.stringify({ task_id: this.selectedTask.id, text: this.commentText })
            });
            this.commentText = '';
            this.fetchData(); // Refresh to get new comment
        },
        // User Actions
        async saveUser() {
            const token = localStorage.getItem('crm_token');
            await fetch('api/users.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: JSON.stringify(this.userForm)
            });
            this.showUserModal = false;
            this.fetchData();
        },
        async deleteUser(id) {
            if (!confirm('Удалить пользователя?')) return;
            const token = localStorage.getItem('crm_token');
            await fetch(`api/users.php?id=${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${token}` }
            });
            this.fetchData();
        },
        // Settings Actions
        async saveSettings() {
            const token = localStorage.getItem('crm_token');
            await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: JSON.stringify(this.settings)
            });
            alert('Настройки сохранены');
        }
    }
}).mount('#app');