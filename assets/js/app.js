const App = {
    user: null,
    currentView: 'dashboard',
    clients: [],
    stats: null,

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

        document.getElementById('logout-btn').addEventListener('click', () => this.logout());
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
        return fetch(url, options);
    },

    async checkAuth() {
        try {
            const res = await this.apiFetch('api/auth.php?action=check');
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
        await this.apiFetch('api/auth.php?action=logout');
        this.user = null;
        this.showLogin();
    },

    showLogin() {
        document.getElementById('auth-view').classList.remove('hidden');
        document.getElementById('app-view').classList.add('hidden');
        document.getElementById('app-view').style.opacity = '0';
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
            await this.fetchStats();
        }

        this.setView(this.currentView);
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
    },

    async setView(view) {
        this.currentView = view;
        document.querySelectorAll('.nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.view === view);
        });

        const container = document.getElementById('view-container');
        container.style.opacity = '0';
        container.style.transform = 'translateY(10px)';
        container.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';

        setTimeout(async () => {
            container.innerHTML = '<div class="card animate-fade">Загрузка...</div>';
            switch (view) {
            case 'dashboard':
                await this.renderDashboard(container);
                break;
            case 'documents':
                await this.renderDocuments(container);
                break;
            case 'messages':
                await this.renderMessages(container);
                break;
            case 'users':
                await this.renderUsers(container);
                break;
            case 'logs':
                await this.renderLogs(container);
                break;
                case 'about':
                    this.renderAbout(container);
                    break;
            }
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 150);
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
                        <small style="color:var(--text-muted)">Логи</small>
                        <h3 style="font-size:24px">${this.stats.total_logs}</h3>
                    </div>
                </div>
            `;
        }

        html += `
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                <div class="card" style="text-align:center; cursor:pointer" onclick="App.setView('documents')">
                    <h3 style="margin-bottom:10px">Документы</h3>
                    <p style="font-size:32px; color:var(--primary)">📄</p>
                </div>
                <div class="card" style="text-align:center; cursor:pointer" onclick="App.setView('messages')">
                    <h3 style="margin-bottom:10px">Сообщения</h3>
                    <p style="font-size:32px; color:var(--primary)">✉️</p>
                </div>
            </div>
        `;
        container.innerHTML = html;
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
                        ${data.documents.map(doc => `
                            <tr style="${doc.archived ? 'opacity:0.6; font-style:italic' : ''}">
                                <td data-label="Название">
                                    ${this.escapeHTML(doc.name)}
                                    ${doc.archived ? ' <small>(архив)</small>' : ''}
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
                        `).join('')}
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

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Чат и поддержка</h1>
                ${isAdmin ? '<div style="display:flex; gap:10px;"><select id="chat-filter" style="padding:6px; font-size:12px;"><option value="all">Все сообщения</option></select></div>' : ''}
            </div>
            <div class="chat-layout">
                <div class="chat-history" id="chat-history">
                    ${data.messages.map(msg => `
                        <div class="chat-bubble ${msg.from === this.user.id ? 'mine' : 'theirs'}">
                            <div style="font-weight:600; font-size:11px; margin-bottom:4px;">
                                ${this.escapeHTML(msg.from_name || msg.from)}
                            </div>
                            <div style="margin-bottom:5px;"><strong>${this.escapeHTML(msg.subject)}</strong></div>
                            <div style="white-space: pre-wrap;">${this.escapeHTML(msg.body)}</div>
                            ${msg.attachments && msg.attachments.length > 0 ? `
                                <div class="chat-attachments">
                                    ${msg.attachments.map(att => `
                                        <a href="api/messages.php?action=download_attachment&id=${att.id}" class="chat-att-item">📎 ${this.escapeHTML(att.name)}</a>
                                    `).join('')}
                                </div>
                            ` : ''}
                            <div class="chat-info">
                                <span>${this.escapeHTML(msg.created_at)}</span>
                                <span style="cursor:pointer" onclick="App.showReplyModal('${msg.id}', '${this.escapeHTML(msg.subject)}', '${msg.from}')">Ответить</span>
                            </div>
                        </div>
                    `).join('')}
                    ${data.messages.length === 0 ? '<p style="text-align:center; color:var(--text-muted); margin:auto;">Сообщений пока нет.</p>' : ''}
                </div>
                <div class="chat-input-area">
                    <button class="btn btn-primary" style="width:100%" onclick="App.showSendMessageModal()">Написать сообщение</button>
                </div>
            </div>
        `;

        const history = document.getElementById('chat-history');
        history.scrollTop = history.scrollHeight;

        if (isAdmin) {
            const filter = document.getElementById('chat-filter');
            const clientIds = [...new Set(data.messages.flatMap(m => [m.from, m.to]).filter(id => id !== this.user.id && id !== 'admin' && id !== 'all'))];
            clientIds.forEach(id => {
                const opt = document.createElement('option');
                opt.value = id;
                const u = this.clients.find(c => c.id === id);
                opt.textContent = u ? `Чат с: ${u.company_name || u.username}` : `Чат с: ${id}`;
                filter.appendChild(opt);
            });
            filter.onchange = (e) => {
                const val = e.target.value;
                document.querySelectorAll('.chat-bubble').forEach((b, idx) => {
                    const msg = data.messages[idx];
                    if (val === 'all') b.style.display = '';
                    else {
                        const isRelevant = msg.from === val || msg.to === val;
                        b.style.display = isRelevant ? '' : 'none';
                    }
                });
            };
        }
    },

    async renderUsers(container) {
        const res = await this.apiFetch('api/users.php?action=list');
        const data = await res.json();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Управление клиентами</h1>
                <div style="display:flex; gap:10px;">
                    <input type="text" placeholder="Поиск клиентов..." id="user-search" style="padding:6px 12px; width:200px;">
                    <button class="btn btn-primary btn-sm" onclick="App.showCreateUserModal()">Новый клиент</button>
                </div>
            </div>
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
                        ${data.users.map(u => `
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
                                <td data-label="Статус"><span class="badge ${u.status === 'active' ? 'badge-success' : 'badge-warning'}">${this.escapeHTML(u.status)}</span></td>
                                <td data-label="Действия">
                                    <div style="display:flex; gap:5px; flex-wrap:wrap">
                                        <button class="btn btn-outline btn-sm" onclick="App.showEditUserModal('${u.id}')">✏️</button>
                                        ${u.status === 'active' ?
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
        document.getElementById('user-search').oninput = (e) => this.filterTable('user-table', e.target.value);
    },

    async renderLogs(container) {
        const res = await this.apiFetch('api/logs.php?action=list');
        const data = await res.json();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Журнал аудита</h1>
                <div style="display:flex; gap:10px;">
                    <input type="text" placeholder="Поиск в логах..." id="log-search" style="padding:6px 12px; width:200px;">
                    <a href="api/logs.php?action=export" class="btn btn-outline btn-sm">Экспорт CSV</a>
                </div>
            </div>
            <div class="card">
                <table class="data-table" id="log-table">
                    <thead>
                        <tr>
                            <th>Событие</th>
                            <th>Объект</th>
                            <th>Дата</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.logs.map(log => `
                            <tr>
                                <td data-label="Событие">${this.escapeHTML(log.type)}</td>
                                <td data-label="Объект">${this.escapeHTML(log.object)}</td>
                                <td data-label="Дата">${this.escapeHTML(log.date)}</td>
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

    renderAbout(container) {
        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">О программе</h1>
            </div>
            <div class="card">
                <h2 style="margin-bottom:15px; color:var(--primary)">Enterprise Portal v2.0</h2>
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

    showCreateUserModal() {
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
                    tax_id: document.getElementById('user-tax-id').value,
                    address: document.getElementById('user-address').value,
                    contact_person: document.getElementById('user-contact').value,
                    phone: document.getElementById('user-phone').value
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

    async showEditUserModal(id) {
        const user = this.clients.find(u => u.id === id);
        if (!user) return;

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
                    tax_id: document.getElementById('user-tax-id').value
                })
            });
            await this.fetchClients();
            this.closeModal();
            this.setView('users');
        };
    }
};

App.init();
