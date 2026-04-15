const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
    setup() {
        const authenticated = ref(false);
        const loading = ref(false);
        const error = ref('');
        const currentTab = ref('dashboard');
        const pinParts = reactive(['', '', '', '', '', '']);

        const stats = ref({
            total_clients: 0,
            total_leads: 0,
            pending_tasks: 0,
            recent_logs: []
        });

        const clients = ref([]);
        const leads = ref([]);
        const tasks = ref([]);
        const docs = ref([]);

        const showClientModal = ref(false);
        const editingClient = ref(null);
        const clientForm = reactive({ name: '', email: '', phone: '', status: 'lead' });

        const showLeadModal = ref(false);
        const leadForm = reactive({ title: '', value: 0, status: 'new' });

        const showTaskModal = ref(false);
        const taskForm = reactive({ title: '', priority: 'Medium', due_date: '', status: 'pending' });
        const taskFilter = ref('');

        const mailing = reactive({ subject: '', body: '', recipients: [] });

        const menu = [
            { id: 'dashboard', name: 'Обзор', icon: 'fas fa-chart-pie' },
            { id: 'clients', name: 'Клиенты', icon: 'fas fa-users' },
            { id: 'sales', name: 'Продажи', icon: 'fas fa-funnel-dollar' },
            { id: 'marketing', name: 'Маркетинг', icon: 'fas fa-bullhorn' },
            { id: 'tasks', name: 'Задачи', icon: 'fas fa-check-circle' },
            { id: 'docs', name: 'Документы', icon: 'fas fa-file-alt' }
        ];

        const salesStages = [
            { id: 'new', name: 'Новые' },
            { id: 'contacted', name: 'Контакт' },
            { id: 'proposal', name: 'Предложение' },
            { id: 'negotiation', name: 'Переговоры' },
            { id: 'closed', name: 'Закрыто' }
        ];

        const activeMenuName = computed(() => menu.find(m => m.id === currentTab.value)?.name || '');

        const filteredTasks = computed(() => {
            let t = tasks.value;
            if (taskFilter.value) t = t.filter(x => x.priority === taskFilter.value);
            return t.sort((a,b) => (a.status === 'completed' ? 1 : -1));
        });

        // Auth
        const focusNext = (e, i) => { if (e.target.value.length === 1 && i < 5) document.getElementById(`pin-${i + 1}`).focus(); };
        const focusPrev = (e, i) => { if (e.target.value.length === 0 && i > 0) document.getElementById(`pin-${i - 1}`).focus(); };

        const login = async () => {
            const pin = pinParts.join('');
            if (pin.length < 6) return;
            loading.value = true;
            try {
                const res = await fetch('api/auth.php?action=login', { method: 'POST', body: JSON.stringify({ pin }) });
                const data = await res.json();
                if (data.success) { authenticated.value = true; fetchData(); }
                else error.value = data.error;
            } catch (e) { error.value = 'Server error'; }
            finally { loading.value = false; }
        };

        const logout = async () => { await fetch('api/auth.php?action=logout'); authenticated.value = false; };
        const checkAuth = async () => {
            const res = await fetch('api/auth.php?action=check');
            const data = await res.json();
            if (data.authenticated) { authenticated.value = true; fetchData(); }
        };

        const fetchData = async () => {
            await Promise.all([fetchStats(), fetchClients(), fetchLeads(), fetchTasks(), fetchDocs()]);
        };

        const fetchStats = async () => { stats.value = await (await fetch('api/stats.php')).json(); };
        const fetchClients = async () => { clients.value = await (await fetch('api/clients.php')).json(); };
        const fetchLeads = async () => { leads.value = await (await fetch('api/leads.php')).json(); };
        const fetchTasks = async () => { tasks.value = await (await fetch('api/tasks.php')).json(); };
        const fetchDocs = async () => { docs.value = await (await fetch('api/uploads.php?action=list')).json(); };

        // CRUDs
        const saveClient = async () => {
            loading.value = true;
            const p = { ...clientForm }; if (editingClient.value) p.id = editingClient.value.id;
            await fetch('api/clients.php', { method: 'POST', body: JSON.stringify(p) });
            showClientModal.value = false; fetchData(); loading.value = false;
        };

        const editClient = (c) => { editingClient.value = c; Object.assign(clientForm, c); showClientModal.value = true; };
        const deleteClient = async (id) => { if (confirm('Удалить?')) { await fetch(`api/clients.php?id=${id}`, { method: 'DELETE' }); fetchData(); } };

        const openLeadModal = (stage) => { leadForm.status = stage; showLeadModal.value = true; };
        const saveLead = async () => {
            await fetch('api/leads.php', { method: 'POST', body: JSON.stringify(leadForm) });
            showLeadModal.value = false; fetchData();
        };

        const saveTask = async () => {
            await fetch('api/tasks.php', { method: 'POST', body: JSON.stringify(taskForm) });
            showTaskModal.value = false; fetchData();
        };

        const toggleTask = async (task) => {
            task.status = task.status === 'completed' ? 'pending' : 'completed';
            await fetch('api/tasks.php', { method: 'POST', body: JSON.stringify(task) });
            fetchTasks(); fetchStats();
        };

        const deleteTask = async (id) => { await fetch(`api/tasks.php?id=${id}`, { method: 'DELETE' }); fetchData(); };

        // Marketing
        const addRecipient = (e) => {
            const email = e.target.value.trim();
            if (email && !mailing.recipients.includes(email)) { mailing.recipients.push(email); e.target.value = ''; }
        };
        const removeRecipient = (email) => { mailing.recipients = mailing.recipients.filter(r => r !== email); };
        const sendMailing = async () => {
            loading.value = true;
            await fetch('api/mailing.php', { method: 'POST', body: JSON.stringify(mailing) });
            alert('Sent!'); mailing.recipients = []; fetchData(); loading.value = false;
        };

        // Docs
        const uploadFile = async (e) => {
            const file = e.target.files[0]; if (!file) return;
            const formData = new FormData(); formData.append('file', file);
            await fetch('api/uploads.php?action=upload', { method: 'POST', body: formData });
            fetchDocs();
        };
        const downloadDoc = (id) => { window.location.href = `api/uploads.php?action=download&id=${id}`; };
        const deleteDoc = async (id) => { if (confirm('Удалить документ?')) { await fetch(`api/uploads.php?action=delete&id=${id}`); fetchDocs(); } };
        const getFileIcon = (n) => {
            const e = n.split('.').pop().toLowerCase();
            if (['jpg','png','jpeg'].includes(e)) return 'fas fa-file-image';
            if (e === 'pdf') return 'fas fa-file-pdf';
            if (['doc','docx'].includes(e)) return 'fas fa-file-word';
            return 'fas fa-file-alt';
        };

        const clientStatusClass = (s) => {
            if (s === 'active') return 'bg-green-100 text-green-700';
            if (s === 'lead') return 'bg-blue-100 text-blue-700';
            return 'bg-red-100 text-red-700';
        };

        onMounted(() => checkAuth());

        return {
            authenticated, loading, error, currentTab, pinParts, stats, clients, leads, tasks, docs,
            showClientModal, editingClient, clientForm, showLeadModal, leadForm, showTaskModal, taskForm, taskFilter,
            mailing, menu, salesStages, activeMenuName, filteredTasks,
            focusNext, focusPrev, login, logout, saveClient, editClient, deleteClient, openLeadModal, saveLead,
            saveTask, toggleTask, deleteTask, addRecipient, removeRecipient, sendMailing, uploadFile, downloadDoc, deleteDoc, getFileIcon, clientStatusClass,
            leadsByStage: (s) => leads.value.filter(l => l.status === s)
        };
    }
}).mount('#app');
