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
        this.deferredPrompt = null;
    }

    vibrate(pattern = [80, 40, 80]) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    }

    async apiFetch(endpoint, options = {}) {
        let url = endpoint;
        if (!url.startsWith('http') && !url.startsWith('/')) {
            url = `api/index.php/${endpoint.replace(/^api\//, '')}`;
        }

        options.headers = options.headers || {};

        const token = localStorage.getItem('medservice_token');
        if (token) {
            options.headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            const res = await fetch(url, options);
            const contentType = res.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const data = await res.json();
                data._status = res.status;
                data._ok = res.ok;
                return data;
            } else {
                const text = await res.text();
                return { _ok: false, _status: res.status, error: `Ошибка сервера (${res.status})` };
            }
        } catch (e) {
            return { _ok: false, _status: 0, error: 'Ошибка соединения с сервером: ' + e.message };
        }
    }

    async init() {
        this.setupNavigation();
        this.setupPwaInstall();

        const setup = await this.apiFetch('setup');
        if (setup.installed === false) {
            document.getElementById('installerModal').classList.add('active');
            return;
        }

        await this.checkAuth();
    }

    setupPwaInstall() {
        // Listen for Chrome / Android PWA installation event
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) banner.style.display = 'flex';
        });

        // Check if iOS Safari
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isStandalone = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;

        if (isIos && !isStandalone) {
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) banner.style.display = 'flex';
        }
    }

    promptPwaInstall() {
        this.vibrate();
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            this.deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    document.getElementById('pwaInstallBanner').style.display = 'none';
                }
                this.deferredPrompt = null;
            });
        } else {
            alert('📱 Для установки на iPhone/iPad:\n\n1. Нажмите кнопку «Поделиться» (квадрат со стрелкой вверх) в нижней панели Safari.\n2. Выберите «На экран «Домой»».');
        }
    }

    async requestNotificationPermission() {
        this.vibrate();
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                alert('🔔 Push-уведомления успешно включены!');
                document.getElementById('pwaNotificationBanner').style.display = 'none';
                this.subscribePush();
            } else {
                alert('Разрешение на уведомления отклонено в настройках браузера');
            }
        }
    }

    async subscribePush() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await this.apiFetch('push/subscribe', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(sub)
                    });
                }
            } catch (e) {
                console.error('Push subscribe error:', e);
            }
        }
    }

    async checkAuth() {
        const user = await this.apiFetch('user');
        if (user && user.id && !user.error) {
            this.currentUser = user;
            this.onLoginSuccess();
        } else {
            document.getElementById('loginModal').classList.add('active');
        }
    }

    onLoginSuccess() {
        document.getElementById('loginModal').classList.remove('active');
        document.getElementById('registerModal').classList.remove('active');

        document.getElementById('greetingText').innerText = `Добрый день, ${this.currentUser.name.split(' ')[0]}!`;
        document.getElementById('headerUserName').innerText = `${this.currentUser.name} (${this.getRoleTitle(this.currentUser.role)})`;

        // Check onboarding
        if (!localStorage.getItem('medservice_onboarding_done')) {
            document.getElementById('onboardingModal').classList.add('active');
        }

        this.startPolling();
        this.loadDashboardData();
        this.loadServices();
    }

    getRoleTitle(role) {
        const roles = {
            'Admin': 'Администратор',
            'Dispatcher': 'Диспетчер',
            'Service Head': 'Руководитель службы',
            'Executor': 'Исполнитель',
            'Employee': 'Сотрудник'
        };
        return roles[role] || role;
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

        document.querySelectorAll('.sidebar-nav .nav-item, .mobile-nav .mobile-nav-item').forEach(el => {
            if (el.dataset.view === viewName) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        document.querySelectorAll('.app-view').forEach(v => v.style.display = 'none');

        const targetView = document.getElementById(`view-${viewName}`);
        if (targetView) {
            targetView.style.display = 'block';
        }

        if (viewName === 'dashboard') this.loadDashboardData();
        if (viewName === 'requests') this.loadRequests(params);
        if (viewName === 'directory') this.loadDirectory();
        if (viewName === 'chats') this.loadChats();
        if (viewName === 'employees') this.loadEmployees();
        if (viewName === 'services') this.loadServicesManager();
        if (viewName === 'notifications') this.loadNotifications();
        if (viewName === 'analytics') this.loadAnalytics();
        if (viewName === 'settings') this.loadAdminSettings();
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
        const stats = await this.apiFetch('statistics');
        if (stats && !stats.error) {
            document.getElementById('cntNew').innerText = stats.new || 0;
            document.getElementById('cntInProgress').innerText = stats.in_progress || 0;
            document.getElementById('cntCompleted').innerText = stats.completed || 0;
            document.getElementById('cntEmergency').innerText = stats.emergency || 0;

            if (stats.new > 0) {
                document.getElementById('badgeRequests').innerText = stats.new;
                document.getElementById('badgeRequests').style.display = 'inline-block';
            } else {
                document.getElementById('badgeRequests').style.display = 'none';
            }
        }
    }

    async loadFastServices() {
        const services = await this.apiFetch('services');
        if (Array.isArray(services)) {
            this.services = services;
            const container = document.getElementById('fastServicesContainer');
            container.innerHTML = this.services.map(s => `
                <div class="service-card" onclick="app.openServiceCallModal(${s.id})">
                    <div class="service-icon">${s.icon || '⚡'}</div>
                    <div class="service-name">${s.name}</div>
                    <div class="service-sub">${s.internal_phone ? 'внутр. ' + s.internal_phone : s.phone}</div>
                </div>
            `).join('');
        }
    }

    async loadRecentRequests() {
        const data = await this.apiFetch('requests?limit=5');
        const requests = (data && data.data) ? data.data : [];
        const container = document.getElementById('recentRequestsContainer');

        if (requests.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-muted);">Заявок пока нет</div>';
            return;
        }

        container.innerHTML = requests.map(r => this.renderRequestCard(r)).join('');
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
                    <span class="meta-pill">📍 ${r.location_text}</span>
                    <span class="meta-pill">🏢 ${r.service_name}</span>
                    <span class="meta-pill">👤 ${r.author_name}</span>
                    <span class="meta-pill">🕒 ${r.created_at}</span>
                </div>
            </div>
        `;
    }

    // ==========================================
    // REQUESTS CREATION & MANAGEMENT
    // ==========================================
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

        const result = await this.apiFetch('requests', {
            method: 'POST',
            body: formData
        });

        if (result.success) {
            alert(`Заявка ${result.request.number} успешно создана!`);
            form.reset();
            this.switchView('requests');
        } else {
            alert(result.error || 'Ошибка создания заявки');
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

        const result = await this.apiFetch('requests', {
            method: 'POST',
            body: formData
        });

        if (result.success) {
            alert(`🚨 Аварийный сигнал зафиксирован! Заявка ${result.request.number} отправлена службы немедленного реагирования.`);
            this.switchView('requests');
        }
    }

    async loadRequests(params = {}) {
        let path = 'requests?page=1&limit=50';
        const status = params.status || document.getElementById('filterStatus').value;
        const priority = params.priority || document.getElementById('filterPriority').value;

        if (status) path += `&status=${encodeURIComponent(status)}`;
        if (priority) path += `&priority=${encodeURIComponent(priority)}`;

        const data = await this.apiFetch(path);
        const container = document.getElementById('requestsListContainer');
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Заявки с выбранными фильтрами не найдены</div>';
            return;
        }
        container.innerHTML = data.data.map(r => this.renderRequestCard(r)).join('');
    }

    async assignRequestExecutor(reqId, execId) {
        const res = await this.apiFetch(`requests/${reqId}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ executor_id: parseInt(execId) || 0 })
        });
        if (res && res.success) {
            alert('Исполнитель заявки успешно назначен');
            document.getElementById('requestDetailModal')?.remove();
            this.openRequestDetailModal(reqId);
        } else {
            alert(res.error || 'Ошибка назначения исполнителя');
        }
    }

    async openRequestDetailModal(id) {
        const r = await this.apiFetch(`requests/${id}`);
        if (r && r.id) {
            const employees = await this.apiFetch(`employees?service_id=${r.service_id || 0}`);
            const serviceEmps = Array.isArray(employees) ? employees : [];
            const canAssign = this.currentUser && (this.currentUser.role === 'Admin' || this.currentUser.role === 'Dispatcher' || this.currentUser.role === 'Service Head' || (this.currentUser.permissions && this.currentUser.permissions.assign_executors));

            const executorOptions = serviceEmps.map(e => `
                <option value="${e.id}" ${e.id == r.executor_id ? 'selected' : ''}>${e.name} (${e.position})</option>
            `).join('');
            const photosHtml = (r.photos || []).map(p => `
                <a href="${p}" target="_blank">
                    <img src="${p}" style="width:70px; height:70px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color);">
                </a>
            `).join('');

            const commentsHtml = (r.comments || []).map(c => `
                <div style="background-color:var(--bg-main); padding:10px 12px; border-radius:8px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); margin-bottom:4px; flex-wrap:wrap;">
                        <span><b>${c.user_name}</b> (${c.user_role})</span>
                        <span>${c.created_at}</span>
                    </div>
                    <div style="font-size:13px;">${c.text}</div>
                </div>
            `).join('');

            const historyHtml = (r.history || []).map(h => `
                <div style="font-size:12px; color:var(--text-muted); border-left:2px solid var(--primary); padding-left:8px; margin-bottom:6px;">
                    <b>${h.time}</b> — ${h.author}: ${h.text}
                </div>
            `).join('');

            const isAdmin = this.currentUser && this.currentUser.role === 'Admin';

            const modalHtml = `
                <div id="requestDetailModal" class="modal-overlay active">
                    <div class="modal-container">
                        <div class="modal-header">
                            <div class="modal-title">${r.number} — ${r.category}</div>
                            <button class="modal-close" onclick="document.getElementById('requestDetailModal').remove()">×</button>
                        </div>

                        <div style="display:flex; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
                            <div><b>Статус:</b> <span class="badge badge-new">${r.status}</span></div>
                            <div><b>Приоритет:</b> <span>${r.priority}</span></div>
                        </div>

                        <div style="margin-bottom:16px; font-size:13px; display:flex; flex-direction:column; gap:4px;">
                            <div><b>Место:</b> ${r.location_text}</div>
                            <div><b>Автор:</b> ${r.author_name} (${r.author_department}) — 📞 ${r.author_phone}</div>
                            <div><b>Ответственная служба:</b> ${r.service_name}</div>
                            <div><b>Исполнитель:</b> ${r.executor_name} ${canAssign ? `
                                <select class="form-select" style="display:inline-block; width:auto; padding:4px 8px; font-size:12px; margin-left:6px;" onchange="app.assignRequestExecutor(${r.id}, this.value)">
                                    <option value="0">-- Назначить исполнителя --</option>
                                    ${executorOptions}
                                </select>
                            ` : ''}</div>
                            ${r.last_status_changed_by ? `<div style="color:var(--primary); font-size:12px;"><b>Последний изменил статус:</b> ${r.last_status_changed_by} (${r.last_status_changed_at || ''})</div>` : ''}
                        </div>

                        <div style="background-color:var(--primary-light); padding:12px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                            <b>Описание проблемы:</b>
                            <div style="margin-top:4px;">${r.description}</div>
                        </div>

                        ${photosHtml ? `<div style="margin-bottom:16px;"><b>Фотографии:</b><div style="display:flex; gap:8px; margin-top:6px; flex-wrap:wrap;">${photosHtml}</div></div>` : ''}

                        <div style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap;">
                            <a class="btn btn-outline btn-sm" href="tel:${r.author_phone}">📞 Позвонить автору</a>
                            <button class="btn btn-secondary btn-sm" onclick="app.updateRequestStatus(${r.id}, 'Принято')">🟡 Принять</button>
                            <button class="btn btn-secondary btn-sm" onclick="app.updateRequestStatus(${r.id}, 'В исполнении')">🟠 В работу</button>
                            <button class="btn btn-primary btn-sm" onclick="app.updateRequestStatus(${r.id}, 'Выполнено')">🟢 Выполнено</button>
                            ${isAdmin ? `<button class="btn btn-danger btn-sm" onclick="app.deleteRequest(${r.id})">🗑 Удалить</button>` : ''}
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
    }

    async updateRequestStatus(id, newStatus) {
        const res = await this.apiFetch(`requests/${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ status: newStatus })
        });
        if (res.success) {
            alert(`Статус заявки изменен на: ${newStatus}`);
            document.getElementById('requestDetailModal')?.remove();
            this.loadRequests();
        } else {
            alert('Ошибка обновления статуса');
        }
    }

    async deleteRequest(id) {
        if (!confirm('Вы уверены, что хотите полностью удалить эту заявку?')) return;
        const res = await this.apiFetch(`requests/${id}`, { method: 'DELETE' });
        if (res.success) {
            alert('Заявка успешно удалена');
            document.getElementById('requestDetailModal')?.remove();
            this.loadRequests();
        } else {
            alert(res.error || 'Ошибка удаления');
        }
    }

    async addComment(id) {
        const input = document.getElementById('modalCommentInput');
        if (!input || !input.value.trim()) return;

        const res = await this.apiFetch(`requests/${id}/comments`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ comment: input.value.trim() })
        });

        if (res.success) {
            document.getElementById('requestDetailModal')?.remove();
            this.openRequestDetailModal(id);
        } else {
            alert('Ошибка добавления комментария');
        }
    }

    // ==========================================
    // DIRECTORY & PHONEBOOK
    // ==========================================
    async loadDirectory() {
        const services = await this.apiFetch('services');
        if (Array.isArray(services)) {
            this.services = services;
            this.renderDirectoryGrid(this.services);
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
        const chats = await this.apiFetch('chats');
        if (Array.isArray(chats)) {
            this.chats = chats;
            const selector = document.getElementById('chatRoomSelector');
            if (selector) {
                selector.innerHTML = this.chats.map(c => `
                    <option value="${c.id}" ${c.id == this.activeChatId ? 'selected' : ''}>${c.title}</option>
                `).join('');
            }
            this.loadChatMessages(this.activeChatId);
        }
    }

    selectChat(chatId, title) {
        this.activeChatId = chatId;
        const titleEl = document.querySelector('#activeChatTitle span');
        if (titleEl) titleEl.innerText = title;
        this.loadChatMessages(chatId);
    }

    async loadChatMessages(chatId, background = false) {
        const data = await this.apiFetch(`chats/${chatId}`);
        if (data && data.messages) {
            const messagesArea = document.getElementById('chatMessagesArea');

            messagesArea.innerHTML = data.messages.map(m => {
                const isMine = m.user_id == this.currentUser.id;
                return `
                    <div class="message-bubble ${isMine ? 'mine' : 'other'}">
                        <div class="message-author">${this.escapeHtml(m.user_name)}</div>
                        <div>${this.escapeHtml(m.text)}</div>
                        ${m.photo ? `<img src="${m.photo}" style="max-width:100%; border-radius:6px; margin-top:6px;">` : ''}
                        <div class="message-meta">${m.created_at.split(' ')[1] || m.created_at}</div>
                    </div>
                `;
            }).join('');

            if (!background) {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }
    }

    async sendChatMessage() {
        const input = document.getElementById('chatInputText');
        const text = input.value.trim();
        if (!text) return;

        const res = await this.apiFetch(`chats/${this.activeChatId}/messages`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ text })
        });

        if (res.success) {
            input.value = '';
            this.loadChatMessages(this.activeChatId);
        } else {
            alert('Ошибка отправки сообщения');
        }
    }

    // ==========================================
    // EMPLOYEES MANAGEMENT
    // ==========================================
    async loadEmployees() {
        const employees = await this.apiFetch('employees');
        if (Array.isArray(employees)) {
            this.cachedEmployees = employees;
            const isAdmin = this.currentUser && this.currentUser.role === 'Admin';
            const container = document.getElementById('employeesListContainer');

            container.innerHTML = `
                ${isAdmin ? `
                    <div style="margin-bottom:16px;">
                        <button class="btn btn-primary btn-sm" onclick="app.openAddEmployeeModal()">+ Добавить сотрудника</button>
                    </div>
                ` : ''}
                <div class="stats-grid">
                    ${employees.map(e => `
                        <div class="stat-card" style="flex-direction:column; align-items:flex-start;">
                            <div style="font-weight:bold; font-size:16px; margin-bottom:4px;">
                                ${e.name} ${e.is_blocked ? '<span class="badge badge-emergency">Заблокирован</span>' : ''}
                            </div>
                            <div style="font-size:13px; color:var(--text-muted);">${e.position} — ${e.department_name}</div>
                            <div style="font-size:12px; margin-top:4px;"><b>Роль:</b> ${this.getRoleTitle(e.role)}</div>
                            <div style="font-size:12px; margin-top:4px;">📞 ${e.phone}</div>

                            <div style="display:flex; gap:6px; margin-top:12px; width:100%; flex-wrap:wrap;">
                                <a class="btn btn-outline btn-sm" href="tel:${e.phone}" style="flex:1; text-decoration:none; text-align:center;">📞 Звонок</a>
                                <button class="btn btn-secondary btn-sm" style="flex:1;" onclick="app.startDMChat(${e.id})">💬 Чат</button>
                                ${isAdmin ? `
                                    <button class="btn btn-outline btn-sm" onclick="app.openEditEmployeeModal(${e.id})">✏️ Редактировать</button>
                                    <button class="btn btn-outline btn-sm" onclick="app.openPermissionsModal(${e.id})">🔑 Права</button>
                                    <button class="btn btn-danger btn-sm" onclick="app.deleteEmployee(${e.id})">🗑</button>
                                ` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
    }

    openAddEmployeeModal() {
        const serviceOpts = (this.services || []).map(s => `<option value="${s.id}">${s.icon || '🛠'} ${s.name}</option>`).join('');

        const modalHtml = `
            <div id="addEmployeeModal" class="modal-overlay active">
                <div class="modal-container" style="max-width:500px;">
                    <div class="modal-header">
                        <div class="modal-title">➕ Добавить сотрудника</div>
                        <button class="modal-close" onclick="document.getElementById('addEmployeeModal').remove()">×</button>
                    </div>
                    <form onsubmit="app.submitAddEmployee(event)">
                        <div class="form-group">
                            <label class="form-label">ФИО</label>
                            <input type="text" id="addEmpName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Телефон (Логин)</label>
                            <input type="tel" id="addEmpPhone" class="form-input" placeholder="+375291234567" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Роль в системе</label>
                            <select id="addEmpRole" class="form-select" required>
                                <option value="Employee">Сотрудник</option>
                                <option value="Executor">Исполнитель</option>
                                <option value="Service Head">Руководитель службы</option>
                                <option value="Dispatcher">Диспетчер</option>
                                <option value="Admin">Администратор</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Привязка к службе (для исполняющего персонала)</label>
                            <select id="addEmpServiceId" class="form-select">
                                <option value="0">Без привязки к службе</option>
                                ${serviceOpts}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Подразделение</label>
                            <input type="text" id="addEmpDept" class="form-input" value="Терапевтическое отделение" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Должность</label>
                            <input type="text" id="addEmpPos" class="form-input" value="Врач" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Пароль (6 цифр)</label>
                            <input type="password" id="addEmpPassword" class="form-input" value="123456" maxlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Сохранить сотрудника</button>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    openEditEmployeeModal(empId) {
        if (!this.cachedEmployees) return;
        const emp = this.cachedEmployees.find(e => e.id == empId);
        if (!emp) return;

        const serviceOpts = (this.services || []).map(s => `
            <option value="${s.id}" ${(emp.service_id ?? 0) == s.id ? 'selected' : ''}>${s.icon || '🛠'} ${s.name}</option>
        `).join('');

        const modalHtml = `
            <div id="editEmployeeModal" class="modal-overlay active">
                <div class="modal-container" style="max-width:500px;">
                    <div class="modal-header">
                        <div class="modal-title">✏️ Редактирование сотрудника</div>
                        <button class="modal-close" onclick="document.getElementById('editEmployeeModal').remove()">×</button>
                    </div>
                    <form onsubmit="app.submitEditEmployee(event, ${emp.id})">
                        <div class="form-group">
                            <label class="form-label">ФИО</label>
                            <input type="text" id="editEmpName" class="form-input" value="${this.escapeHtml(emp.name)}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Телефон (Логин)</label>
                            <input type="tel" id="editEmpPhone" class="form-input" value="${this.escapeHtml(emp.phone)}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Роль в системе</label>
                            <select id="editEmpRole" class="form-select" required>
                                <option value="Employee" ${emp.role === 'Employee' ? 'selected' : ''}>Сотрудник</option>
                                <option value="Executor" ${emp.role === 'Executor' ? 'selected' : ''}>Исполнитель</option>
                                <option value="Service Head" ${emp.role === 'Service Head' ? 'selected' : ''}>Руководитель службы</option>
                                <option value="Dispatcher" ${emp.role === 'Dispatcher' ? 'selected' : ''}>Диспетчер</option>
                                <option value="Admin" ${emp.role === 'Admin' ? 'selected' : ''}>Администратор</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Привязка к службе (для получающих заявки)</label>
                            <select id="editEmpServiceId" class="form-select">
                                <option value="0">Без привязки к службе</option>
                                ${serviceOpts}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Подразделение</label>
                            <input type="text" id="editEmpDept" class="form-input" value="${this.escapeHtml(emp.department_name || '')}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Должность</label>
                            <input type="text" id="editEmpPos" class="form-input" value="${this.escapeHtml(emp.position || '')}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Новый пароль (оставьте пустым если не меняете)</label>
                            <input type="password" id="editEmpPassword" class="form-input" placeholder="Новый пароль (6 цифр)">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" id="editEmpBlocked" ${emp.is_blocked ? 'checked' : ''} style="width:16px; height:16px;">
                            <label for="editEmpBlocked" style="font-size:13px; cursor:pointer;">Заблокировать доступ пользователя</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    async submitEditEmployee(e, empId) {
        e.preventDefault();
        const payload = {
            name: document.getElementById('editEmpName').value,
            phone: document.getElementById('editEmpPhone').value,
            role: document.getElementById('editEmpRole').value,
            service_id: parseInt(document.getElementById('editEmpServiceId').value) || 0,
            department_name: document.getElementById('editEmpDept').value,
            position: document.getElementById('editEmpPos').value,
            is_blocked: document.getElementById('editEmpBlocked').checked
        };

        const pass = document.getElementById('editEmpPassword').value;
        if (pass && pass.trim() !== '') {
            payload.password = pass.trim();
        }

        const res = await this.apiFetch(`employees/${empId}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            alert('Данные сотрудника успешно обновлены!');
            document.getElementById('editEmployeeModal')?.remove();
            this.loadEmployees();
        } else {
            alert(res.error || 'Ошибка при сохранении данных сотрудника');
        }
    }

    async submitAddEmployee(e) {
        e.preventDefault();
        const data = {
            name: document.getElementById('addEmpName').value,
            phone: document.getElementById('addEmpPhone').value,
            role: document.getElementById('addEmpRole').value,
            service_id: parseInt(document.getElementById('addEmpServiceId').value) || 0,
            department_name: document.getElementById('addEmpDept').value,
            position: document.getElementById('addEmpPos').value,
            password: document.getElementById('addEmpPassword').value
        };

        const res = await this.apiFetch('employees', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        if (res.success) {
            alert('Сотрудник успешно добавлен!');
            document.getElementById('addEmployeeModal')?.remove();
            this.loadEmployees();
        } else {
            alert(res.error || 'Ошибка добавления');
        }
    }

    openProfileModal() {
        if (!this.currentUser) return;
        this.openEditEmployeeModal(this.currentUser.id);
    }

    onCategoryChange(category) {
        const categoryMap = {
            'Электрика': 1,
            'Сантехника': 2,
            'Отопление': 3,
            'Канализация': 2,
            'Вентиляция': 3,
            'Уборка': 4,
            'Территория': 5,
            'Ремонт помещений': 6,
            'Мебель': 6,
            'Оборудование': 7,
            'IT': 7
        };
        const servId = categoryMap[category] || 0;
        const serviceSelect = document.getElementById('reqServiceSelect');
        if (serviceSelect && servId > 0) {
            serviceSelect.value = servId;
        }
    }

    async loadNotifications() {
        const container = document.getElementById('notificationsList');
        if (!container) return;
        container.innerHTML = '<p style="padding:16px; color:var(--text-muted)">Загрузка уведомлений...</p>';

        const notifs = await this.apiFetch('notifications');
        if (Array.isArray(notifs) && notifs.length > 0) {
            container.innerHTML = notifs.map(n => `
                <div style="padding:12px; border-bottom:1px solid var(--border-color); background-color:var(--bg-card); margin-bottom:8px; border-radius:var(--radius-sm);">
                    <div style="font-weight:bold; color:var(--primary);">${this.escapeHtml(n.title)}</div>
                    <div style="font-size:13px; margin:4px 0;">${this.escapeHtml(n.message)}</div>
                    <div style="font-size:11px; color:var(--text-muted);">${n.created_at}</div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="padding:16px; color:var(--text-muted)">У вас нет новых уведомлений</p>';
        }
    }

    async loadAnalytics() {
        const container = document.getElementById('analyticsContainer');
        if (!container) return;
        container.innerHTML = 'Загрузка аналитики...';

        const stats = await this.apiFetch('statistics');
        if (stats && !stats.error) {
            container.innerHTML = `
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-top:16px;">
                    <div style="background-color:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); text-align:center;">
                        <div style="font-size:32px; font-weight:bold; color:var(--primary);">${stats.total || 0}</div>
                        <div style="color:var(--text-muted); font-size:13px; margin-top:4px;">Всего заявок</div>
                    </div>
                    <div style="background-color:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); text-align:center;">
                        <div style="font-size:32px; font-weight:bold; color:#f59e0b;">${stats.in_progress || 0}</div>
                        <div style="color:var(--text-muted); font-size:13px; margin-top:4px;">В работе</div>
                    </div>
                    <div style="background-color:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); text-align:center;">
                        <div style="font-size:32px; font-weight:bold; color:#10b981;">${stats.completed || 0}</div>
                        <div style="color:var(--text-muted); font-size:13px; margin-top:4px;">Выполнено</div>
                    </div>
                    <div style="background-color:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); text-align:center;">
                        <div style="font-size:32px; font-weight:bold; color:#ef4444;">${stats.emergency || 0}</div>
                        <div style="color:var(--text-muted); font-size:13px; margin-top:4px;">Аварийных</div>
                    </div>
                </div>
            `;
        }
    }

    openPermissionsModal(empId) {
        if (!empId) empId = this.currentUser?.id;
        if (!this.cachedEmployees) return;
        const emp = this.cachedEmployees.find(e => e.id == empId);
        if (!emp) return;

        document.getElementById('permUserId').value = emp.id;
        document.getElementById('permUserName').innerText = `${emp.name} (${emp.position})`;

        const perms = emp.permissions || {};
        const keys = ['create_requests', 'view_all_requests', 'assign_executors', 'change_status', 'manage_directories', 'manage_users', 'view_analytics', 'chat_access', 'export_backup'];

        keys.forEach(k => {
            const el = document.getElementById(`perm_${k}`);
            if (el) el.checked = perms[k] !== undefined ? !!perms[k] : (emp.role === 'Admin' || emp.role === 'Dispatcher' || k === 'create_requests' || k === 'chat_access');
        });

        document.getElementById('permissionsModal').classList.add('active');
    }

    hidePermissionsModal() {
        document.getElementById('permissionsModal').classList.remove('active');
    }

    async saveUserPermissions(e) {
        if (e) e.preventDefault();
        const empId = document.getElementById('permUserId').value;
        const keys = ['create_requests', 'view_all_requests', 'assign_executors', 'change_status', 'manage_directories', 'manage_users', 'view_analytics', 'chat_access', 'export_backup'];
        const perms = {};
        keys.forEach(k => {
            const el = document.getElementById(`perm_${k}`);
            if (el) perms[k] = el.checked;
        });

        const res = await this.apiFetch(`employees/${empId}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ permissions: perms })
        });

        if (res && res.success) {
            alert('Права сотрудника успешно обновлены');
            this.hidePermissionsModal();
            this.loadEmployees();
        } else {
            alert(res.error || 'Ошибка сохранения прав');
        }
    }

    async deleteEmployee(id) {
        if (!confirm('Заблокировать / удалить этого сотрудника?')) return;
        const res = await this.apiFetch(`employees/${id}`, { method: 'DELETE' });
        if (res.success) {
            alert('Сотрудник заблокирован');
            this.loadEmployees();
        }
    }

    // ==========================================
    // SERVICES MANAGEMENT
    // ==========================================
    async loadServicesManager() {
        const services = await this.apiFetch('services');
        if (Array.isArray(services)) {
            const isAdmin = this.currentUser && this.currentUser.role === 'Admin';
            const container = document.getElementById('servicesListContainer');

            container.innerHTML = `
                ${isAdmin ? `
                    <div style="margin-bottom:16px;">
                        <button class="btn btn-primary btn-sm" onclick="app.openAddServiceModal()">+ Создать службу</button>
                    </div>
                ` : ''}
                <div class="stats-grid">
                    ${services.map(s => `
                        <div class="stat-card" style="flex-direction:column; align-items:flex-start;">
                            <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                                <div style="font-size:24px;">${s.icon || '⚡'}</div>
                                ${isAdmin ? `<button class="btn btn-outline btn-sm" onclick="app.deleteService(${s.id})">🗑 Удалить</button>` : ''}
                            </div>
                            <div style="font-size:18px; font-weight:700; margin:8px 0;">${s.name}</div>
                            <div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;">${s.description}</div>
                            <div style="font-size:12px;">📞 Основной: <b>${s.phone}</b></div>
                            ${s.emergency_phone ? `<div style="font-size:12px; color:#ef4444;">🚨 Аварийный: <b>${s.emergency_phone}</b></div>` : ''}
                            <div style="font-size:12px; margin-top:4px; color:var(--text-muted);">SLA: реакция ${s.sla_reaction_minutes || 15} мин, выполнение ${s.sla_completion_hours || 2} ч</div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
    }

    openAddServiceModal() {
        const modalHtml = `
            <div id="addServiceModal" class="modal-overlay active">
                <div class="modal-container" style="max-width:500px;">
                    <div class="modal-header">
                        <div class="modal-title">🏢 Создание службы</div>
                        <button class="modal-close" onclick="document.getElementById('addServiceModal').remove()">×</button>
                    </div>
                    <form onsubmit="app.submitAddService(event)">
                        <div class="form-group">
                            <label class="form-label">Название службы</label>
                            <input type="text" id="addServName" class="form-input" placeholder="Дежурная служба" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Иконка (Эмодзи)</label>
                            <input type="text" id="addServIcon" class="form-input" value="🛠" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Телефон</label>
                            <input type="tel" id="addServPhone" class="form-input" placeholder="+375290000000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Аварийный телефон</label>
                            <input type="tel" id="addServEmergency" class="form-input" placeholder="+375290000001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Описание</label>
                            <textarea id="addServDesc" class="form-textarea" rows="2" placeholder="Обслуживание задвижек и узлов"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Сохранить службу</button>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    async submitAddService(e) {
        e.preventDefault();
        const data = {
            name: document.getElementById('addServName').value,
            icon: document.getElementById('addServIcon').value,
            phone: document.getElementById('addServPhone').value,
            emergency_phone: document.getElementById('addServEmergency').value,
            description: document.getElementById('addServDesc').value,
            sla_reaction_minutes: 15,
            sla_completion_hours: 2
        };

        const res = await this.apiFetch('services', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        if (res.success) {
            alert('Служба успешно создана!');
            document.getElementById('addServiceModal')?.remove();
            this.loadServicesManager();
        } else {
            alert(res.error || 'Ошибка создания');
        }
    }

    async deleteService(id) {
        if (!confirm('Удалить эту службу?')) return;
        const res = await this.apiFetch(`services/${id}`, { method: 'DELETE' });
        if (res.success) {
            alert('Служба удалена');
            this.loadServicesManager();
        }
    }

    async startDMChat(userId) {
        const data = await this.apiFetch('chats', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ user_id: userId })
        });

        if (data.success) {
            this.switchView('chats');
            this.selectChat(data.chat.id, data.chat.title);
        } else {
            alert('Ошибка создания личного чата');
        }
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ==========================================
    // ABOUT APP & LEGAL DISCLAIMER
    // ==========================================
    openAboutModal() {
        const modal = document.getElementById('aboutModal');
        if (modal) modal.classList.add('active');
    }

    hideAboutModal() {
        const modal = document.getElementById('aboutModal');
        if (modal) modal.classList.remove('active');
    }

    // ==========================================
    // SETTINGS & AUDIT LOGS
    // ==========================================
    async loadAdminSettings() {
        const data = await this.apiFetch('settings');
        if (data) {
            if (document.getElementById('settingHospitalName')) document.getElementById('settingHospitalName').value = data.hospital_name || '';
            if (document.getElementById('settingHospitalPhone')) document.getElementById('settingHospitalPhone').value = data.hospital_phone || '';
            if (document.getElementById('settingEmergencyContact')) document.getElementById('settingEmergencyContact').value = data.emergency_contact || '';
            if (document.getElementById('settingHospitalAddress')) document.getElementById('settingHospitalAddress').value = data.hospital_address || '';
            if (document.getElementById('settingSlaEmergencyMins')) document.getElementById('settingSlaEmergencyMins').value = data.sla_emergency_mins || 10;
            if (document.getElementById('settingSlaNormalHours')) document.getElementById('settingSlaNormalHours').value = data.sla_normal_hours || 24;
            if (document.getElementById('settingMaxUploadMb')) document.getElementById('settingMaxUploadMb').value = data.max_upload_mb || 10;
            if (document.getElementById('settingFontFamily') && data.font_family) document.getElementById('settingFontFamily').value = data.font_family;
            if (document.getElementById('settingFontSizeBase') && data.font_size_base) document.getElementById('settingFontSizeBase').value = data.font_size_base;
            if (document.getElementById('settingPwaThemeColor') && data.pwa_theme_color) document.getElementById('settingPwaThemeColor').value = data.pwa_theme_color;
            if (document.getElementById('settingBorderRadius') && data.border_radius) document.getElementById('settingBorderRadius').value = data.border_radius;

            this.applyCssCustomProperties(data);
        }
    }

    updateUiStylesPreview() {
        const font = document.getElementById('settingFontFamily')?.value;
        const fontSize = document.getElementById('settingFontSizeBase')?.value;
        const color = document.getElementById('settingPwaThemeColor')?.value;
        const radius = document.getElementById('settingBorderRadius')?.value;

        this.applyCssCustomProperties({
            font_family: font,
            font_size_base: fontSize,
            pwa_theme_color: color,
            border_radius: radius
        });
    }

    applyCssCustomProperties(opts) {
        if (opts.font_family) document.documentElement.style.setProperty('--font-family', opts.font_family);
        if (opts.font_size_base) document.documentElement.style.setProperty('--font-size-base', opts.font_size_base);
        if (opts.pwa_theme_color) document.documentElement.style.setProperty('--primary', opts.pwa_theme_color);
        if (opts.border_radius) {
            document.documentElement.style.setProperty('--radius-md', opts.border_radius);
            document.documentElement.style.setProperty('--radius-sm', (parseInt(opts.border_radius) * 0.6) + 'px');
        }
    }

    async saveAdminSettings(e) {
        if (e) e.preventDefault();
        const payload = {
            hospital_name: document.getElementById('settingHospitalName').value,
            hospital_phone: document.getElementById('settingHospitalPhone').value,
            emergency_contact: document.getElementById('settingEmergencyContact').value,
            hospital_address: document.getElementById('settingHospitalAddress').value,
            sla_emergency_mins: parseInt(document.getElementById('settingSlaEmergencyMins').value) || 10,
            sla_normal_hours: parseInt(document.getElementById('settingSlaNormalHours').value) || 24,
            max_upload_mb: parseInt(document.getElementById('settingMaxUploadMb').value) || 10,
            font_family: document.getElementById('settingFontFamily')?.value || '',
            font_size_base: document.getElementById('settingFontSizeBase')?.value || '14px',
            pwa_theme_color: document.getElementById('settingPwaThemeColor')?.value || '#0284c7',
            border_radius: document.getElementById('settingBorderRadius')?.value || '16px'
        };

        const res = await this.apiFetch('admin/settings', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        if (res && res.success) {
            this.applyCssCustomProperties(payload);
            alert('Настройки системы и графического интерфейса успешно сохранены');
        } else {
            alert(res.error || 'Ошибка при сохранении настроек');
        }
    }

    switchSettingsSubtab(tab) {
        document.querySelectorAll('.settings-subtab-btn').forEach(btn => btn.classList.remove('active', 'btn-primary'));
        document.querySelectorAll('.settings-subtab-btn').forEach(btn => btn.classList.add('btn-outline'));

        document.querySelectorAll('.settings-subtab-content').forEach(sec => sec.style.display = 'none');

        if (tab === 'general') {
            document.getElementById('btnSettingsSubtabGeneral')?.classList.add('active', 'btn-primary');
            document.getElementById('btnSettingsSubtabGeneral')?.classList.remove('btn-outline');
            document.getElementById('settingsSubtabGeneralSection').style.display = 'block';
        } else if (tab === 'users') {
            document.getElementById('btnSettingsSubtabUsers')?.classList.add('active', 'btn-primary');
            document.getElementById('btnSettingsSubtabUsers')?.classList.remove('btn-outline');
            document.getElementById('settingsSubtabUsersSection').style.display = 'block';
            this.loadSettingsUsersList();
        } else if (tab === 'backup') {
            document.getElementById('btnSettingsSubtabBackup')?.classList.add('active', 'btn-primary');
            document.getElementById('btnSettingsSubtabBackup')?.classList.remove('btn-outline');
            document.getElementById('settingsSubtabBackupSection').style.display = 'block';
        }
    }

    async loadSettingsUsersList() {
        const tbody = document.getElementById('settingsUsersTableBody');
        if (!tbody) return;

        const search = document.getElementById('settingsUserSearch')?.value || '';
        const roleFilter = document.getElementById('settingsUserRoleFilter')?.value || '';

        tbody.innerHTML = '<tr><td colspan="6" style="padding:16px; text-align:center;">Загрузка списка пользователей...</td></tr>';

        const employees = await this.apiFetch('employees');
        if (!Array.isArray(employees)) {
            tbody.innerHTML = '<tr><td colspan="6" style="padding:16px; text-align:center; color:#ef4444;">Ошибка загрузки пользователей</td></tr>';
            return;
        }

        const filtered = employees.filter(emp => {
            if (roleFilter && emp.role !== roleFilter) return false;
            if (search) {
                const s = search.toLowerCase();
                const nameMatch = (emp.name || '').toLowerCase().includes(s);
                const phoneMatch = (emp.phone || '').includes(s);
                if (!nameMatch && !phoneMatch) return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="padding:16px; text-align:center; color:var(--text-muted);">Пользователи не найдены</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(u => `
            <tr style="border-bottom:1px solid var(--border-color);">
                <td style="padding:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:18px;">${u.avatar || '👤'}</span>
                        <div>
                            <strong>${this.escapeHtml(u.name)}</strong>
                            <div style="font-size:11px; color:var(--text-muted);">${this.escapeHtml(u.position || 'Должность не указана')}</div>
                        </div>
                    </div>
                </td>
                <td style="padding:10px;">${this.escapeHtml(u.phone)}</td>
                <td style="padding:10px;">${this.escapeHtml(u.department_name || '—')}</td>
                <td style="padding:10px;">
                    <select class="form-select" style="font-size:12px; padding:4px 8px;" onchange="app.updateUserRoleQuick(${u.id}, this.value)">
                        <option value="Employee" ${u.role === 'Employee' || u.role === 'employee' ? 'selected' : ''}>👤 Сотрудник</option>
                        <option value="Executor" ${u.role === 'Executor' || u.role === 'executor' ? 'selected' : ''}>👷 Исполнитель</option>
                        <option value="Service Head" ${u.role === 'Service Head' || u.role === 'service_head' ? 'selected' : ''}>👔 Руководитель службы</option>
                        <option value="Dispatcher" ${u.role === 'Dispatcher' || u.role === 'dispatcher' ? 'selected' : ''}>🎧 Диспетчер</option>
                        <option value="Admin" ${u.role === 'Admin' || u.role === 'admin' ? 'selected' : ''}>👑 Администратор</option>
                    </select>
                </td>
                <td style="padding:10px;">
                    ${u.is_blocked ? '<span style="color:#ef4444; font-weight:bold;">🚫 Заблокирован</span>' : '<span style="color:#10b981; font-weight:bold;">🟢 Активен</span>'}
                </td>
                <td style="padding:10px; text-align:right;">
                    <div style="display:flex; gap:6px; justify-content:flex-end;">
                        <button class="btn btn-outline btn-sm" onclick="app.openPermissionsModal(${u.id})">🔑 Права</button>
                        <button class="btn btn-secondary btn-sm" onclick="app.openEditEmployeeModal(${u.id})">✏️ Изменить</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async updateUserRoleQuick(userId, newRole) {
        const res = await this.apiFetch(`employees/${userId}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ role: newRole })
        });
        if (res && res.success) {
            alert('Роль пользователя успешно обновлена');
            this.loadSettingsUsersList();
        } else {
            alert(res.error || 'Ошибка при изменении роли');
        }
    }

    async loadAuditLogs() {
        const container = document.getElementById('auditLogsContainer');
        if (!container) return;
        container.style.display = 'block';
        container.innerHTML = 'Загрузка журнала аудита...';

        const logs = await this.apiFetch('admin/audit');
        if (Array.isArray(logs)) {
            if (logs.length === 0) {
                container.innerHTML = '<p style="color:var(--text-muted)">Записи аудита отсутствуют</p>';
                return;
            }
            container.innerHTML = logs.map(l => `
                <div style="padding:6px; border-bottom:1px solid var(--border-color)">
                    <strong style="color:var(--primary)">${l.timestamp || ''}</strong> [${l.user_name || 'Система'}]: ${l.action || ''} (${l.details || ''})
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="color:#ef4444">Ошибка загрузки аудита</p>';
        }
    }

    // ==========================================
    // EXPORT & BACKUP
    // ==========================================
    exportRequestsCSV() {
        window.location.href = 'api/index.php/export';
    }

    downloadBackup() {
        window.location.href = 'api/index.php/admin/backup';
    }

    // ==========================================
    // AUTH MODALS & UTILS
    // ==========================================
    async submitLogin(e) {
        e.preventDefault();
        const phone = document.getElementById('loginPhone').value;
        const password = document.getElementById('loginPassword').value;
        const rememberMe = document.getElementById('loginRememberMe')?.checked;

        const data = await this.apiFetch('auth/login', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ phone, password })
        });

        if (data && data.success && data.user) {
            this.currentUser = data.user;
            if (rememberMe && data.user.token) {
                localStorage.setItem('medservice_token', data.user.token);
            }
            this.onLoginSuccess();
        } else {
            alert(data.error || 'Неверный номер телефона или пароль');
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

        const data = await this.apiFetch('auth/register', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name, phone, department_name, position, password })
        });

        if (data && data.success) {
            alert('Регистрация прошла успешно! Выполняется вход...');
            document.getElementById('loginPhone').value = phone;
            document.getElementById('loginPassword').value = password;
            this.submitLogin(e);
        } else {
            alert(data.error || 'Ошибка регистрации');
        }
    }

    async submitInstaller(e) {
        e.preventDefault();
        const hospital_name = document.getElementById('setupHospitalName').value;
        const admin_name = document.getElementById('setupAdminName').value;
        const admin_phone = document.getElementById('setupAdminPhone').value;
        const admin_password = document.getElementById('setupAdminPassword').value;

        const data = await this.apiFetch('setup', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ hospital_name, admin_name, admin_phone, admin_password })
        });

        if (data && data.success) {
            alert('Система МедСервис успешно настроена!');
            document.getElementById('installerModal').classList.remove('active');
            this.currentUser = data.user;
            if (data.user.token) {
                localStorage.setItem('medservice_token', data.user.token);
            }
            this.onLoginSuccess();
        } else {
            alert(data.error || 'Ошибка настройки системы');
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
        localStorage.removeItem('medservice_token');
        await this.apiFetch('auth/logout');
        location.reload();
    }
}

// Global App Instance
window.app = new MedServiceApp();
document.addEventListener('DOMContentLoaded', () => window.app.init());
