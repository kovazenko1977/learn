/**
 * МедСервис - PWA Corporate Hospital Client Application
 */

class MedServiceApp {
    constructor() {
        this.currentUser = null;
        this.activeView = 'dashboard';
        this.services = [];
        this.chats = [];
        this.activeChatId = 1; // 1 = General Chat
        this.pollInterval = null;
    }

    async init() {
        this.setupNavigation();

        // 1. Check if installed
        try {
            const res = await fetch('api/setup');
            const setup = await res.json();
            if (!setup.installed) {
                document.getElementById('installerModal').classList.add('active');
                return;
            }
        } catch (e) {
            console.error('Setup check failed:', e);
        }

        // 2. Check current user session
        await this.checkAuth();
    }

    async checkAuth() {
        try {
            const res = await fetch('api/user');
            if (res.ok) {
                this.currentUser = await res.json();
                this.onLoginSuccess();
            } else {
                document.getElementById('loginModal').classList.add('active');
            }
        } catch (e) {
            document.getElementById('loginModal').classList.add('active');
        }
    }

    onLoginSuccess() {
        document.getElementById('loginModal').classList.remove('active');
        document.getElementById('registerModal').classList.remove('active');

        document.getElementById('greetingText').innerText = `Добрый день, ${this.currentUser.name.split(' ')[0]}!`;
        document.getElementById('headerUserName').innerText = this.currentUser.name;

        // Check onboarding
        if (!localStorage.getItem('medservice_onboarding_done')) {
            document.getElementById('onboardingModal').classList.add('active');
        }

        // Start polling for chats & notifications
        this.startPolling();
        this.loadDashboardData();
        this.loadServices();
    }

