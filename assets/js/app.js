const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
    setup() {
        const authenticated = ref(false);
        const user = ref(null);
        const loading = ref(false);
        const error = ref('');
        const currentTab = ref('dashboard');
        const pinParts = reactive(['', '', '', '', '', '']);

        const stats = ref({ total_clients: 0, total_leads: 0, pending_tasks: 0, recent_logs: [] });
        const clients = ref([]);
        const leads = ref([]);
        const tasks = ref([]);
        const docs = ref([]);
        const users = ref([]);
        const searchQuery = ref('');
        const showTimelineModal = ref(false);
        const selectedClient = ref(null);
        const interactions = ref([]);
        const newInteraction = reactive({ type: 'note', text: '' });

        const showClientModal = ref(false);
        const editingClient = ref(null);
        const clientForm = reactive({ name: '', email: '', phone: '', status: 'lead', source: 'direct', tags: '', address: '', birthday: '', social_links: '' });

        const showLeadModal = ref(false);
        const leadForm = reactive({ title: '', value: 0, status: 'new', source: 'direct', tags: '', expected_closing: '', probability: 50, email: '', phone: '' });

        const showTaskModal = ref(false);
        const taskForm = reactive({ title: '', priority: 'Medium', due_date: '', status: 'pending', assigned_to: '', client_id: '' });
        const taskFilter = ref('');

        const mailing = reactive({ subject: '', body: '', recipients: [] });

        const activeChat = ref('all');
        const messages = ref([]);
        const newMessage = ref('');
        const unreadCount = ref(0);
        const chatSearchQuery = ref('');
        const replyingTo = ref(null);

        const menu = [
            { id: 'dashboard', name: 'Обзор', icon: 'fas fa-chart-pie' },
            { id: 'clients', name: 'Клиенты', icon: 'fas fa-address-book' },
            { id: 'sales', name: 'Продажи', icon: 'fas fa-funnel-dollar' },
            { id: 'marketing', name: 'Маркетинг', icon: 'fas fa-bullhorn' },
            { id: 'tasks', name: 'Задачи', icon: 'fas fa-check-circle' },
            { id: 'docs', name: 'Документы', icon: 'fas fa-file-alt' },
            { id: 'chat', name: 'Чат', icon: 'fas fa-comments' },
            { id: 'team', name: 'Команда', icon: 'fas fa-users-cog', adminOnly: true }
        ];

        const salesStages = [
            { id: 'new', name: 'Новые' },
            { id: 'contacted', name: 'Контакт' },
            { id: 'proposal', name: 'Предложение' },
            { id: 'negotiation', name: 'Переговоры' },
            { id: 'closed', name: 'Закрыто' }
        ];

        const activeMenuName = computed(() => menu.find(m => m.id === currentTab.value)?.name || '');
        const filteredMenu = computed(() => menu.filter(m => !m.adminOnly || (user.value && user.value.role === 'admin')));
        const otherUsers = computed(() => users.value.filter(u => u.id !== user.value?.id));
        const activeChatName = computed(() => {
            if (activeChat.value === 'all') return 'Общий чат';
            return users.value.find(u => u.id === activeChat.value)?.name || 'Чат';
        });

        const filteredTasks = computed(() => {
            let t = tasks.value;
            if (taskFilter.value) t = t.filter(x => x.priority === taskFilter.value);
            return t.sort((a,b) => (a.status === 'completed' ? 1 : -1));
        });

        const filteredClients = computed(() => {
            if (!searchQuery.value) return clients.value;
            const q = searchQuery.value.toLowerCase();
            return clients.value.filter(c =>
                c.name.toLowerCase().includes(q) ||
                c.email.toLowerCase().includes(q) ||
                c.phone.toLowerCase().includes(q)
            );
        });

        // Multi-user & Team
        const showUserModal = ref(false);
        const userForm = reactive({ id: 'new', name: '', role: 'manager', pin: '' });

        const fetchUsers = async () => { users.value = await (await fetch('api/users.php')).json(); };
        const openUserModal = (u = null) => {
            if (u) { userForm.id = u.id; userForm.name = u.name; userForm.role = u.role; userForm.pin = ''; }
            else { userForm.id = 'new'; userForm.name = ''; userForm.role = 'manager'; userForm.pin = ''; }
            showUserModal.value = true;
        };
        const saveUser = async () => {
            await fetch('api/users.php', { method: 'POST', body: JSON.stringify(userForm) });
            showUserModal.value = false; fetchUsers();
        };
        const deleteUser = async (id) => { if (confirm('Удалить сотрудника?')) { await fetch(`api/users.php?id=${id}`, { method: 'DELETE' }); fetchUsers(); } };

        // Funnel
        const getStagePercentage = (stageId) => {
            const stageLeads = leads.value.filter(l => l.status === stageId);
            const count = stageLeads.length;
            if (count === 0) return 5;
            const max = Math.max(...salesStages.map(s => leads.value.filter(l => l.status === s.id).length));
            return (count / (max || 1)) * 100;
        };

        const getLeadScore = (client) => {
            let score = 1;
            if (client.status === 'active') score += 2;
            if (client.email && client.phone) score += 1;
            if (client.created_at && (new Date() - new Date(client.created_at)) < 86400000 * 7) score += 1;
            return Math.min(score, 5);
        };

        const initChart = () => {
            const ctx = document.getElementById('performanceChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                        datasets: [{
                            label: 'Сделки',
                            data: [12, 19, 3, 5, 2, 3, 9],
                            borderColor: '#7360f2',
                            tension: 0.4,
                            fill: true,
                            backgroundColor: 'rgba(115, 96, 242, 0.1)'
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, display: false }, x: { grid: { display: false } } }
                    }
                });
            }

            const ctxS = document.getElementById('sourcesChart');
            if (ctxS) {
                const sourceCounts = { direct: 0, ad: 0, social: 0, referral: 0 };
                clients.value.forEach(c => { if (sourceCounts[c.source] !== undefined) sourceCounts[c.source]++; });

                new Chart(ctxS, {
                    type: 'doughnut',
                    data: {
                        labels: ['Прямой', 'Реклама', 'Соцсети', 'Реф'],
                        datasets: [{
                            data: [sourceCounts.direct, sourceCounts.ad, sourceCounts.social, sourceCounts.referral],
                            backgroundColor: ['#7360f2', '#ffbc42', '#3fb1ce', '#d72638']
                        }]
                    },
                    options: {
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
                        cutout: '70%'
                    }
                });
            }
        };

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
                if (data.success) { authenticated.value = true; user.value = data.user; fetchData(); }
                else error.value = data.error;
            } catch (e) { error.value = 'Server error'; }
            finally { loading.value = false; }
        };

        const logout = async () => { await fetch('api/auth.php?action=logout'); authenticated.value = false; user.value = null; };
        const checkAuth = async () => {
            const res = await fetch('api/auth.php?action=check');
            const data = await res.json();
            if (data.authenticated) { authenticated.value = true; user.value = data.user; fetchData(); }
        };

        const fetchData = async () => {
            await Promise.all([fetchStats(), fetchClients(), fetchLeads(), fetchTasks(), fetchDocs(), fetchUsers()]);
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
            const p = { ...leadForm }; if (editingLead.value) p.id = editingLead.value.id;
            await fetch('api/leads.php', { method: 'POST', body: JSON.stringify(p) });
            showLeadModal.value = false; fetchData();
        };

        const editingLead = ref(null);
        const editLead = (l) => { editingLead.value = l; Object.assign(leadForm, l); showLeadModal.value = true; };
        const convertLead = async (id) => {
            if (confirm('Конвертировать лид в клиента?')) {
                await fetch('api/leads.php?action=convert', { method: 'POST', body: JSON.stringify({ id }) });
                fetchData();
            }
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

        // Interaction History
        const viewTimeline = async (client) => {
            selectedClient.value = client;
            showTimelineModal.value = true;
            fetchInteractions();
        };

        const fetchInteractions = async () => {
            if (!selectedClient.value) return;
            const res = await fetch(`api/interactions.php?client_id=${selectedClient.value.id}`);
            interactions.value = await res.json();
        };

        const addInteraction = async () => {
            if (!newInteraction.text.trim() || !selectedClient.value) return;
            const payload = { ...newInteraction, client_id: selectedClient.value.id };
            await fetch('api/interactions.php', { method: 'POST', body: JSON.stringify(payload) });
            newInteraction.text = '';
            fetchInteractions();
            fetchUsers(); // Update points
        };

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

        // Chat Logic
        const fetchMessages = async () => {
            if (!authenticated.value) return;
            const url = `api/chat.php?recipient=${activeChat.value}${chatSearchQuery.value ? '&search='+encodeURIComponent(chatSearchQuery.value) : ''}`;
            const res = await fetch(url);
            messages.value = await res.json();

            if (!chatSearchQuery.value) {
                setTimeout(() => {
                    const box = document.getElementById('chat-box');
                    if (box) box.scrollTop = box.scrollHeight;
                }, 100);
            }
        };

        const sendMessage = async () => {
            if (!newMessage.value.trim()) return;
            const payload = {
                recipient: activeChat.value,
                text: newMessage.value,
                reply_to: replyingTo.value ? replyingTo.value.id : null,
                reply_text: replyingTo.value ? replyingTo.value.text : null
            };
            await fetch('api/chat.php', { method: 'POST', body: JSON.stringify(payload) });
            newMessage.value = '';
            replyingTo.value = null;
            fetchMessages();
        };

        const setReply = (msg) => {
            replyingTo.value = msg;
            document.getElementById('chat-input-field').focus();
        };

        // Polling
        let pollInterval = null;
        const startPolling = () => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(() => {
                if (authenticated.value) {
                    if (currentTab.value === 'chat') fetchMessages();
                }
            }, 3000);
        };
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

        onMounted(() => {
            checkAuth();
            startPolling();
            setTimeout(initChart, 500);
        });

        const forecastRevenue = computed(() => {
            return leads.value.reduce((acc, l) => acc + (Number(l.value) * (Number(l.probability) || 50) / 100), 0).toFixed(0);
        });

        return {
            authenticated, user, loading, error, currentTab, pinParts, stats, clients, leads, tasks, docs, users, searchQuery,
            showTimelineModal, selectedClient, interactions, newInteraction,
            showClientModal, editingClient, clientForm, showLeadModal, leadForm, showTaskModal, taskForm, taskFilter,
            showUserModal, userForm, activeChat, messages, newMessage, unreadCount, chatSearchQuery, replyingTo,
            editingLead, forecastRevenue,
            mailing, menu, filteredMenu, salesStages, activeMenuName, filteredTasks, filteredClients, otherUsers, activeChatName,
            focusNext, focusPrev, login, logout, saveClient, editClient, deleteClient, openLeadModal, saveLead, editLead, convertLead,
            fetchUsers, openUserModal, saveUser, deleteUser, getStagePercentage, getLeadScore,
            fetchMessages, sendMessage, setReply, viewTimeline, fetchInteractions, addInteraction,
            saveTask, toggleTask, deleteTask, addRecipient, removeRecipient, sendMailing, uploadFile, downloadDoc, deleteDoc, getFileIcon, clientStatusClass,
            leadsByStage: (s) => leads.value.filter(l => l.status === s)
        };
    }
}).mount('#app');
