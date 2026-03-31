const App = {
    user: null,
    currentView: 'dashboard',

    async init() {
        this.bindEvents();
        await this.checkAuth();
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
        const code = document.getElementById('2fa-code').value;

        const res = await this.apiFetch('api/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ username, password, code })
        });
        const data = await res.json();

        if (data.success) {
            this.user = data.user;
            this.showApp();
        } else if (data['2fa_required']) {
            document.getElementById('2fa-group').classList.remove('hidden');
            alert('Введите код 2FA (тестовый: 000000)');
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
    },

    showApp() {
        document.getElementById('auth-view').classList.add('hidden');
        document.getElementById('app-view').classList.remove('hidden');

        const isAdmin = ['superadmin', 'admin_clients', 'admin_content', 'admin_communications'].includes(this.user.role);
        document.querySelectorAll('.admin-only').forEach(el => {
            el.classList.toggle('hidden', !isAdmin);
        });

        this.setView(this.currentView);
    },

    async setView(view) {
        this.currentView = view;
        document.querySelectorAll('.nav-item').forEach(el => {
            el.classList.toggle('active', el.dataset.view === view);
        });

        const container = document.getElementById('view-container');
        container.innerHTML = '<div class="card">Загрузка...</div>';

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
    },

    async renderDashboard(container) {
        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Добро пожаловать, ${this.escapeHTML(this.user.username)}</h1>
            </div>
            <div class="card">
                <p>Вы вошли как: <strong>${this.escapeHTML(this.user.role)}</strong></p>
                <p>Используйте меню для навигации по документам и сообщениям.</p>
            </div>
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
    },

    renderAbout(container) {
        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">О программе</h1>
            </div>
            <div class="card">
                <h2 style="margin-bottom:15px; color:var(--primary)">Enterprise Portal v1.0</h2>
                <p style="margin-bottom:10px">Система управления личным кабинетом клиента.</p>
                <hr style="margin-bottom:15px; border:0; border-top:1px solid var(--border)">
                <p style="font-weight:600">Разработана WES.BY</p>
                <p>Служба поддержки: <a href="tel:+375333533971" style="color:var(--primary); text-decoration:none">+375 33 353 39 71</a></p>
            </div>
        `;
    },

    async renderDocuments(container) {
        const res = await this.apiFetch('api/documents.php?action=list');
        const data = await res.json();

        let html = `
            <div class="view-header">
                <h1 class="view-title">Документы</h1>
                ${['superadmin', 'admin_content'].includes(this.user.role) ? '<button class="btn btn-primary btn-sm" onclick="App.showUploadModal()">Загрузить</button>' : ''}
            </div>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.documents.map(doc => `
                            <tr>
                                <td data-label="Название">${this.escapeHTML(doc.name)}</td>
                                <td data-label="Тип">${this.escapeHTML(doc.meta.type) || '-'}</td>
                                <td data-label="Дата">${this.escapeHTML(doc.uploaded_at)}</td>
                                <td data-label="Действия">
                                    <a href="api/documents.php?action=download&id=${doc.id}" class="btn btn-outline btn-sm">Скачать</a>
                                    ${['superadmin', 'admin_content'].includes(this.user.role) ? `<button onclick="App.deleteDocument('${doc.id}')" class="btn btn-outline btn-sm" style="color:red">Удалить</button>` : ''}
                                </td>
                            </tr>
                        `).join('')}
                        ${data.documents.length === 0 ? '<tr><td colspan="4" style="text-align:center">Нет доступных документов</td></tr>' : ''}
                    </tbody>
                </table>
            </div>
        `;
        container.innerHTML = html;
    },

    async renderMessages(container) {
        const res = await this.apiFetch('api/messages.php?action=list');
        const data = await res.json();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Сообщения</h1>
                ${['superadmin', 'admin_communications'].includes(this.user.role) ? '<button class="btn btn-primary btn-sm" onclick="App.showSendMessageModal()">Отправить</button>' : ''}
            </div>
            <div class="card">
                ${data.messages.map(msg => `
                    <div class="card" style="margin-bottom: 15px; border-left: 4px solid var(--primary); padding: 15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:10px">
                            <strong>${this.escapeHTML(msg.subject)}</strong>
                            <small style="color:var(--text-muted)">${this.escapeHTML(msg.created_at)}</small>
                        </div>
                        <p style="font-size:14px; color:var(--text)">${this.escapeHTML(msg.body)}</p>
                    </div>
                `).join('')}
                ${data.messages.length === 0 ? '<p style="text-align:center; color:var(--text-muted); padding:20px;">Сообщений пока нет.</p>' : ''}
            </div>
        `;
    },

    async renderUsers(container) {
        const res = await this.apiFetch('api/users.php?action=list');
        const data = await res.json();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Управление клиентами</h1>
                <button class="btn btn-primary btn-sm" onclick="App.showCreateUserModal()">Новый клиент</button>
            </div>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Логин</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.users.map(u => `
                            <tr>
                                <td data-label="Логин">${this.escapeHTML(u.username)}</td>
                                <td data-label="Роль">${this.escapeHTML(u.role)}</td>
                                <td data-label="Статус"><span class="badge ${u.status === 'active' ? 'badge-success' : 'badge-warning'}">${this.escapeHTML(u.status)}</span></td>
                                <td data-label="Действия">
                                    <button class="btn btn-outline btn-sm" onclick="App.deleteUser('${u.id}')">Удалить</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    },

    async renderLogs(container) {
        const res = await this.apiFetch('api/logs.php?action=list');
        const data = await res.json();

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Журнал аудита</h1>
                <a href="api/logs.php?action=export" class="btn btn-outline btn-sm">Экспорт CSV</a>
            </div>
            <div class="card">
                <table class="data-table">
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
                        `).reverse().slice(0, 50).join('')}
                    </tbody>
                </table>
            </div>
        `;
    },

    // Modal Handling
    showModal(title, content) {
        document.getElementById('modal-body').innerHTML = `
            <h2 style="margin-bottom:20px">${title}</h2>
            ${content}
        `;
        document.getElementById('modal-container').classList.remove('hidden');
    },

    closeModal() {
        document.getElementById('modal-container').classList.add('hidden');
    },

    showUploadModal() {
        this.showModal('Загрузка документа', `
            <form id="upload-form">
                <div class="form-group">
                    <label>Название документа</label>
                    <input type="text" id="doc-name" required>
                </div>
                <div class="form-group">
                    <label>ID клиента (оставьте пустым для всех)</label>
                    <input type="text" id="doc-client-id">
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
            this.closeModal();
            this.setView('documents');
        };
    },

    showSendMessageModal() {
        this.showModal('Отправка сообщения', `
            <form id="msg-form">
                <div class="form-group">
                    <label>ID получателя (оставьте пустым для всех)</label>
                    <input type="text" id="msg-to" placeholder="all">
                </div>
                <div class="form-group">
                    <label>Тема</label>
                    <input type="text" id="msg-subject" required>
                </div>
                <div class="form-group">
                    <label>Текст</label>
                    <textarea id="msg-body" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Отправить</button>
            </form>
        `);
        document.getElementById('msg-form').onsubmit = async (e) => {
            e.preventDefault();
            await this.apiFetch('api/messages.php?action=send', {
                method: 'POST',
                body: JSON.stringify({
                    to: document.getElementById('msg-to').value || 'all',
                    subject: document.getElementById('msg-subject').value,
                    body: document.getElementById('msg-body').value
                })
            });
            this.closeModal();
            this.setView('messages');
        };
    },

    showCreateUserModal() {
        this.showModal('Новый пользователь', `
            <form id="user-form">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" id="user-username" required>
                </div>
                <div class="form-group">
                    <label>Пароль</label>
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
                    role: document.getElementById('user-role').value
                })
            });
            this.closeModal();
            this.setView('users');
        };
    },

    async deleteDocument(id) {
        if (confirm('Удалить документ?')) {
            await this.apiFetch(`api/documents.php?action=delete&id=${id}`);
            this.setView('documents');
        }
    },

    async deleteUser(id) {
        if (confirm('Удалить пользователя?')) {
            await this.apiFetch(`api/users.php?action=delete&id=${id}`);
            this.setView('users');
        }
    }
};

App.init();