    startPolling() {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => {
            this.loadStats();
            if (this.activeView === 'chats') {
                this.loadChatMessages(this.activeChatId, true);
            }
        }, 4000);
    }

    // ==========================================
    // NAVIGATION & VIEWS
    // ==========================================
    setupNavigation() {
        document.querySelectorAll('.sidebar-nav .nav-item, .mobile-nav .mobile-nav-item').forEach(el => {
            el.addEventListener('click', (e) => {
                const view = el.dataset.view;
                if (view) {
                    this.switchView(view);
                }
            });
        });
    }

    switchView(viewName, params = {}) {
        this.activeView = viewName;

        // Update nav active states
        document.querySelectorAll('.sidebar-nav .nav-item, .mobile-nav .mobile-nav-item').forEach(el => {
            if (el.dataset.view === viewName) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        // Hide all views
        document.querySelectorAll('.app-view').forEach(v => v.style.display = 'none');

        // Show active view
        const targetView = document.getElementById(`view-${viewName}`);
        if (targetView) {
            targetView.style.display = 'block';
        }

        // Trigger view specific loaders
        if (viewName === 'dashboard') this.loadDashboardData();
        if (viewName === 'requests') this.loadRequests(params);
        if (viewName === 'directory') this.loadDirectory();
        if (viewName === 'chats') this.loadChats();
        if (viewName === 'employees') this.loadEmployees();
        if (viewName === 'services') this.loadServicesManager();
        if (viewName === 'notifications') this.loadNotifications();
        if (viewName === 'analytics') this.loadAnalytics();
    }

    // ==========================================
    // DASHBOARD & STATS
    // ==========================================
    async loadDashboardData() {
        this.loadStats();
        this.loadFastServices();
        this.loadRecentRequests();
    }

    async loadStats() {
        try {
            const res = await fetch('api/statistics');
            if (res.ok) {
                const stats = await res.json();
                document.getElementById('cntNew').innerText = stats.new;
                document.getElementById('cntInProgress').innerText = stats.in_progress;
                document.getElementById('cntCompleted').innerText = stats.completed;
                document.getElementById('cntEmergency').innerText = stats.emergency;

                if (stats.new > 0) {
                    document.getElementById('badgeRequests').innerText = stats.new;
                    document.getElementById('badgeRequests').style.display = 'inline-block';
                } else {
                    document.getElementById('badgeRequests').style.display = 'none';
                }
            }
        } catch (e) {
            console.error('Failed to load stats', e);
        }
    }

    async loadFastServices() {
        try {
            const res = await fetch('api/services');
            if (res.ok) {
                this.services = await res.json();
                const container = document.getElementById('fastServicesContainer');
                container.innerHTML = this.services.map(s => `
                    <div class="service-card" onclick="app.openServiceCallModal(${s.id})">
                        <div class="service-icon">${s.icon || '⚡'}</div>
                        <div class="service-name">${s.name}</div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">${s.internal_phone ? 'внутр. ' + s.internal_phone : s.phone}</div>
                    </div>
                `).join('');
            }
        } catch (e) {
            console.error('Failed to load services', e);
        }
    }

    async loadRecentRequests() {
        try {
            const res = await fetch('api/requests?limit=5');
            if (res.ok) {
                const data = await res.json();
                const requests = data.data || [];
                const container = document.getElementById('recentRequestsContainer');

                if (requests.length === 0) {
                    container.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-muted);">Заявок пока нет</div>';
                    return;
                }

                container.innerHTML = requests.map(r => this.renderRequestCard(r)).join('');
            }
        } catch (e) {
            console.error('Failed to load recent requests', e);
        }
    }

    renderRequestCard(r) {
        let badgeClass = 'badge-new';
        if (r.status === 'Принято' || r.status === 'В исполнении') badgeClass = 'badge-progress';
        if (r.status === 'Выполнено') badgeClass = 'badge-completed';
        if (r.priority === 'Аварийный') badgeClass = 'badge-emergency';

        return `
            <div class="request-card" onclick="app.openRequestDetailModal(${r.id})">
                <div class="request-header">
                    <span class="request-num">${r.number} — ${r.category}</span>
                    <span class="badge ${badgeClass}">${r.priority === 'Аварийный' ? '🚨 Аварийная' : r.status}</span>
                </div>
                <div class="request-desc">${r.description}</div>
                <div class="request-meta">
                    <span>📍 ${r.location_text}</span>
                    <span>🏢 ${r.service_name}</span>
                    <span>👤 ${r.author_name}</span>
                    <span>🕒 ${r.created_at}</span>
                </div>
            </div>
        `;
    }

    // ==========================================
    // REQUESTS CREATION & MANAGEMENT
    // ==========================================
    onCategoryChange(cat) {
        const categoryMap = {
            'Электрика': 1,
            'Сантехника': 2,
            'Отопление': 3,
            'Уборка': 4,
            'Территория': 5,
            'Ремонт помещений': 6,
            'Мебель': 6,
            'Оборудование': 7,
            'IT': 7
        };
        const sId = categoryMap[cat] || 8;
        // Pre-select priority recommendation if needed
    }

    async submitCreateRequest(e) {
        e.preventDefault();

        const form = document.getElementById('createRequestForm');
        const formData = new FormData();

        formData.append('category', document.getElementById('reqCategory').value);
        formData.append('building', document.getElementById('reqBuilding').value);
        formData.append('floor', document.getElementById('reqFloor').value);
        formData.append('room', document.getElementById('reqRoom').value);
        formData.append('description', document.getElementById('reqDescription').value);
        formData.append('priority', document.getElementById('reqPriority').value);

        const photoInput = document.getElementById('reqPhotos');
        if (photoInput.files.length > 0) {
            for (let i = 0; i < photoInput.files.length; i++) {
                formData.append('photos[]', photoInput.files[i]);
            }
        }

        try {
            const res = await fetch('api/requests', {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (res.ok && result.success) {
                alert(`Заявка ${result.request.number} успешно создана!`);
                form.reset();
                this.switchView('requests');
            } else {
                alert(result.error || 'Ошибка создания заявки');
            }
        } catch (err) {
            alert('Ошибка отправки формы: ' + err.message);
        }
    }

    async triggerEmergency() {
        if (!confirm('🚨 ВЫ ПОДТВЕРЖДАЕТЕ СОЗДАНИЕ АВАРИЙНОЙ ЗАЯВКИ?\n\nИнформация будет немедленно передана руководителю и дежурному диспетчеру!')) {
            return;
        }

        const room = prompt('Укажите номер кабинета/помещения:', 'Кабинет 101');
        if (!room) return;

        const desc = prompt('Кратко опишите аварийную ситуацию:', 'АВАРИЯ: Прорыв водопровода / короткое замыкание');
        if (!desc) return;

        const formData = new FormData();
        formData.append('category', 'Сантехника');
        formData.append('building', 'Главный корпус');
        formData.append('floor', '1');
        formData.append('room', room);
        formData.append('description', '🚨 АВАРИЯ: ' + desc);
        formData.append('priority', 'Аварийный');

        try {
            const res = await fetch('api/requests', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (res.ok && result.success) {
                alert(`🚨 Аварийный сигнал зафиксирован! Заявка ${result.request.number} отправлена службы немедленного реагирования.`);
                this.switchView('requests');
            }
        } catch (e) {
            alert('Ошибка создания аварийного сигнала');
        }
    }

    async loadRequests(params = {}) {
        let url = 'api/requests?page=1&limit=50';
        const status = params.status || document.getElementById('filterStatus').value;
        const priority = params.priority || document.getElementById('filterPriority').value;

        if (status) url += `&status=${encodeURIComponent(status)}`;
        if (priority) url += `&priority=${encodeURIComponent(priority)}`;

        try {
            const res = await fetch(url);
            if (res.ok) {
                const data = await res.json();
                const container = document.getElementById('requestsListContainer');
                if (data.data.length === 0) {
                    container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Заявки с выбранными фильтрами не найдены</div>';
                    return;
                }
                container.innerHTML = data.data.map(r => this.renderRequestCard(r)).join('');
            }
        } catch (e) {
            console.error('Failed to load requests', e);
        }
    }

    async openRequestDetailModal(id) {
        try {
            const res = await fetch(`api/requests/${id}`);
            if (res.ok) {
                const r = await res.json();

                const photosHtml = (r.photos || []).map(p => `
                    <a href="${p}" target="_blank">
                        <img src="${p}" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color);">
                    </a>
                `).join('');

                const commentsHtml = (r.comments || []).map(c => `
                    <div style="background-color:var(--bg-main); padding:10px 14px; border-radius:8px; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); margin-bottom:4px;">
                            <span><b>${c.user_name}</b> (${c.user_role})</span>
                            <span>${c.created_at}</span>
                        </div>
                        <div>${c.text}</div>
                    </div>
                `).join('');

                const historyHtml = (r.history || []).map(h => `
                    <div style="font-size:12px; color:var(--text-muted); border-left:2px solid var(--primary); padding-left:8px; margin-bottom:6px;">
                        <b>${h.time}</b> — ${h.author}: ${h.text}
                    </div>
                `).join('');

                const modalHtml = `
                    <div id="requestDetailModal" class="modal-overlay active">
                        <div class="modal-container" style="max-width:700px;">
                            <div class="modal-header">
                                <div class="modal-title">${r.number} — ${r.category}</div>
                                <button class="modal-close" onclick="document.getElementById('requestDetailModal').remove()">×</button>
                            </div>

                            <div style="display:flex; justify-content:space-between; margin-bottom:16px;">
                                <div><b>Статус:</b> <span class="badge badge-new">${r.status}</span></div>
                                <div><b>Приоритет:</b> <span>${r.priority}</span></div>
                            </div>

                            <div style="margin-bottom:16px;">
                                <div><b>Место:</b> ${r.location_text}</div>
                                <div><b>Автор:</b> ${r.author_name} (${r.author_department}) — 📞 ${r.author_phone}</div>
                                <div><b>Ответственная служба:</b> ${r.service_name}</div>
                                <div><b>Исполнитель:</b> ${r.executor_name}</div>
                            </div>

                            <div style="background-color:var(--primary-light); padding:12px; border-radius:8px; margin-bottom:16px;">
                                <b>Описание проблемы:</b>
                                <div>${r.description}</div>
                            </div>

                            ${photosHtml ? `<div style="margin-bottom:16px;"><b>Фотографии:</b><div style="display:flex; gap:8px; margin-top:6px;">${photosHtml}</div></div>` : ''}

                            <div style="margin-bottom:16px; display:flex; gap:8px;">
                                <a class="btn btn-outline" href="tel:${r.author_phone}">📞 Позвонить автору</a>
                                <button class="btn btn-secondary" onclick="app.updateRequestStatus(${r.id}, 'Принято')">🟡 Принять</button>
                                <button class="btn btn-secondary" onclick="app.updateRequestStatus(${r.id}, 'В исполнении')">🟠 В работу</button>
                                <button class="btn btn-primary" onclick="app.updateRequestStatus(${r.id}, 'Выполнено')">🟢 Выполнено</button>
                            </div>

                            <hr style="border:none; border-top:1px solid var(--border-color); margin:16px 0;">

                            <h3>Комментарии</h3>
                            <div style="max-height:200px; overflow-y:auto; margin-bottom:12px;">
                                ${commentsHtml || '<div style="color:var(--text-muted); font-size:13px;">Комментариев пока нет</div>'}
                            </div>

                            <div style="display:flex; gap:8px; margin-bottom:20px;">
                                <input type="text" id="modalCommentInput" class="form-input" placeholder="Написать комментарий...">
                                <button class="btn btn-primary" onclick="app.addComment(${r.id})">Отправить</button>
                            </div>

                            <h3>История изменений</h3>
                            <div>${historyHtml}</div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHtml);
            }
        } catch (e) {
            console.error(e);
        }
    }

    async updateRequestStatus(id, newStatus) {
        try {
            const res = await fetch(`api/requests/${id}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ status: newStatus })
            });
            if (res.ok) {
                alert(`Статус заявки изменен на: ${newStatus}`);
                document.getElementById('requestDetailModal')?.remove();
                this.loadRequests();
            }
        } catch (e) {
            alert('Ошибка обновления статуса');
        }
    }

    async addComment(id) {
        const input = document.getElementById('modalCommentInput');
        if (!input || !input.value.trim()) return;

        try {
            const res = await fetch(`api/requests/${id}/comments`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ comment: input.value.trim() })
            });

            if (res.ok) {
                document.getElementById('requestDetailModal')?.remove();
                this.openRequestDetailModal(id);
            }
        } catch (e) {
            alert('Ошибка добавления комментария');
        }
    }

    // ==========================================
    // DIRECTORY & PHONEBOOK
    // ==========================================
    async loadDirectory() {
        try {
            const res = await fetch('api/services');
            if (res.ok) {
                this.services = await res.json();
                this.renderDirectoryGrid(this.services);
            }
        } catch (e) {
            console.error('Failed to load directory', e);
        }
    }

    renderDirectoryGrid(services) {
        const grid = document.getElementById('directoryServicesGrid');
        grid.innerHTML = services.map(s => `
            <div class="stat-card" style="flex-direction:column; align-items:flex-start; text-align:left;">
                <div style="display:flex; align-items:center; gap:10px; width:100%; margin-bottom:10px;">
                    <div style="font-size:28px;">${s.icon || '⚡'}</div>
                    <div>
                        <div style="font-size:16px; font-weight:700;">${s.name}</div>
                        <div style="font-size:12px; color:var(--text-muted);">${s.work_hours || '24/7'}</div>
                    </div>
                </div>
                <p style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">${s.description}</p>

                <div style="display:flex; flex-direction:column; gap:6px; width:100%;">
                    <a class="btn btn-primary" href="tel:${s.phone}" style="width:100%; text-decoration:none;">
                        📞 ПОЗВОНИТЬ (${s.phone})
                    </a>
                    ${s.emergency_phone ? `<a class="btn btn-danger" href="tel:${s.emergency_phone}" style="width:100%; text-decoration:none; padding:6px 12px; font-size:12px;">🚨 АВАРИЙНЫЙ: ${s.emergency_phone}</a>` : ''}
                    ${s.internal_phone ? `<div style="font-size:12px; color:var(--text-muted); text-align:center;">Внутренний номер: <b>${s.internal_phone}</b></div>` : ''}
                </div>
            </div>
        `).join('');
    }

    filterDirectory(term) {
        if (!term) {
            this.renderDirectoryGrid(this.services);
            return;
        }
        const filtered = this.services.filter(s =>
            s.name.toLowerCase().includes(term.toLowerCase()) ||
            s.phone.includes(term) ||
            (s.internal_phone && s.internal_phone.includes(term))
        );
        this.renderDirectoryGrid(filtered);
    }

    openServiceCallModal(id) {
        const s = this.services.find(item => item.id == id);
        if (s) {
            window.location.href = `tel:${s.phone}`;
        }
    }

    // ==========================================
    // CHAT SYSTEM
    // ==========================================
    async loadChats() {
        try {
            const res = await fetch('api/chats');
            if (res.ok) {
                this.chats = await res.json();
                const list = document.getElementById('chatRoomsList');
                list.innerHTML = this.chats.map(c => `
                    <div style="padding:12px; border-bottom:1px solid var(--border-color); cursor:pointer;" onclick="app.selectChat(${c.id}, '${c.title}')">
                        <div style="font-weight:bold; font-size:14px;">${c.title}</div>
                        <div style="font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${c.last_message || 'Сообщений нет'}</div>
                    </div>
                `).join('');

                this.loadChatMessages(this.activeChatId);
            }
        } catch (e) {
            console.error('Failed to load chats', e);
        }
    }

    selectChat(chatId, title) {
        this.activeChatId = chatId;
        document.getElementById('activeChatTitle').innerText = title;
        this.loadChatMessages(chatId);
    }

    async loadChatMessages(chatId, background = false) {
        try {
            const res = await fetch(`api/chats/${chatId}`);
            if (res.ok) {
                const data = await res.json();
                const messagesArea = document.getElementById('chatMessagesArea');

                messagesArea.innerHTML = data.messages.map(m => {
                    const isMine = m.user_id == this.currentUser.id;
                    return `
                        <div class="message-bubble ${isMine ? 'mine' : 'other'}">
                            <div style="font-size:11px; font-weight:bold; margin-bottom:2px;">${m.user_name}</div>
                            <div>${m.text}</div>
                            ${m.photo ? `<img src="${m.photo}" style="max-width:100%; border-radius:6px; margin-top:6px;">` : ''}
                            <div class="message-meta">${m.created_at.split(' ')[1]}</div>
                        </div>
                    `;
                }).join('');

                if (!background) {
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }
            }
        } catch (e) {
            console.error('Failed chat fetch', e);
        }
    }

    async sendChatMessage() {
        const input = document.getElementById('chatInputText');
        const text = input.value.trim();
        if (!text) return;

        try {
            const res = await fetch(`api/chats/${this.activeChatId}/messages`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ text })
            });

            if (res.ok) {
                input.value = '';
                this.loadChatMessages(this.activeChatId);
            }
        } catch (e) {
            alert('Ошибка отправки сообщения');
        }
    }

    // ==========================================
    // EMPLOYEES
    // ==========================================
    async loadEmployees() {
        try {
            const res = await fetch('api/employees');
            if (res.ok) {
                const employees = await res.json();
                const container = document.getElementById('employeesListContainer');
                container.innerHTML = `
                    <div class="stats-grid">
                        ${employees.map(e => `
                            <div class="stat-card" style="flex-direction:column; align-items:flex-start;">
                                <div style="font-weight:bold; font-size:16px; margin-bottom:4px;">${e.name}</div>
                                <div style="font-size:13px; color:var(--text-muted);">${e.position} — ${e.department_name}</div>
                                <div style="font-size:12px; margin-top:8px;">📞 ${e.phone}</div>
                                <div style="display:flex; gap:8px; margin-top:12px; width:100%;">
                                    <a class="btn btn-outline" href="tel:${e.phone}" style="flex:1; text-decoration:none; font-size:12px;">📞 Звонок</a>
                                    <button class="btn btn-primary" style="flex:1; font-size:12px;" onclick="app.startDMChat(${e.id})">💬 Чат</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }
        } catch (e) {
            console.error(e);
        }
    }

    async startDMChat(userId) {
        try {
            const res = await fetch('api/chats', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ user_id: userId })
            });
            if (res.ok) {
                const data = await res.json();
                this.switchView('chats');
                this.selectChat(data.chat.id, data.chat.title);
            }
        } catch (e) {
            alert('Ошибка создания личного чата');
        }
    }

    // ==========================================
    // EXPORT & BACKUP
    // ==========================================
    exportRequestsCSV() {
        window.location.href = 'api/export';
    }

    downloadBackup() {
        window.location.href = 'api/admin/backup';
    }

    // ==========================================
    // AUTH MODALS & UTILS
    // ==========================================
    async submitLogin(e) {
        e.preventDefault();
        const phone = document.getElementById('loginPhone').value;
        const password = document.getElementById('loginPassword').value;

        try {
            const res = await fetch('api/auth/login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ phone, password })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                this.currentUser = data.user;
                this.onLoginSuccess();
            } else {
                alert(data.error || 'Ошибка входа');
            }
        } catch (err) {
            alert('Ошибка соединения с сервером');
        }
    }

    showRegisterModal() {
        document.getElementById('loginModal').classList.remove('active');
        document.getElementById('registerModal').classList.add('active');
    }

    hideRegisterModal() {
        document.getElementById('registerModal').classList.remove('active');
        document.getElementById('loginModal').classList.add('active');
    }

    async submitRegister(e) {
        e.preventDefault();
        const name = document.getElementById('regName').value;
        const phone = document.getElementById('regPhone').value;
        const department_name = document.getElementById('regDept').value;
        const position = document.getElementById('regPos').value;
        const password = document.getElementById('regPassword').value;

        try {
            const res = await fetch('api/auth/register', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ name, phone, department_name, position, password })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                alert('Регистрация прошла успешно! Выполняется вход...');
                document.getElementById('loginPhone').value = phone;
                document.getElementById('loginPassword').value = password;
                this.submitLogin(e);
            } else {
                alert(data.error || 'Ошибка регистрации');
            }
        } catch (err) {
            alert('Ошибка выполнения запроса');
        }
    }

    async submitInstaller(e) {
        e.preventDefault();
        const hospital_name = document.getElementById('setupHospitalName').value;
        const admin_name = document.getElementById('setupAdminName').value;
        const admin_phone = document.getElementById('setupAdminPhone').value;
        const admin_password = document.getElementById('setupAdminPassword').value;

        try {
            const res = await fetch('api/setup', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ hospital_name, admin_name, admin_phone, admin_password })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                alert('Система МедСервис успешно настроена!');
                document.getElementById('installerModal').classList.remove('active');
                this.currentUser = data.user;
                this.onLoginSuccess();
            } else {
                alert(data.error || 'Ошибка настройки системы');
            }
        } catch (err) {
            alert(' Ошибка настройки');
        }
    }

    toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        document.getElementById('themeToggleBtn').innerText = newTheme === 'dark' ? '☀️ Светлая тема' : '🌙 Темная тема';
    }

    nextOnboardingSlide(num) {
        document.querySelectorAll('.onboarding-slide').forEach(s => s.style.display = 'none');
        document.getElementById(`onboardingSlide${num}`).style.display = 'block';
    }

    finishOnboarding() {
        localStorage.setItem('medservice_onboarding_done', 'true');
        document.getElementById('onboardingModal').classList.remove('active');
    }

    async logout() {
        await fetch('api/auth/logout');
        location.reload();
    }
}

// Global App Instance
window.app = new MedServiceApp();
document.addEventListener('DOMContentLoaded', () => window.app.init());
