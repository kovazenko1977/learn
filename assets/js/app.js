const App = {
    user: null,
    currentView: 'dashboard',
    clients: [],
    stats: null,
    pollInterval: null,
    currentListId: null,

    async init() {
        this.bindEvents();
        this.registerPWA();
        await this.checkAuth();
    },

    registerPWA() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }

        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const promptEl = document.getElementById('install-prompt');
            if (promptEl) promptEl.classList.remove('hidden');
        });

        const installBtn = document.getElementById('install-button');
        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                }
                document.getElementById('install-prompt').classList.add('hidden');
            });
        }
    },

    bindEvents() {
        document.querySelectorAll('[data-view]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.setView(el.dataset.view);
            });
        });

        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.login();
        });

        document.getElementById('register-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.register();
        });

        document.getElementById('logout-btn').addEventListener('click', () => this.logout());
    },

    showRegistration() {
        document.getElementById('login-card').classList.add('hidden');
        document.getElementById('register-card').classList.remove('hidden');
    },

    escapeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    async apiFetch(url, options = {}) {
        const headers = options.headers || {};
        if (this.user && this.user.csrf_token) {
            headers['X-CSRF-TOKEN'] = this.user.csrf_token;
        }
        options.headers = headers;
        try {
            const res = await fetch(url, options);
            if (!res.ok) {
                const errorData = await res.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP Error ${res.status}`);
            }
            return res;
        } catch (e) {
            this.showToast(e.message, 'error');
            throw e;
        }
    },

    showToast(message, type = 'info') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed; bottom:80px; left:50%; transform:translateX(-50%); z-index:9999; display:flex; flex-direction:column; gap:10px; pointer-events:none;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `animate-fade`;
        toast.style.cssText = `padding:12px 24px; border-radius:30px; color:white; font-size:14px; font-weight:600; box-shadow:0 4px 15px rgba(0,0,0,0.2); pointer-events:auto; background:${type === 'error' ? '#e74c3c' : '#7360f2'}`;
        toast.textContent = message;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    async checkAuth() {
        try {
            const res = await fetch('api/auth.php?action=check');
            const data = await res.json();
            if (data.success) {
                this.user = data.user;
                this.showApp();
            } else {
                this.showLogin();
            }
        } catch (e) {
            this.showLogin();
        }
    },

    async login() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        const res = await this.apiFetch('api/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        const data = await res.json();

        if (data.success) {
            this.user = data.user;
            this.showApp();
        } else {
            alert(data.error || 'Ошибка входа');
        }
    },

    async logout() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        try {
            await this.apiFetch('api/auth.php?action=logout');
        } catch (e) {}
        this.user = null;
        this.showLogin();
    },

    showLogin() {
        document.getElementById('auth-view').classList.remove('hidden');
        document.getElementById('login-card').classList.remove('hidden');
        document.getElementById('register-card').classList.add('hidden');
        document.getElementById('app-view').classList.add('hidden');
        document.getElementById('app-view').style.opacity = '0';
    },

    async register() {
        const username = document.getElementById('reg-username').value;
        const password = document.getElementById('reg-password').value;
        const company_name = document.getElementById('reg-company').value;
        const tax_id = document.getElementById('reg-taxid').value;
        const email = document.getElementById('reg-email').value;

        try {
            const res = await fetch('api/auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify({ username, password, company_name, tax_id, email })
            });
            const data = await res.json();
            if (data.success) {
                alert('Заявка отправлена. Ожидайте подтверждения администратором.');
                this.showLogin();
            } else {
                alert(data.error);
            }
        } catch (e) {
            alert('Ошибка при регистрации');
        }
    },

    async showApp() {
        document.getElementById('auth-view').classList.add('hidden');
        document.getElementById('app-view').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('app-view').style.opacity = '1';
        }, 50);

        const isAdmin = ['superadmin', 'admin_clients', 'admin_content', 'admin_communications'].includes(this.user.role);
        document.querySelectorAll('.admin-only').forEach(el => {
            el.classList.toggle('hidden', !isAdmin);
        });

        if (isAdmin) {
            await this.fetchClients();
        }
        await this.fetchStats();

        this.setView(this.currentView);

        if (!this.pollInterval) {
            this.pollInterval = setInterval(() => this.fetchStats().catch(() => {}), 10000); // 10s polling
        }
    },

    async fetchClients() {
        const res = await this.apiFetch('api/users.php?action=list');
        const data = await res.json();
        this.clients = data.users;
    },

    async fetchStats() {
        const res = await this.apiFetch('api/stats.php?action=summary');
        const data = await res.json();
        this.stats = data.stats;
        this.updateBadges();

        if (this.currentView === 'messages') {
            this.refreshChatData();
        }
    },

    async refreshChatData() {
        const res = await this.apiFetch('api/messages.php?action=list');
        const data = await res.json();
        const isAdmin = ['superadmin', 'admin_communications'].includes(this.user.role);

        if (isAdmin) {
            // Update unread badges in the sidebar
            const conversations = this.processConversations(data.messages);
            Object.keys(conversations).forEach(pid => {
                const badge = document.querySelector(`#chat-item-${pid} .chat-item-badge`);
                const unread = conversations[pid].unread;
                if (badge) {
                    if (unread > 0) badge.textContent = unread;
                    else badge.remove();
                } else if (unread > 0) {
                    const previewRow = document.querySelector(`#chat-item-${pid} .chat-item-preview`).parentElement;
                    const newBadge = document.createElement('span');
                    newBadge.className = 'chat-item-badge';
                    newBadge.textContent = unread;
                    previewRow.appendChild(newBadge);
                }

                const preview = document.querySelector(`#chat-item-${pid} .chat-item-preview`);
                if (preview) {
                    const lastMsg = conversations[pid].messages[conversations[pid].messages.length - 1];
                    preview.textContent = lastMsg.body;
                }
            });

            if (this.lastOpenedChatId) {
                const chatMessages = data.messages.filter(m => m.from === this.lastOpenedChatId || m.to === this.lastOpenedChatId);
                const history = document.getElementById('chat-history-admin');
                if (history) {
                    const newHtml = this.renderMessageBubbles(chatMessages, this.lastOpenedChatId);
                    if (history.innerHTML !== newHtml) {
                        history.innerHTML = newHtml;
                        history.scrollTop = history.scrollHeight;
                    }
                }
            }
        } else {
            const history = document.getElementById('chat-history-client');
            if (history) {
                const newHtml = this.renderMessageBubbles(data.messages);
                if (history.innerHTML !== newHtml) {
                    history.innerHTML = newHtml;
                    history.scrollTop = history.scrollHeight;
                }
            }
        }
    },

    processConversations(messages) {
        const conversations = {};
        messages.forEach(m => {
            // Find the client in this message (either sender or recipient)
            const sender = this.clients.find(c => c.id === m.from && c.role === 'client');
            const recipient = this.clients.find(c => c.id === m.to && c.role === 'client');

            const client = sender || recipient;
            if (!client) return; // Skip messages between admins or broadcasts to 'all'

            const clientId = client.id;
            if (!conversations[clientId]) {
                conversations[clientId] = {
                    messages: [],
                    unread: 0,
                    partner: client
                };
            }
            conversations[clientId].messages.push(m);
            if (!m.read_by || !m.read_by.includes(this.user.id)) {
                if (m.from === clientId) conversations[clientId].unread++;
            }
        });
        return conversations;
    },

    updateBadges() {
        this.setBadge('documents', this.stats.unread_docs);
        this.setBadge('messages', this.stats.unread_messages);
    },

    setBadge(view, count) {
        const el = document.querySelector(`.nav-item[data-view="${view}"]`);
        if (!el) return;
        let badge = el.querySelector('.badge-nav');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge-nav animate-scale';
                el.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) {
            badge.remove();
        }
    },

    async setView(view) {
        if (this._settingView) return;
        this._settingView = true;

        this.currentView = view;
        document.querySelectorAll('.nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.view === view);
        });

        const container = document.getElementById('view-container');
        container.style.opacity = '0';
        container.style.transform = 'translateY(10px)';

        try {
            // Pre-fetch check for auth to ensure no stale sessions block view loading
            if (view !== 'about') {
                const checkRes = await fetch('api/auth.php?action=check');
                const checkData = await checkRes.json();
                if (!checkData.success) {
                    this.showLogin();
                    this._settingView = false;
                    return;
                }
            }

            container.innerHTML = '<div class="card animate-fade" style="text-align:center; padding:50px">Загрузка...</div>';
            switch (view) {
            case 'dashboard':
                await this.renderDashboard(container);
                break;
            case 'documents':
                await this.renderDocuments(container);
                await this.markAllRead('documents');
                break;
            case 'messages':
                await this.renderMessages(container);
                await this.markAllRead('messages');
                break;
            case 'users':
                await this.renderUsers(container);
                break;
            case 'logs':
                await this.renderLogs(container);
                break;
            case 'maintenance':
                await this.renderMaintenance(container);
                break;
            case 'pricelist':
                await this.renderPriceList(container);
                break;
            case 'orders':
                await this.renderOrders(container);
                break;
            case 'profile':
                await this.renderProfile(container);
                break;
            case 'about':
                this.renderAbout(container);
                break;
            }
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        } catch (e) {
            console.error('View loading error:', e);
            container.innerHTML = `<div class="card"><h2>Ошибка загрузки</h2><p>${this.escapeHTML(e.message)}</p><button class="btn btn-primary" onclick="App.setView('${view}')">Повторить</button></div>`;
            container.style.opacity = '1';
        } finally {
            this._settingView = false;
        }
    },

    async markAllRead(type, clientId = null) {
        if (type === 'documents') {
            const res = await this.apiFetch('api/documents.php?action=list');
            const data = await res.json();
            for (const doc of data.documents) {
                if (!doc.read_by || !doc.read_by.includes(this.user.id)) {
                    await this.apiFetch(`api/documents.php?action=mark_read&id=${doc.id}`);
                }
            }
        } else if (type === 'messages') {
            const url = clientId ? `api/messages.php?action=mark_all_read&client_id=${clientId}` : 'api/messages.php?action=mark_all_read';
            await this.apiFetch(url);
        }
        await this.fetchStats();
    },

    async renderDashboard(container) {
        const isAdmin = ['superadmin', 'admin_clients', 'admin_content', 'admin_communications'].includes(this.user.role);

        let html = `
            <div class="view-header">
                <h1 class="view-title">Добро пожаловать, ${this.escapeHTML(this.user.username)}</h1>
            </div>
            <div class="card">
                <p>Вы вошли как: <strong>${this.escapeHTML(this.user.role)}</strong></p>
                <p>Используйте меню для навигации по документам и сообщениям.</p>
            </div>
        `;

        if (isAdmin && this.stats) {
            html += `
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:30px;">
                    <div class="card" style="text-align:center; padding:15px">
                        <small style="color:var(--text-muted)">Клиенты</small>
                        <h3 style="font-size:24px">${this.stats.total_clients}</h3>
                        <small style="color:green">Активных: ${this.stats.active_clients}</small>
                    </div>
                    <div class="card" style="text-align:center; padding:15px">
                        <small style="color:var(--text-muted)">Документы</small>
                        <h3 style="font-size:24px">${this.stats.total_documents}</h3>
                        <small style="color:var(--text-muted)">${this.stats.storage_used}</small>
                    </div>
                    <div class="card" style="text-align:center; padding:15px">
                        <small style="color:var(--text-muted)">Сообщения</small>
                        <h3 style="font-size:24px">${this.stats.total_messages}</h3>
                    </div>
                    <div class="card" style="text-align:center; padding:15px">
                        <small style="color:var(--text-muted)">Заказы</small>
                        <h3 style="font-size:24px" id="stats-total-orders">-</h3>
                    </div>
                </div>
            `;
        }

        html += `
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                <div class="card" style="text-align:center; cursor:pointer" onclick="App.setView('documents')">
                    <h3 style="margin-bottom:10px">Документы</h3>
                    <p style="font-size:32px; color:var(--primary)">📄</p>
                    ${this.stats.unread_docs > 0 ? `<span class="badge-nav" style="position:static; display:inline-block">${this.stats.unread_docs}</span>` : ''}
                </div>
                <div class="card" style="text-align:center; cursor:pointer" onclick="App.setView('messages')">
                    <h3 style="margin-bottom:10px">Сообщения</h3>
                    <p style="font-size:32px; color:var(--primary)">✉️</p>
                    ${this.stats.unread_messages > 0 ? `<span class="badge-nav" style="position:static; display:inline-block">${this.stats.unread_messages}</span>` : ''}
                </div>
                <div class="card" style="text-align:center; cursor:pointer" onclick="App.setView('pricelist')">
                    <h3 style="margin-bottom:10px">Прайс / Заказ</h3>
                    <p style="font-size:32px; color:var(--primary)">🛒</p>
                </div>
            </div>
        `;

        html += `
            <div class="card" style="margin-top: 30px;">
                <h2 style="margin-bottom: 20px;">${isAdmin ? 'Последние заказы' : 'Мои заказы'}</h2>
                <div id="dashboard-orders">Загрузка заказов...</div>
            </div>
        `;

        container.innerHTML = html;

        this.apiFetch('api/orders.php?action=list').then(res => res.json()).then(data => {
            const orders = data.orders.reverse().slice(0, 5);
            const el = document.getElementById('dashboard-orders');
            const statsEl = document.getElementById('stats-total-orders');
            if (statsEl) statsEl.textContent = data.orders.length;

            if (orders.length === 0) {
                el.innerHTML = '<p style="text-align:center; color:var(--text-muted)">Заказов пока нет</p>';
            } else {
                el.innerHTML = `
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                ${isAdmin ? '<th>Клиент</th>' : ''}
                                <th>Позиций</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orders.map(o => `
                                <tr>
                                    <td>#${o.id.substring(0,6)}</td>
                                    ${isAdmin ? `<td>${this.escapeHTML(o.company_name || o.username)}</td>` : ''}
                                    <td>${o.total_items}</td>
                                    <td>${o.created_at}</td>
                                    <td><span class="badge ${o.status === 'Новый' ? 'badge-warning' : 'badge-success'}">${o.status}</span></td>
                                    <td><button class="btn btn-outline btn-sm" onclick="App.showOrderDetails('${o.id}')">Детали</button></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        });
    },

    async renderDocuments(container) {
        const res = await this.apiFetch('api/documents.php?action=list');
        const data = await res.json();

        const isAdmin = ['superadmin', 'admin_content'].includes(this.user.role);
        let html = `
            <div class="view-header">
                <h1 class="view-title">Документы</h1>
                <div style="display:flex; gap:10px;">
                    <input type="text" placeholder="Поиск по названию..." id="doc-search" style="padding:6px 12px; width:200px;">
                    ${isAdmin ? '<button class="btn btn-primary btn-sm" onclick="App.showUploadModal()">Загрузить</button>' : ''}
                </div>
            </div>
            <div class="card">
                <table class="data-table" id="doc-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Дата</th>
                            ${isAdmin ? '<th>Скачиваний</th>' : ''}
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.documents.map(doc => {
                            const isUnread = !doc.read_by || !doc.read_by.includes(this.user.id);
                            return `
                            <tr style="${doc.archived ? 'opacity:0.6; font-style:italic' : ''}; ${isUnread ? 'font-weight:bold; background:rgba(0,51,102,0.02)' : ''}">
                                <td data-label="Название">
                                    ${this.escapeHTML(doc.name)}
                                    ${doc.archived ? ' <small>(архив)</small>' : ''}
                                    ${isUnread ? ' <span class="badge-success badge" style="font-size:9px">NEW</span>' : ''}
                                </td>
                                <td data-label="Тип">${this.escapeHTML(doc.meta.type) || '-'}</td>
                                <td data-label="Дата">${this.escapeHTML(doc.uploaded_at)}</td>
                                ${isAdmin ? `<td data-label="Скачиваний">${doc.downloads || 0}</td>` : ''}
                                <td data-label="Действия">
                                    <div style="display:flex; gap:5px; flex-wrap:wrap">
                                        <a href="api/documents.php?action=download&id=${doc.id}" class="btn btn-outline btn-sm">Скачать</a>
                                        ${isAdmin ? `
                                            <button onclick="App.toggleArchiveDocument('${doc.id}', ${!!doc.archived})" class="btn btn-outline btn-sm">${doc.archived ? 'Восстановить' : 'В архив'}</button>
                                            <button onclick="App.deleteDocument('${doc.id}')" class="btn btn-outline btn-sm" style="color:red">Удалить</button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `}).join('')}
                        ${data.documents.length === 0 ? '<tr><td colspan="4" style="text-align:center">Нет доступных документов</td></tr>' : ''}
                    </tbody>
                </table>
            </div>
        `;
        container.innerHTML = html;
        document.getElementById('doc-search').oninput = (e) => this.filterTable('doc-table', e.target.value);
    },

    async renderMessages(container) {
        const res = await this.apiFetch('api/messages.php?action=list');
        const data = await res.json();
        const isAdmin = ['superadmin', 'admin_communications'].includes(this.user.role);

        if (isAdmin) {
            await this.renderAdminChat(container, data.messages);
        } else {
            await this.renderClientChat(container, data.messages);
        }
    },

    async renderAdminChat(container, messages) {
        const conversations = this.processConversations(messages);

        const sortedPartners = Object.keys(conversations).sort((a, b) => {
            const lastA = conversations[a].messages[conversations[a].messages.length - 1].created_at;
            const lastB = conversations[b].messages[conversations[b].messages.length - 1].created_at;
            return lastB.localeCompare(lastA);
        });

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Мессенджер</h1>
                <button class="btn btn-primary btn-sm" onclick="App.showSendMessageModal()">Рассылка</button>
            </div>
            <div class="chat-layout" style="display:grid; grid-template-columns: 320px 1fr;" id="chat-layout-admin">
                <div class="chat-list" id="admin-chat-list">
                    ${sortedPartners.map(pid => {
                        const conv = conversations[pid];
                        const lastMsg = conv.messages[conv.messages.length - 1];
                        return `
                        <div class="chat-list-item" onclick="App.openAdminChatWindow('${pid}')" id="chat-item-${pid}">
                            <div class="chat-avatar">${(conv.partner.company_name || conv.partner.username).substring(0,1).toUpperCase()}</div>
                            <div class="chat-item-content">
                                <div class="chat-item-header">
                                    <span class="chat-item-name">${this.escapeHTML(conv.partner.company_name || conv.partner.username)}</span>
                                    <span class="chat-item-time">${lastMsg.created_at.split(' ')[1].substring(0,5)}</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center">
                                    <div class="chat-item-preview">${this.escapeHTML(lastMsg.body)}</div>
                                    ${conv.unread > 0 ? `<span class="chat-item-badge">${conv.unread}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        `;
                    }).join('')}
                    ${sortedPartners.length === 0 ? '<p style="padding:20px; color:var(--text-muted); text-align:center">Нет активных диалогов</p>' : ''}
                </div>
                <div id="admin-chat-window" class="chat-window mobile-hidden" style="display:flex; flex-direction:column; background: white;">
                    <div style="flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-muted); flex-direction:column; gap:15px;">
                        <span style="font-size:48px">💬</span>
                        <p>Выберите чат для начала общения</p>
                    </div>
                </div>
            </div>
        `;

        if (this.lastOpenedChatId) {
            this.openAdminChatWindow(this.lastOpenedChatId);
        }
    },

    async openAdminChatWindow(partnerId) {
        this.lastOpenedChatId = partnerId;
        document.querySelectorAll('.chat-list-item').forEach(el => el.classList.remove('active'));
        const activeItem = document.getElementById(`chat-item-${partnerId}`);
        if (activeItem) activeItem.classList.add('active');

        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            document.getElementById('admin-chat-list').classList.add('mobile-hidden');
            document.getElementById('admin-chat-window').classList.remove('mobile-hidden');
        }

        const res = await this.apiFetch('api/messages.php?action=list');
        const data = await res.json();
        const chatMessages = data.messages.filter(m => m.from === partnerId || m.to === partnerId);
        const partner = this.clients.find(c => c.id === partnerId) || { username: partnerId };

        const windowEl = document.getElementById('admin-chat-window');
        windowEl.innerHTML = `
            <div style="padding:15px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:white">
                <div style="display:flex; align-items:center; gap:12px;">
                    ${isMobile ? `<button class="btn btn-outline btn-sm" style="border:none; padding:5px" onclick="App.closeAdminChatWindow()">⬅️</button>` : ''}
                    <div class="chat-avatar" style="width:40px; height:40px; font-size:14px;">${(partner.company_name || partner.username).substring(0,1).toUpperCase()}</div>
                    <div>
                        <div style="font-weight:600">${this.escapeHTML(partner.company_name || partner.username)}</div>
                        <div style="font-size:12px; color:var(--primary)">в сети</div>
                    </div>
                </div>
                <button class="btn btn-outline btn-sm" style="color:#e74c3c; border-color:#e74c3c" onclick="App.deleteChat('${partnerId}')" title="Удалить чат">🗑️</button>
            </div>
            <div class="chat-history" id="chat-history-admin">
                ${this.renderMessageBubbles(chatMessages, partnerId)}
            </div>
            <div class="chat-input-area">
                <input type="text" id="admin-chat-input" placeholder="Напишите сообщение..." style="flex:1; border-radius:20px; padding:10px 20px;">
                <button class="btn btn-primary btn-sm" style="border-radius:50%; width:40px; height:40px; padding:0" onclick="App.sendAdminChatMessage('${partnerId}')">➜</button>
            </div>
        `;

        const history = document.getElementById('chat-history-admin');
        history.scrollTop = history.scrollHeight;

        document.getElementById('admin-chat-input').onkeypress = (e) => {
            if (e.key === 'Enter') this.sendAdminChatMessage(partnerId);
        };

        // Mark as read
        await this.markAllRead('messages', partnerId);
    },

    closeAdminChatWindow() {
        this.lastOpenedChatId = null;
        document.getElementById('admin-chat-list').classList.remove('mobile-hidden');
        document.getElementById('admin-chat-window').classList.add('mobile-hidden');
        document.querySelectorAll('.chat-list-item').forEach(el => el.classList.remove('active'));
    },

    async renderClientChat(container, messages) {
        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Чат с администратором</h1>
            </div>
            <div class="chat-layout">
                <div class="chat-history" id="chat-history-client">
                    ${this.renderMessageBubbles(messages)}
                </div>
                <div class="chat-input-area">
                    <input type="text" id="client-chat-input" placeholder="Ваше сообщение..." style="flex:1; border-radius:20px; padding:10px 20px;">
                    <button class="btn btn-primary btn-sm" style="border-radius:50%; width:40px; height:40px; padding:0" onclick="App.sendClientChatMessage()">➜</button>
                </div>
            </div>
        `;
        const history = document.getElementById('chat-history-client');
        history.scrollTop = history.scrollHeight;

        document.getElementById('client-chat-input').onkeypress = (e) => {
            if (e.key === 'Enter') this.sendClientChatMessage();
        };

        for (const m of messages) {
            if (m.from !== this.user.id && (!m.read_by || !m.read_by.includes(this.user.id))) {
                await this.markAllRead('messages');
                break;
            }
        }
    },

    renderMessageBubbles(messages, partnerId = null) {
        if (messages.length === 0) return '<p style="text-align:center; color:var(--text-muted); margin:auto;">Сообщений пока нет.</p>';

        let html = '';
        let lastDate = '';

        messages.forEach(msg => {
            const date = msg.created_at.split(' ')[0];
            if (date !== lastDate) {
                html += `<div style="text-align:center; margin:15px 0;"><span style="background:#e6e6f2; color:var(--text-muted); padding:2px 10px; border-radius:10px; font-size:11px;">${date}</span></div>`;
                lastDate = date;
            }

            const isMine = msg.from === this.user.id;
            const isRead = isMine && msg.read_by_partner;

            html += `
                <div class="chat-bubble ${isMine ? 'mine' : 'theirs'}">
                    <div style="white-space: pre-wrap;">${this.escapeHTML(msg.body)}</div>
                    ${msg.attachments && msg.attachments.length > 0 ? `
                        <div class="chat-attachments">
                            ${msg.attachments.map(att => `
                                <a href="api/messages.php?action=download_attachment&id=${att.id}" class="chat-att-item">📎 ${this.escapeHTML(att.name)}</a>
                            `).join('')}
                        </div>
                    ` : ''}
                    <div class="chat-info">
                        <span>${msg.created_at.split(' ')[1].substring(0,5)}</span>
                        ${isMine ? `<span style="font-size:10px; color:${isRead ? 'var(--primary)' : 'inherit'}">✓✓</span>` : ''}
                    </div>
                </div>
            `;
        });
        return html;
    },

    async sendAdminChatMessage(partnerId) {
        const input = document.getElementById('admin-chat-input');
        const body = input.value.trim();
        if (!body) return;

        const formData = new FormData();
        formData.append('to', partnerId);
        formData.append('subject', 'Chat Message');
        formData.append('body', body);

        input.value = '';
        await this.apiFetch('api/messages.php?action=send', { method: 'POST', body: formData });
        await this.openAdminChatWindow(partnerId);
    },

    async deleteChat(clientId) {
        if (!confirm('Вы уверены, что хотите полностью удалить переписку с этим клиентом? Это действие необратимо.')) return;

        try {
            await this.apiFetch(`api/messages.php?action=delete_chat&client_id=${clientId}`);
            this.lastOpenedChatId = null;
            if (window.innerWidth <= 768) {
                this.closeAdminChatWindow();
            }
            await this.setView('messages');
            this.showToast('Чат успешно удален');
        } catch (e) {
            this.showToast('Ошибка при удалении чата', 'error');
        }
    },

    async sendClientChatMessage() {
        const input = document.getElementById('client-chat-input');
        const body = input.value.trim();
        if (!body) return;

        const formData = new FormData();
        formData.append('to', 'admin');
        formData.append('subject', 'Client Message');
        formData.append('body', body);

        input.value = '';
        await this.apiFetch('api/messages.php?action=send', { method: 'POST', body: formData });
        await this.refreshChatData();
    },

    async renderUsers(container) {
        const res = await this.apiFetch('api/users.php?action=list');
        const data = await res.json();

        const pendingUsers = data.users.filter(u => u.status === 'Ожидает');
        const otherUsers = data.users.filter(u => u.status !== 'Ожидает');

        let html = `
            <div class="view-header">
                <h1 class="view-title">Управление клиентами</h1>
                <div style="display:flex; gap:10px;">
                    <input type="text" placeholder="Поиск клиентов..." id="user-search" style="padding:6px 12px; width:200px;">
                    <button class="btn btn-primary btn-sm" onclick="App.showCreateUserModal()">Новый клиент</button>
                </div>
            </div>
        `;

        if (pendingUsers.length > 0) {
            html += `
                <div class="card" style="border: 2px solid var(--primary); margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; color: var(--primary)">Заявки на регистрацию (${pendingUsers.length})</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Клиент / Компания</th>
                                <th>Email / Телефон</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pendingUsers.map(u => `
                                <tr>
                                    <td>
                                        <strong>${this.escapeHTML(u.username)}</strong><br>
                                        <small>${this.escapeHTML(u.company_name || '-')}</small><br>
                                        <small>ИНН: ${this.escapeHTML(u.tax_id || '-')}</small>
                                    </td>
                                    <td>
                                        ${this.escapeHTML(u.email || '-')}<br>
                                        ${this.escapeHTML(u.phone || '-')}
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:10px;">
                                            <button class="btn btn-primary btn-sm" onclick="App.approveUser('${u.id}')">Одобрить</button>
                                            <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="App.rejectUser('${u.id}')">Отклонить</button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        html += `
            <div class="card">
                <table class="data-table" id="user-table">
                    <thead>
                        <tr>
                            <th>Клиент / Компания</th>
                            <th>Контакты</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${otherUsers.map(u => `
                            <tr>
                                <td data-label="Клиент">
                                    <strong>${this.escapeHTML(u.username)}</strong><br>
                                    <small>${this.escapeHTML(u.company_name || '-')}</small><br>
                                    <small style="color:var(--text-muted)">ИНН: ${this.escapeHTML(u.tax_id || '-')}</small>
                                </td>
                                <td data-label="Контакты">
                                    <small>${this.escapeHTML(u.contact_person || '-')}</small><br>
                                    <small>${this.escapeHTML(u.phone || '-')}</small>
                                </td>
                                <td data-label="Статус">
                                    <span class="badge ${u.status === 'Активен' ? 'badge-success' : (u.status === 'Отклонен' ? 'badge-error' : 'badge-warning')}">
                                        ${this.escapeHTML(u.status)}
                                    </span>
                                </td>
                                <td data-label="Действия">
                                    <div style="display:flex; gap:5px; flex-wrap:wrap">
                                        <button class="btn btn-outline btn-sm" onclick="App.showEditUserModal('${u.id}')">✏️</button>
                                        ${u.status === 'Активен' ?
                                            `<button class="btn btn-outline btn-sm" onclick="App.blockUser('${u.id}')" title="Блокировать">🚫</button>` :
                                            `<button class="btn btn-outline btn-sm" onclick="App.unblockUser('${u.id}')" title="Разблокировать">✅</button>`
                                        }
                                        <button class="btn btn-outline btn-sm" onclick="App.resetPassword('${u.id}')" title="Сброс пароля">🔑</button>
                                        <button class="btn btn-outline btn-sm" style="color:red" onclick="App.deleteUser('${u.id}')">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        container.innerHTML = html;
        document.getElementById('user-search').oninput = (e) => this.filterTable('user-table', e.target.value);
    },

    async renderLogs(container) {
        const res = await this.apiFetch('api/logs.php?action=list');
        const data = await res.json();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Журнал аудита</h1>
                <div style="display:flex; gap:10px; flex-wrap:wrap">
                    <input type="text" placeholder="Поиск в логах..." id="log-search" style="padding:6px 12px; width:200px;">
                    <a href="api/logs.php?action=export" class="btn btn-outline btn-sm">Экспорт CSV</a>
                    ${this.user.role === 'superadmin' ? `<button class="btn btn-outline btn-sm" style="color:red" onclick="App.clearLogs()">Очистить все</button>` : ''}
                </div>
            </div>
            <div class="card">
                <table class="data-table" id="log-table">
                    <thead>
                        <tr>
                            <th>Событие</th>
                            <th>Объект</th>
                            <th>Дата</th>
                            <th>Устройство</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.logs.map(log => `
                            <tr>
                                <td data-label="Событие">${this.escapeHTML(log.type)}</td>
                                <td data-label="Объект">${this.escapeHTML(log.object)}</td>
                                <td data-label="Дата">${this.escapeHTML(log.date)}</td>
                                <td data-label="Устройство">${this.escapeHTML(log.device || '-')}</td>
                                <td data-label="IP">${this.escapeHTML(log.ip)}</td>
                            </tr>
                        `).reverse().slice(0, 100).join('')}
                    </tbody>
                </table>
            </div>
        `;
        document.getElementById('log-search').oninput = (e) => this.filterTable('log-table', e.target.value);
    },

    filterTable(tableId, query) {
        const q = query.toLowerCase();
        const rows = document.querySelectorAll(`#${tableId} tbody tr`);
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    },

    async renderMaintenance(container) {
        const res = await this.apiFetch('api/settings.php?action=get');
        const data = await res.json();
        const settings = data.settings;

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Оптимизация и Сервис</h1>
            </div>

            <div class="card" style="margin-bottom:30px">
                <h3>Настройки уведомлений (Email)</h3>
                <form id="notification-settings-form" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-top:15px;">
                    <div class="form-group">
                        <label>Отправка уведомлений</label>
                        <select id="smtp_enabled">
                            <option value="true" ${settings.smtp_enabled ? 'selected' : ''}>Включена</option>
                            <option value="false" ${!settings.smtp_enabled ? 'selected' : ''}>Выключена</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email отправителя</label>
                        <input type="email" id="smtp_from" value="${this.escapeHTML(settings.smtp_from || '')}" placeholder="noreply@example.com">
                    </div>
                    <div style="grid-column: 1 / -1">
                        <button type="submit" class="btn btn-primary">Сохранить настройки Email</button>
                    </div>
                </form>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                <div class="card">
                    <h3>Очистка логов</h3>
                    <p style="color:var(--text-muted); font-size:13px; margin:10px 0;">Удаление всех записей из журнала аудита.</p>
                    <button class="btn btn-outline" style="color:red; width:100%" onclick="App.clearLogs()">Очистить все логи</button>
                </div>
                <div class="card">
                    <h3>Очистка временных файлов</h3>
                    <p style="color:var(--text-muted); font-size:13px; margin:10px 0;">Удаление бесхозных файлов в папке uploads (не связанных с документами или сообщениями).</p>
                    <button class="btn btn-primary" style="width:100%" onclick="App.cleanupOrphans()">Запустить очистку</button>
                </div>
                <div class="card">
                    <h3>Сброс лимитов IP</h3>
                    <p style="color:var(--text-muted); font-size:13px; margin:10px 0;">Разблокировка всех IP-адресов, попавших в rate-limit.</p>
                    <button class="btn btn-outline" style="width:100%" onclick="App.clearRateLimits()">Сбросить лимиты</button>
                </div>
                <div class="card">
                    <h3>Режим техобслуживания</h3>
                    <p style="color:var(--text-muted); font-size:13px; margin:10px 0;">Запрет входа для клиентов на время работ.</p>
                    <button class="btn btn-outline" style="width:100%" onclick="App.toggleMaintenance()">Переключить режим</button>
                </div>
            </div>

            <div class="card" style="margin-top:30px">
                <h3>Резервное копирование и Восстановление</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-top:15px;">
                    <div>
                        <h4 style="margin-bottom:10px">Экспорт данных</h4>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;"><input type="checkbox" class="backup-entity" value="users" checked style="width:auto; margin:0;"> Пользователи</label>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;"><input type="checkbox" class="backup-entity" value="pricelist" checked style="width:auto; margin:0;"> Прайс-листы</label>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;"><input type="checkbox" class="backup-entity" value="orders" checked style="width:auto; margin:0;"> Заказы</label>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;"><input type="checkbox" class="backup-entity" value="messages" checked style="width:auto; margin:0;"> Сообщения</label>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;"><input type="checkbox" class="backup-entity" value="logs" checked style="width:auto; margin:0;"> Логи</label>
                            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;"><input type="checkbox" class="backup-entity" value="settings" checked style="width:auto; margin:0;"> Настройки</label>
                        </div>
                        <button class="btn btn-primary" onclick="App.handleBackup()">Создать и скачать бэкап (.zip)</button>
                    </div>
                    <div style="border-left: 1px solid var(--border-color); padding-left:30px;">
                        <h4 style="margin-bottom:10px">Импорт данных</h4>
                        <p style="color:var(--text-muted); font-size:13px; margin-bottom:15px;">Выберите ZIP-архив с данными для восстановления. <b>Внимание:</b> текущие файлы будут перезаписаны.</p>
                        <input type="file" id="restore-file-input" style="display:none" accept=".zip" onchange="App.handleRestore(this)">
                        <button class="btn btn-outline" onclick="document.getElementById('restore-file-input').click()">Загрузить и восстановить</button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('notification-settings-form').onsubmit = async (e) => {
            e.preventDefault();
            await this.apiFetch('api/settings.php?action=update', {
                method: 'POST',
                body: JSON.stringify({
                    smtp_enabled: document.getElementById('smtp_enabled').value === 'true',
                    smtp_from: document.getElementById('smtp_from').value
                })
            });
            this.showToast('Настройки уведомлений обновлены');
        };
    },

    async cleanupOrphans() {
        const res = await this.apiFetch('api/settings.php?action=maintenance&sub=cleanup_orphans');
        const data = await res.json();
        alert(`Очистка завершена. Удалено файлов: ${data.deleted}`);
        await this.fetchStats();
    },

    async clearRateLimits() {
        await this.apiFetch('api/settings.php?action=maintenance&sub=clear_rate_limits');
        alert('Лимиты сброшены');
    },

    async toggleMaintenance() {
        const res = await this.apiFetch('api/settings.php?action=toggle_maintenance');
        const data = await res.json();
        alert(`Режим техобслуживания: ${data.maintenance ? 'ВКЛ' : 'ВЫКЛ'}`);
    },

    async handleBackup() {
        const checkboxes = document.querySelectorAll('.backup-entity:checked');
        const entities = Array.from(checkboxes).map(cb => cb.value);
        if (entities.length === 0) {
            alert('Выберите сущности для резервного копирования');
            return;
        }

        const res = await this.apiFetch('api/backup.php?action=export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ entities })
        });

        if (res.ok) {
            const blob = await res.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `backup_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.zip`;
            document.body.appendChild(a);
            a.click();
            a.remove();
        } else {
            const err = await res.json();
            alert('Ошибка экспорта: ' + (err.error || 'Unknown error'));
        }
    },

    async handleRestore(input) {
        if (!input.files || !input.files[0]) return;
        if (!confirm('Вы уверены? Это перезапишет текущие данные выбранными из архива.')) {
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('backup_file', input.files[0]);

        const res = await this.apiFetch('api/backup.php?action=import', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert('Данные успешно восстановлены');
            location.reload();
        } else {
            alert('Ошибка восстановления: ' + (data.error || 'Unknown error'));
            input.value = '';
        }
    },

    async renderPriceList(container) {
        const isAdmin = ['superadmin', 'admin_content'].includes(this.user.role);

        // Admin first chooses/manages price lists
        if (isAdmin && !this.currentListId) {
            await this.renderPriceListsManager(container);
            return;
        }

        const listId = this.currentListId || this.user.assigned_pricelist_id || 'default';
        const res = await this.apiFetch(`api/pricelist.php?action=get&list_id=${listId}`);
        const data = await res.json();
        const list = data.data;
        const { items, columns } = list;

        let html = `
            <div class="view-header">
                <h1 class="view-title">${this.escapeHTML(list.name)}</h1>
                <div style="display:flex; gap:10px;">
                    ${isAdmin ? `<button class="btn btn-outline btn-sm" onclick="App.currentListId = null; App.setView('pricelist')">⬅️ К спискам</button>` : ''}
                    <button class="btn btn-outline btn-sm" onclick="window.print()">🖨️ Печать / PDF</button>
                    <a href="api/pricelist.php?action=export_csv&list_id=${listId}" class="btn btn-outline btn-sm">📊 Экспорт CSV</a>
                    ${isAdmin ? `
                        <button class="btn btn-outline btn-sm" onclick="App.showImportModal('${listId}')">📥 Импорт Excel</button>
                        <button class="btn btn-outline btn-sm" onclick="App.showPriceListEditModal('${listId}')">Настройка прайса</button>
                        <button class="btn btn-primary btn-sm" onclick="App.showPriceItemModal()">Добавить товар</button>
                    ` : ''}
                </div>
            </div>

            <div class="card glass" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" placeholder="Поиск по наименованию или коду..." id="price-search"
                               style="width:100%; padding: 12px 15px; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.05);">
                    </div>
                </div>
            </div>

            <div class="card glass">
                <table class="data-table" id="price-table">
                    <thead>
                        <tr>
                            ${columns.map(c => `<th>${this.escapeHTML(c.label)}</th>`).join('')}
                            ${!isAdmin ? '<th>Заказ (кол-во)</th>' : '<th>Действия</th>'}
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(item => `
                            <tr class="price-row" data-id="${item.id}" data-price="${item.price || 0}">
                                ${columns.map(c => `<td data-label="${this.escapeHTML(c.label)}">${this.escapeHTML(item[c.id] || '-')}</td>`).join('')}
                                <td data-label="${!isAdmin ? 'Заказ' : 'Действия'}">
                                    ${!isAdmin ? `
                                        <input type="number" class="order-qty" data-id="${item.id}" placeholder="0"
                                               style="width: 80px; padding: 8px; border-radius: 6px; border: 1px solid #ddd;" min="0"
                                               oninput="App.updateOrderSummary()">
                                    ` : `
                                        <div style="display:flex; gap:5px;">
                                            <button class="btn btn-outline btn-sm" onclick="App.showPriceItemModal('${item.id}')">✏️</button>
                                            <button class="btn btn-outline btn-sm" style="color:red" onclick="App.deletePriceItem('${item.id}')">🗑️</button>
                                        </div>
                                    `}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>

            ${!isAdmin ? `
                <div id="order-summary-bar" class="summary-bar glass hidden">
                    <div class="container" style="display:flex; justify-content: space-between; align-items: center; padding: 15px 0;">
                        <div>
                            <span style="font-size: 14px; opacity: 0.8;">Выбрано позиций: </span>
                            <strong id="summary-count">0</strong>
                            <span style="margin: 0 15px; opacity: 0.3;">|</span>
                            <span style="font-size: 14px; opacity: 0.8;">Общая сумма: </span>
                            <strong id="summary-total" style="font-size: 20px; color: var(--primary);">0.00</strong>
                        </div>
                        <button class="btn btn-primary" onclick="App.showSubmitOrderModal()">Оформить заказ</button>
                    </div>
                </div>
            ` : ''}
        `;
        container.innerHTML = html;
        document.getElementById('price-search').oninput = (e) => this.filterTable('price-table', e.target.value);
    },

    async renderPriceListsManager(container) {
        const res = await this.apiFetch('api/pricelist.php?action=list');
        const data = await res.json();
        const lists = data.lists;

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Управление прайс-листами</h1>
                <button class="btn btn-primary btn-sm" onclick="App.showPriceListEditModal()">Создать новый прайс</button>
            </div>
            <div class="grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                ${lists.map(l => `
                    <div class="card glass" style="cursor:pointer; position:relative;" onclick="App.currentListId = '${l.id}'; App.setView('pricelist')">
                        <h3 style="margin-bottom: 10px;">${this.escapeHTML(l.name)}</h3>
                        <p style="font-size: 12px; color: var(--text-muted);">ID: ${l.id}</p>
                        <div style="margin-top: 20px; display: flex; gap: 10px;" onclick="event.stopPropagation()">
                             <button class="btn btn-outline btn-sm" onclick="App.showPriceListEditModal('${l.id}')">⚙️ Настройка</button>
                             <button class="btn btn-outline btn-sm" style="color:red" onclick="App.deletePriceList('${l.id}')">🗑️ Удалить</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },

    async showPriceListEditModal(id = null) {
        let list = { name: '', columns: [{ id: 'code', label: 'Код', type: 'text' }, { id: 'name', label: 'Наименование', type: 'text' }, { id: 'price', label: 'Цена', type: 'number' }] };
        if (id) {
            const res = await this.apiFetch(`api/pricelist.php?action=get&list_id=${id}`);
            const data = await res.json();
            list = data.data;
        }

        this.showModal(id ? 'Настройка прайс-листа' : 'Создать прайс-лист', `
            <form id="price-list-form">
                <div class="form-group">
                    <label>Название прайс-листа</label>
                    <input type="text" id="pl-name" value="${this.escapeHTML(list.name)}" required>
                </div>
                <div id="columns-editor">
                    <label>Колонки прайса</label>
                    ${list.columns.map((c, i) => `
                        <div class="column-row" style="display:flex; gap:10px; margin-bottom: 10px;">
                            <input type="text" class="col-id" value="${this.escapeHTML(c.id)}" placeholder="ID (lat)" style="width: 80px;" ${i < 2 ? 'readonly' : ''}>
                            <input type="text" class="col-label" value="${this.escapeHTML(c.label)}" placeholder="Заголовок">
                            <select class="col-type" style="width: 100px;">
                                <option value="text" ${c.type === 'text' ? 'selected' : ''}>Текст</option>
                                <option value="number" ${c.type === 'number' ? 'selected' : ''}>Число</option>
                            </select>
                            ${i >= 2 ? `<button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:red; cursor:pointer;">&times;</button>` : ''}
                        </div>
                    `).join('')}
                </div>
                <button type="button" class="btn btn-outline btn-sm" style="margin-bottom: 20px;" onclick="App.addPriceListColumn()">+ Добавить колонку</button>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Сохранить</button>
            </form>
        `);

        document.getElementById('price-list-form').onsubmit = async (e) => {
            e.preventDefault();
            const cols = Array.from(document.querySelectorAll('.column-row')).map(row => ({
                id: row.querySelector('.col-id').value,
                label: row.querySelector('.col-label').value,
                type: row.querySelector('.col-type').value
            }));
            await this.apiFetch('api/pricelist.php?action=save_list', {
                method: 'POST',
                body: JSON.stringify({ id, name: document.getElementById('pl-name').value, columns: cols })
            });
            this.closeModal();
            this.setView('pricelist');
        };
    },

    addPriceListColumn() {
        const div = document.createElement('div');
        div.className = 'column-row';
        div.style.cssText = 'display:flex; gap:10px; margin-bottom: 10px;';
        div.innerHTML = `
            <input type="text" class="col-id" placeholder="ID (lat)" style="width: 80px;">
            <input type="text" class="col-label" placeholder="Заголовок">
            <select class="col-type" style="width: 100px;">
                <option value="text">Текст</option>
                <option value="number">Число</option>
            </select>
            <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:red; cursor:pointer;">&times;</button>
        `;
        document.getElementById('columns-editor').appendChild(div);
    },

    async deletePriceList(id) {
        if (confirm('Внимание! Это удалит весь прайс-лист со всеми товарами. Продолжить?')) {
            await this.apiFetch(`api/pricelist.php?action=delete_list&id=${id}`);
            this.setView('pricelist');
        }
    },

    async showImportModal(listId) {
        const res = await this.apiFetch(`api/pricelist.php?action=get&list_id=${listId}`);
        const data = await res.json();
        const list = data.data;

        this.showModal('Импорт данных из Excel (CSV)', `
            <div class="card" style="background: #f8faff; border-left: 4px solid var(--primary); margin-bottom: 20px;">
                <h4 style="margin-bottom:10px">Инструкция для администратора</h4>
                <ul style="font-size: 13px; line-height: 1.6; padding-left: 20px;">
                    <li>Файл должен быть в формате <b>CSV</b> (можно сохранить из Excel через "Сохранить как").</li>
                    <li>Кодировка файла: <b>UTF-8</b>.</li>
                    <li>Разделитель: <b>точка с запятой (;)</b> или запятая.</li>
                    <li>Первая строка должна содержать заголовки колонок (например: <b>Наименование</b>, <b>Цена</b>, <b>Код</b>).</li>
                    <li>Система автоматически сопоставит колонки по их названиям.</li>
                    <li>Если названия не совпадают, данные будут импортированы в порядке следования колонок.</li>
                    <li>Числовые значения (цены) могут содержать как точку, так и запятую.</li>
                </ul>
                <div style="margin-top:15px; text-align:center;">
                    <a href="api/pricelist.php?action=export_csv&list_id=${listId}" class="btn btn-outline btn-sm" style="background:#fff; border: 1px dashed var(--primary);">📥 Скачать готовый образец для этого прайса</a>
                </div>
                <div style="margin-top:15px; font-size: 13px;">
                    <strong>Текущие колонки для этого прайса:</strong>
                    <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:5px;">
                        ${list.columns.map(c => `<span class="badge badge-primary">${this.escapeHTML(c.label)}</span>`).join('')}
                    </div>
                </div>
            </div>

            <form id="import-form">
                <div class="form-group">
                    <label>Выберите файл (.csv)</label>
                    <input type="file" id="import-file" accept=".csv" required>
                </div>
                <div id="import-progress" class="hidden" style="margin-bottom:15px; text-align:center;">
                    <div style="font-size:14px; margin-bottom:5px;">Обработка данных...</div>
                    <div style="height:4px; background:#eee; border-radius:2px; overflow:hidden;">
                        <div style="width:100%; height:100%; background:var(--primary); animation: progress-indet 2s infinite linear;"></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px;">Запустить импорт</button>
            </form>

            <style>
                @keyframes progress-indet {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }
            </style>
        `);

        document.getElementById('import-form').onsubmit = async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('import-file');
            if (!fileInput.files.length) return;

            document.getElementById('import-progress').classList.remove('hidden');
            e.target.querySelector('button').disabled = true;

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            try {
                const res = await this.apiFetch(`api/pricelist.php?action=import_csv&list_id=${listId}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    this.showToast(`Импорт завершен! Загружено позиций: ${result.count}`);
                    this.closeModal();
                    this.setView('pricelist');
                } else {
                    alert('Ошибка импорта: ' + result.error);
                    document.getElementById('import-progress').classList.add('hidden');
                    e.target.querySelector('button').disabled = false;
                }
            } catch (err) {
                alert('Произошла системная ошибка при импорте');
                document.getElementById('import-progress').classList.add('hidden');
                e.target.querySelector('button').disabled = false;
            }
        };
    },

    async showPriceItemModal(id = null) {
        const listId = this.currentListId || this.user.assigned_pricelist_id || 'default';
        const res = await this.apiFetch(`api/pricelist.php?action=get&list_id=${listId}`);
        const data = await res.json();
        const list = data.data;

        let item = {};
        if (id) {
            item = list.items.find(i => i.id === id);
        }

        this.showModal(id ? 'Редактировать товар' : 'Добавить товар', `
            <form id="price-item-form">
                ${list.columns.map(c => `
                    <div class="form-group">
                        <label>${this.escapeHTML(c.label)}</label>
                        <input type="${c.type === 'number' ? 'number' : 'text'}" id="p-${c.id}" value="${this.escapeHTML(item[c.id] || '')}" ${c.id === 'name' ? 'required' : ''} step="any">
                    </div>
                `).join('')}
                <button type="submit" class="btn btn-primary" style="width: 100%;">${id ? 'Сохранить' : 'Добавить'}</button>
            </form>
        `);

        document.getElementById('price-item-form').onsubmit = async (e) => {
            e.preventDefault();
            const newItem = { id: id };
            list.columns.forEach(c => {
                newItem[c.id] = document.getElementById(`p-${c.id}`).value;
            });
            await this.apiFetch(`api/pricelist.php?action=save_item&list_id=${listId}`, {
                method: 'POST',
                body: JSON.stringify(newItem)
            });
            this.closeModal();
            this.setView('pricelist');
        };
    },

    async deletePriceItem(id) {
        const listId = this.currentListId || this.user.assigned_pricelist_id || 'default';
        if (confirm('Удалить этот товар из прайса?')) {
            await this.apiFetch(`api/pricelist.php?action=delete_item&id=${id}&list_id=${listId}`);
            this.setView('pricelist');
        }
    },

    updateOrderSummary() {
        const inputs = document.querySelectorAll('.order-qty');
        let total = 0;
        let count = 0;
        inputs.forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                const row = input.closest('tr');
                const price = parseFloat(row.dataset.price) || 0;
                total += qty * price;
                count++;
            }
        });

        const summaryBar = document.getElementById('order-summary-bar');
        if (summaryBar) {
            if (count > 0) {
                summaryBar.classList.remove('hidden');
                document.getElementById('summary-count').textContent = count;
                document.getElementById('summary-total').textContent = total.toFixed(2);
            } else {
                summaryBar.classList.add('hidden');
            }
        }
    },

    showSubmitOrderModal() {
        this.showModal('Оформление заказа', `
            <form id="submit-order-form">
                <div class="form-group">
                    <label>Ваш комментарий к заказу</label>
                    <textarea id="order-comment" placeholder="Напишите пожелания по доставке или другие детали..." style="height: 100px;"></textarea>
                </div>
                <div class="card" style="background: #f8f9ff; border: 1px dashed var(--primary);">
                    <p style="display:flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Позиций в заказе:</span>
                        <strong id="final-count">${document.getElementById('summary-count').textContent}</strong>
                    </p>
                    <p style="display:flex; justify-content: space-between; font-size: 18px;">
                        <span>Итого к оплате:</span>
                        <strong style="color: var(--primary)">${document.getElementById('summary-total').textContent}</strong>
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Подтвердить заказ</button>
            </form>
        `);

        document.getElementById('submit-order-form').onsubmit = (e) => {
            e.preventDefault();
            this.submitOrder(document.getElementById('order-comment').value);
        };
    },

    async submitOrder(comment = '') {
        const inputs = document.querySelectorAll('.order-qty');
        const items = [];
        inputs.forEach(input => {
            const qty = parseFloat(input.value);
            if (qty > 0) {
                items.push({
                    id: input.dataset.id,
                    qty: qty
                });
            }
        });

        if (items.length === 0) {
            alert('Выберите хотя бы один товар, указав количество');
            return;
        }

        try {
            const res = await this.apiFetch('api/orders.php?action=submit', {
                method: 'POST',
                body: JSON.stringify({ items, comment })
            });
            const data = await res.json();
            if (data.success) {
                this.closeModal();
                alert('Заказ успешно оформлен! Администратор свяжется с вами.');
                this.setView('pricelist');
            }
        } catch (e) {
            this.showToast('Ошибка при оформлении заказа', 'error');
        }
    },

    async showOrderDetails(id) {
        const res = await this.apiFetch('api/orders.php?action=list');
        const data = await res.json();
        const order = data.orders.find(o => o.id === id);
        if (!order) return;

        const isAdmin = ['superadmin', 'admin_content'].includes(this.user.role);

        let html = `
            <div style="margin-bottom: 20px;">
                <p><strong>Дата:</strong> ${order.created_at}</p>
                <p><strong>Клиент:</strong> ${this.escapeHTML(order.company_name || order.username)}</p>
                <p><strong>Статус:</strong> ${order.status}</p>
                ${order.comment ? `<p><strong>Комментарий:</strong><br><span style="font-size: 13px; color: var(--text-muted);">${this.escapeHTML(order.comment)}</span></p>` : ''}
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Кол-во</th>
                        <th>Цена</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map(oi => {
                        return `
                            <tr>
                                <td>${this.escapeHTML(oi.name_snapshot || 'Удаленный товар')}</td>
                                <td>${oi.qty}</td>
                                <td>${(oi.price_snapshot || 0).toFixed(2)}</td>
                                <td>${(oi.subtotal || 0).toFixed(2)}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" style="text-align:right">ИТОГО:</th>
                        <th>${(order.total_sum || 0).toFixed(2)}</th>
                    </tr>
                </tfoot>
            </table>
            ${isAdmin ? `
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button class="btn btn-primary btn-sm" onclick="App.updateOrderStatus('${order.id}', 'В обработке')">В работу</button>
                    <button class="btn btn-primary btn-sm" style="background: #27ae60" onclick="App.updateOrderStatus('${order.id}', 'Выполнен')">Завершен</button>
                    <button class="btn btn-outline btn-sm" style="color:red" onclick="App.updateOrderStatus('${order.id}', 'Отменен')">Отменить</button>
                </div>
            ` : ''}
        `;

        this.showModal(`Заказ #${id.substring(0,8)}`, html);
    },

    async updateOrderStatus(id, status) {
        await this.apiFetch('api/orders.php?action=update_status', {
            method: 'POST',
            body: JSON.stringify({ id, status })
        });
        this.closeModal();
        this.setView(this.currentView);
        this.showToast('Статус заказа обновлен');
    },

    async renderOrders(container) {
        const res = await this.apiFetch('api/orders.php?action=list');
        const data = await res.json();
        const isAdmin = ['superadmin', 'admin_content'].includes(this.user.role);
        const orders = data.orders.reverse();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Управление заказами</h1>
                <div style="display:flex; gap:10px;">
                    <input type="text" placeholder="Поиск по заказам..." id="order-search" style="padding:6px 12px; width:200px;">
                </div>
            </div>
            <div class="card">
                <table class="data-table" id="order-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            ${isAdmin ? '<th>Клиент</th>' : ''}
                            <th>Позиций</th>
                            <th>Дата</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orders.map(o => `
                            <tr>
                                <td data-label="ID">#${o.id.substring(0,8)}</td>
                                ${isAdmin ? `<td data-label="Клиент">${this.escapeHTML(o.company_name || o.username)}</td>` : ''}
                                <td data-label="Позиций">${o.total_items}</td>
                                <td data-label="Дата">${o.created_at}</td>
                                <td data-label="Статус">
                                    <span class="badge ${o.status === 'Новый' ? 'badge-warning' : (o.status === 'Выполнен' ? 'badge-success' : 'badge-primary')}">
                                        ${this.escapeHTML(o.status)}
                                    </span>
                                </td>
                                <td data-label="Действия">
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn btn-outline btn-sm" onclick="App.showOrderDetails('${o.id}')">🔍 Детали</button>
                                        ${isAdmin ? `<button class="btn btn-outline btn-sm" style="color:red" onclick="App.deleteOrder('${o.id}')">🗑️</button>` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                        ${orders.length === 0 ? '<tr><td colspan="6" style="text-align:center">Заказов пока нет</td></tr>' : ''}
                    </tbody>
                </table>
            </div>
        `;
        document.getElementById('order-search').oninput = (e) => this.filterTable('order-table', e.target.value);
    },

    async renderProfile(container) {
        const res = await this.apiFetch('api/users.php?action=list');
        const data = await res.json();
        const me = data.users.find(u => u.id === this.user.id);
        if (!me) return;

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Мой профиль</h1>
            </div>
            <div class="card">
                <h3>Настройки уведомлений</h3>
                <form id="profile-form" style="margin-top:20px">
                    <div class="form-group">
                        <label>Ваш Email для уведомлений</label>
                        <input type="email" id="profile-email" value="${this.escapeHTML(me.email || '')}" placeholder="example@mail.com">
                        <small style="color:var(--text-muted)">На этот адрес будут приходить уведомления о новых документах и сообщениях.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
            <div class="card">
                <h3>Безопасность</h3>
                <p style="margin:15px 0">Вы можете изменить свой пароль для входа в кабинет.</p>
                <button class="btn btn-outline" onclick="App.showEditUserModal('${this.user.id}')">Изменить пароль / Данные</button>
            </div>
        `;

        document.getElementById('profile-form').onsubmit = async (e) => {
            e.preventDefault();
            await this.apiFetch(`api/users.php?action=update&id=${this.user.id}`, {
                method: 'POST',
                body: JSON.stringify({
                    email: document.getElementById('profile-email').value
                })
            });
            this.showToast('Профиль обновлен');
        };
    },

    renderAbout(container) {
        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">О программе</h1>
            </div>
            <div class="card">
                <h2 style="margin-bottom:15px; color:var(--primary)">Личный кабинет v2.2</h2>
                <p style="margin-bottom:10px">Система управления личным кабинетом клиента.</p>
                <hr style="margin-bottom:15px; border:0; border-top:1px solid var(--border)">
                <p style="font-weight:600; margin-bottom:10px;">Разработана WES.BY</p>
                <p style="margin-bottom:10px;">Служба поддержки: <a href="tel:+375333533971" style="color:var(--primary); text-decoration:none">+375 33 353 39 71</a></p>
                <p style="font-style: italic; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 15px; margin-top: 15px;">
                    Такое же приложение вы можете заказать на <a href="https://wes.by" target="_blank" style="color:var(--primary); text-decoration:none; font-weight:600;">WES.BY</a>
                </p>
            </div>
        `;
    },

    // Modal Handling
    showModal(title, content) {
        document.getElementById('modal-body').innerHTML = `
            <h2 style="margin-bottom:20px">${title}</h2>
            ${content}
        `;
        const modal = document.getElementById('modal-container');
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.add('active'), 10);
    },

    closeModal() {
        const modal = document.getElementById('modal-container');
        modal.classList.remove('active');
        setTimeout(() => modal.classList.add('hidden'), 300);
    },

    showUploadModal() {
        const clientOptions = this.clients.filter(u => u.role === 'client').map(c => `<option value="${c.id}">${this.escapeHTML(c.company_name || c.username)} (${c.username})</option>`).join('');
        this.showModal('Загрузка документа', `
            <form id="upload-form">
                <div class="form-group">
                    <label>Название документа</label>
                    <input type="text" id="doc-name" required>
                </div>
                <div class="form-group">
                    <label>Выбор клиента (оставьте "Все" для общего)</label>
                    <select id="doc-client-id">
                        <option value="">Все клиенты (общий)</option>
                        ${clientOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label>Файл</label>
                    <input type="file" id="doc-file" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Загрузить</button>
            </form>
        `);
        document.getElementById('upload-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('file', document.getElementById('doc-file').files[0]);
            formData.append('name', document.getElementById('doc-name').value);
            const client_id = document.getElementById('doc-client-id').value;
            formData.append('client_id', client_id);
            formData.append('is_public', client_id ? 'false' : 'true');
            await this.apiFetch('api/documents.php?action=upload', { method: 'POST', body: formData });
            await this.fetchStats();
            this.closeModal();
            this.setView('documents');
        };
    },

    showSendMessageModal() {
        const isAdmin = ['superadmin', 'admin_communications'].includes(this.user.role);
        const clientOptions = this.clients.filter(u => u.role === 'client').map(c => `<option value="${c.id}">${this.escapeHTML(c.company_name || c.username)} (${c.username})</option>`).join('');

        this.showModal('Написать сообщение', `
            <form id="msg-form">
                ${isAdmin ? `
                <div class="form-group">
                    <label>Получатель</label>
                    <select id="msg-to">
                        <option value="all">Все клиенты (Рассылка)</option>
                        ${clientOptions}
                    </select>
                </div>
                ` : ''}
                <div class="form-group">
                    <label>Тема</label>
                    <input type="text" id="msg-subject" required>
                </div>
                <div class="form-group">
                    <label>Текст сообщения</label>
                    <textarea id="msg-body" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Прикрепить файл (опционально)</label>
                    <input type="file" id="msg-attachment">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Отправить</button>
            </form>
        `);

        document.getElementById('msg-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('to', isAdmin ? document.getElementById('msg-to').value : 'admin');
            formData.append('subject', document.getElementById('msg-subject').value);
            formData.append('body', document.getElementById('msg-body').value);
            const file = document.getElementById('msg-attachment').files[0];
            if (file) formData.append('attachment', file);

            await this.apiFetch('api/messages.php?action=send', { method: 'POST', body: formData });
            await this.fetchStats();
            this.closeModal();
            this.setView('messages');
        };
    },

    showReplyModal(parentId, subject, originalSender) {
        const isAdmin = ['superadmin', 'admin_communications'].includes(this.user.role);
        this.showModal('Ответить', `
            <form id="reply-form">
                <div class="form-group">
                    <label>Тема</label>
                    <input type="text" id="msg-subject" value="Re: ${this.escapeHTML(subject)}" required>
                </div>
                <div class="form-group">
                    <label>Текст сообщения</label>
                    <textarea id="msg-body" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Прикрепить файл</label>
                    <input type="file" id="msg-attachment">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Отправить ответ</button>
            </form>
        `);

        document.getElementById('reply-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('parent_id', parentId);
            formData.append('to', isAdmin ? originalSender : 'admin');
            formData.append('subject', document.getElementById('msg-subject').value);
            formData.append('body', document.getElementById('msg-body').value);
            const file = document.getElementById('msg-attachment').files[0];
            if (file) formData.append('attachment', file);

            await this.apiFetch('api/messages.php?action=send', { method: 'POST', body: formData });
            this.closeModal();
            this.setView('messages');
        };
    },

    async showCreateUserModal() {
        const plRes = await this.apiFetch('api/pricelist.php?action=list');
        const plData = await plRes.json();
        const priceLists = plData.lists || [];

        this.showModal('Новый клиент / Пользователь', `
            <form id="user-form">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <div class="form-group">
                            <label>Логин *</label>
                            <input type="text" id="user-username" required>
                        </div>
                        <div class="form-group">
                            <label>Пароль *</label>
                            <input type="password" id="user-password" required>
                        </div>
                        <div class="form-group">
                            <label>Email для уведомлений</label>
                            <input type="email" id="user-email">
                        </div>
                        <div class="form-group">
                            <label>Роль</label>
                            <select id="user-role">
                                <option value="client">Клиент</option>
                                <option value="admin_content">Админ контента</option>
                                <option value="admin_clients">Админ клиентов</option>
                                <option value="admin_communications">Админ коммуникаций</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Название компании</label>
                            <input type="text" id="user-company">
                        </div>
                        <div class="form-group">
                            <label>ИНН / УНП</label>
                            <input type="text" id="user-tax-id">
                        </div>
                        <div class="form-group">
                            <label>Адрес</label>
                            <input type="text" id="user-address">
                        </div>
                        <div class="form-group">
                            <label>Назначенный Прайс-лист</label>
                            <select id="user-pricelist">
                                ${priceLists.map(l => `<option value="${l.id}">${this.escapeHTML(l.name)}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Контактное лицо</label>
                        <input type="text" id="user-contact">
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" id="user-phone">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Создать</button>
            </form>
        `);
        document.getElementById('user-form').onsubmit = async (e) => {
            e.preventDefault();
            await this.apiFetch('api/users.php?action=create', {
                method: 'POST',
                body: JSON.stringify({
                    username: document.getElementById('user-username').value,
                    password: document.getElementById('user-password').value,
                    role: document.getElementById('user-role').value,
                    company_name: document.getElementById('user-company').value,
                    email: document.getElementById('user-email').value,
                    tax_id: document.getElementById('user-tax-id').value,
                    address: document.getElementById('user-address').value,
                    contact_person: document.getElementById('user-contact').value,
                    phone: document.getElementById('user-phone').value,
                    assigned_pricelist_id: document.getElementById('user-pricelist').value
                })
            });
            await this.fetchClients();
            await this.fetchStats();
            this.closeModal();
            this.setView('users');
        };
    },

    async deleteDocument(id) {
        if (confirm('Удалить документ?')) {
            await this.apiFetch(`api/documents.php?action=delete&id=${id}`);
            await this.fetchStats();
            this.setView('documents');
        }
    },

    async toggleArchiveDocument(id, isArchived) {
        const action = isArchived ? 'restore' : 'archive';
        await this.apiFetch(`api/documents.php?action=${action}&id=${id}`);
        this.setView('documents');
    },

    async clearLogs() {
        if (confirm('Очистить все логи безвозвратно?')) {
            await this.apiFetch('api/logs.php?action=clear_all');
            this.setView('logs');
        }
    },

    async deleteUser(id) {
        if (confirm('Удалить пользователя?')) {
            await this.apiFetch(`api/users.php?action=delete&id=${id}`);
            await this.fetchClients();
            await this.fetchStats();
            this.setView('users');
        }
    },

    async blockUser(id) {
        if (confirm('Заблокировать пользователя?')) {
            await this.apiFetch(`api/users.php?action=block&id=${id}`);
            await this.fetchClients();
            this.setView('users');
        }
    },

    async unblockUser(id) {
        if (confirm('Разблокировать пользователя?')) {
            await this.apiFetch(`api/users.php?action=unblock&id=${id}`);
            await this.fetchClients();
            this.setView('users');
        }
    },

    async resetPassword(id) {
        if (confirm('Сбросить пароль?')) {
            const res = await this.apiFetch(`api/users.php?action=reset_password&id=${id}`);
            const data = await res.json();
            alert(`Новый пароль: ${data.new_password}`);
        }
    },

    async approveUser(id) {
        if (confirm('Одобрить регистрацию этого клиента?')) {
            await this.apiFetch(`api/users.php?action=approve&id=${id}`);
            await this.fetchClients();
            this.setView('users');
            this.showToast('Клиент одобрен');
        }
    },

    async rejectUser(id) {
        if (confirm('Отклонить заявку на регистрацию?')) {
            await this.apiFetch(`api/users.php?action=reject&id=${id}`);
            await this.fetchClients();
            this.setView('users');
            this.showToast('Заявка отклонена');
        }
    },

    async showEditUserModal(id) {
        const user = this.clients.find(u => u.id === id);
        if (!user) return;

        const plRes = await this.apiFetch('api/pricelist.php?action=list');
        const plData = await plRes.json();
        const priceLists = plData.lists || [];

        this.showModal('Редактировать клиента', `
            <form id="edit-user-form">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <div class="form-group">
                            <label>Логин</label>
                            <input type="text" id="user-username" value="${this.escapeHTML(user.username)}" required>
                        </div>
                        <div class="form-group">
                            <label>Новый пароль (оставьте пустым)</label>
                            <input type="password" id="user-password">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="user-email" value="${this.escapeHTML(user.email || '')}">
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Название компании</label>
                            <input type="text" id="user-company" value="${this.escapeHTML(user.company_name || '')}">
                        </div>
                        <div class="form-group">
                            <label>ИНН / УНП</label>
                            <input type="text" id="user-tax-id" value="${this.escapeHTML(user.tax_id || '')}">
                        </div>
                        <div class="form-group">
                            <label>Назначенный Прайс-лист</label>
                            <select id="user-pricelist">
                                ${priceLists.map(l => `<option value="${l.id}" ${user.assigned_pricelist_id === l.id ? 'selected' : ''}>${this.escapeHTML(l.name)}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Сохранить</button>
            </form>
        `);

        document.getElementById('edit-user-form').onsubmit = async (e) => {
            e.preventDefault();
            await this.apiFetch(`api/users.php?action=update&id=${id}`, {
                method: 'POST',
                body: JSON.stringify({
                    username: document.getElementById('user-username').value,
                    password: document.getElementById('user-password').value,
                    company_name: document.getElementById('user-company').value,
                    email: document.getElementById('user-email').value,
                    tax_id: document.getElementById('user-tax-id').value,
                    assigned_pricelist_id: document.getElementById('user-pricelist').value
                })
            });
            await this.fetchClients();
            this.closeModal();
            this.setView('users');
        };
    }
};

App.init();
