const App = {
    state: {
        user: null,
        settings: null
    },

    async init() {
        const token = localStorage.getItem('token');
        if (token) {
            try {
                this.state.user = await API.auth.me();
                this.state.settings = await API.admin.getSettings();
                this.showMain();
                this.handleRouting();
            } catch (e) {
                console.error('Init error:', e);
                this.showLogin();
            }
        } else {
            this.showLogin();
        }

        window.addEventListener('hashchange', () => this.handleRouting());
    },

    showLoader() {
        document.getElementById('loader').classList.remove('d-none');
        document.getElementById('main-container').classList.add('d-none');
    },

    hideLoader() {
        document.getElementById('loader').classList.add('d-none');
        document.getElementById('main-container').classList.remove('d-none');
    },

    showLogin() {
        const container = document.getElementById('main-container');
        container.innerHTML = `
            <div class="d-flex justify-content-center align-items-center vh-100">
                <div class="card p-4" style="width: 400px;">
                    <h3 class="text-center mb-4">Вход в CRM ХОП</h3>
                    <form id="login-form">
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" class="form-control" name="login" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div id="login-error" class="alert alert-danger d-none"></div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                </div>
            </div>
        `;
        this.hideLoader();

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await API.auth.login(formData.get('login'), formData.get('password'));
                localStorage.setItem('token', response.token);
                this.state.user = response.user;
                this.state.settings = await API.admin.getSettings();
                this.showMain();
                window.location.hash = '#dashboard';
            } catch (error) {
                const errEl = document.getElementById('login-error');
                errEl.textContent = error.message;
                errEl.classList.remove('d-none');
            }
        });
    },

    showMain() {
        const container = document.getElementById('main-container');
        container.innerHTML = `
            <div class="d-flex">
                <div class="sidebar bg-dark text-white p-3">
                    <h4 class="mb-4">CRM ХОП</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#dashboard"><i class="bi bi-speedometer2 me-2"></i>Дашборд</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#requests"><i class="bi bi-list-task me-2"></i>Заявки</a>
                        </li>
                        ${this.state.user.role === 'admin' || this.state.user.role === 'head' ? `
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#admin"><i class="bi bi-gear me-2"></i>Админ</a>
                        </li>
                        ` : ''}
                        <li class="nav-item mt-auto">
                            <a class="nav-link text-white" href="#profile"><i class="bi bi-person me-2"></i>Профиль</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#" id="logout-btn"><i class="bi bi-box-arrow-right me-2"></i>Выход</a>
                        </li>
                    </ul>
                </div>
                <div class="main-content flex-grow-1" id="content-area"></div>
            </div>
        `;
        this.hideLoader();

        document.getElementById('logout-btn').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('token');
            window.location.reload();
        });
    },

    async handleRouting() {
        const hash = window.location.hash || '#dashboard';
        const contentArea = document.getElementById('content-area');
        if (!contentArea) return;

        this.showLoader();

        switch (hash) {
            case '#dashboard':
                await this.renderDashboard();
                break;
            case '#requests':
                await this.renderRequests();
                break;
            case '#admin':
                await this.renderAdmin();
                break;
            case '#profile':
                await this.renderProfile();
                break;
            default:
                if (hash.startsWith('#request/')) {
                    const id = hash.split('/')[1];
                    await this.renderRequestDetails(id);
                } else {
                    await this.renderDashboard();
                }
        }

        this.hideLoader();
    },

    async renderDashboard() {
        const contentArea = document.getElementById('content-area');
        const stats = await API.analytics.getStats();

        contentArea.innerHTML = `
            <h2>Дашборд аналитики</h2>
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>Всего новых</h5>
                        <h2 class="text-primary">${stats.by_status.new}</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>В работе</h5>
                        <h2 class="text-warning">${stats.by_status.in_work}</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>Выполнено</h5>
                        <h2 class="text-success">${stats.by_status.completed}</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center">
                        <h5>Просрочено</h5>
                        <h2 class="text-danger">${stats.sla_stats.overdue}</h2>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5>Статусы заявок</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5>Нагрузка на исполнителей</h5>
                        <canvas id="loadChart"></canvas>
                    </div>
                </div>
            </div>
        `;

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(stats.by_status),
                datasets: [{
                    data: Object.values(stats.by_status),
                    backgroundColor: ['#0d6efd', '#6f42c1', '#fd7e14', '#198754', '#dc3545']
                }]
            }
        });

        new Chart(document.getElementById('loadChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(stats.executors_load),
                datasets: [{
                    label: 'Активные заявки',
                    data: Object.values(stats.executors_load),
                    backgroundColor: '#0d6efd'
                }]
            }
        });
    },

    async renderRequests() {
        const contentArea = document.getElementById('content-area');
        const requests = await API.requests.list();

        contentArea.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Список заявок</h2>
                <div>
                    ${this.state.user.role === 'admin' || this.state.user.role === 'head' ? `
                        <a href="/api/requests.php?action=export&token=${localStorage.getItem('token')}" class="btn btn-outline-success me-2">Экспорт CSV</a>
                    ` : ''}
                    <button class="btn btn-primary" id="new-request-btn">Создать заявку</button>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" id="search-input" class="form-control" placeholder="Поиск по названию...">
                        </div>
                        <div class="col-md-3">
                            <select id="status-filter" class="form-select">
                                <option value="">Все статусы</option>
                                <option value="new">Новая</option>
                                <option value="assigned">Назначена</option>
                                <option value="in_work">В работе</option>
                                <option value="completed">Выполнено</option>
                                <option value="rejected">Отклонено</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Приоритет</th>
                            <th>Статус</th>
                            <th>Создана</th>
                            <th>Дедлайн</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="requests-table-body">
                        ${this.renderRequestRows(requests)}
                    </tbody>
                </table>
            </div>
        `;

        document.getElementById('new-request-btn').onclick = () => this.showNewRequestModal();

        const filterFn = () => {
            const search = document.getElementById('search-input').value.toLowerCase();
            const status = document.getElementById('status-filter').value;
            const filtered = requests.filter(r =>
                (r.title.toLowerCase().includes(search)) &&
                (status === '' || r.status === status)
            );
            document.getElementById('requests-table-body').innerHTML = this.renderRequestRows(filtered);
        };

        document.getElementById('search-input').oninput = filterFn;
        document.getElementById('status-filter').onchange = filterFn;
    },

    renderRequestRows(requests) {
        if (requests.length === 0) return '<tr><td colspan="7" class="text-center p-4">Заявки не найдены</td></tr>';
        return requests.map(r => `
            <tr>
                <td>${r.id.substr(-6)}</td>
                <td><a href="#request/${r.id}">${this.escapeHTML(r.title)}</a></td>
                <td><span class="priority-badge priority-${r.priority}"></span>${r.priority}</td>
                <td><span class="status-badge status-${r.status}">${this.getStatusLabel(r.status)}</span></td>
                <td>${new Date(r.created_at).toLocaleDateString()}</td>
                <td class="${new Date(r.deadline) < new Date() && r.status !== 'completed' ? 'text-danger fw-bold' : ''}">
                    ${new Date(r.deadline).toLocaleString()}
                </td>
                <td>
                    <a href="#request/${r.id}" class="btn btn-sm btn-outline-primary">Открыть</a>
                </td>
            </tr>
        `).join('');
    },

    getStatusLabel(status) {
        const labels = {
            'new': 'Новая',
            'assigned': 'Назначена',
            'in_work': 'В работе',
            'completed': 'Выполнено',
            'rejected': 'Отклонено'
        };
        return labels[status] || status;
    },

    escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    async showNewRequestModal() {
        const categories = this.state.settings.categories;
        const priorities = this.state.settings.priorities;
        const customFields = this.state.settings.form_config;

        const modalHtml = `
            <div class="modal fade" id="newRequestModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Новая заявка</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="new-request-form">
                                <div class="mb-3">
                                    <label class="form-label">Название</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Категория</label>
                                        <select class="form-select" name="category">
                                            ${categories.map(c => `<option value="${c}">${c}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Приоритет</label>
                                        <select class="form-select" name="priority">
                                            ${priorities.map(p => `<option value="${p}" ${p === 'Средний' ? 'selected' : ''}>${p}</option>`).join('')}
                                        </select>
                                    </div>
                                </div>
                                ${customFields.map(f => `
                                    <div class="mb-3">
                                        <label class="form-label">${f.label}</label>
                                        ${f.type === 'textarea'
                                            ? `<textarea class="form-control" name="custom_${f.id}" ${f.required ? 'required' : ''}></textarea>`
                                            : `<input type="text" class="form-control" name="custom_${f.id}" ${f.required ? 'required' : ''}>`
                                        }
                                    </div>
                                `).join('')}
                                <div class="mb-3">
                                    <label class="form-label">Прикрепить файл</label>
                                    <input type="file" class="form-control" id="request-file">
                                </div>
                                <div id="file-list" class="mb-3"></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" form="new-request-form" class="btn btn-primary">Создать</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('modals-container').innerHTML = modalHtml;
        const modal = new bootstrap.Modal(document.getElementById('newRequestModal'));
        modal.show();

        const attachments = [];
        document.getElementById('request-file').onchange = async (e) => {
            const file = e.target.files[0];
            if (file) {
                try {
                    const res = await API.upload(file);
                    attachments.push(res);
                    document.getElementById('file-list').innerHTML += `<div class="badge bg-secondary me-2">${res.original_name}</div>`;
                } catch (err) {
                    alert(err.message);
                }
            }
        };

        document.getElementById('new-request-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const customData = {};
            customFields.forEach(f => {
                customData[f.id] = formData.get(`custom_${f.id}`);
            });

            try {
                await API.requests.create({
                    title: formData.get('title'),
                    category: formData.get('category'),
                    priority: formData.get('priority'),
                    description: customData[customFields[0]?.id] || '', // Use first custom field as main desc
                    custom_fields: customData,
                    attachments: attachments
                });
                modal.hide();
                this.renderRequests();
            } catch (err) {
                alert(err.message);
            }
        };
    },

    async renderRequestDetails(id) {
        const contentArea = document.getElementById('content-area');
        const req = await API.requests.get(id);
        const users = await API.admin.getUsers();
        const executors = users.filter(u => u.role === 'executor');

        contentArea.innerHTML = `
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#requests">Заявки</a></li>
                    <li class="breadcrumb-item active">${req.id.substr(-6)}</li>
                </ol>
            </nav>
            <div class="row">
                <div class="col-md-8">
                    <div class="card p-4 mb-4">
                        <h3>${this.escapeHTML(req.title)}</h3>
                        <p class="text-muted">Создана: ${new Date(req.created_at).toLocaleString()} | Автор: ${req.creator_name || 'System'}</p>
                        <hr>
                        <h5>Описание</h5>
                        <p>${this.escapeHTML(req.description) || 'Нет описания'}</p>

                        ${req.attachments && req.attachments.length > 0 ? `
                            <h5 class="mt-4">Вложения</h5>
                            <div class="list-group">
                                ${req.attachments.map(a => `<a href="${a.url}" class="list-group-item list-group-item-action" target="_blank"><i class="bi bi-file-earmark me-2"></i>${a.original_name}</a>`).join('')}
                            </div>
                        ` : ''}
                    </div>

                    <div class="card p-4">
                        <h5>Комментарии</h5>
                        <div id="comments-list" class="mb-4">
                            ${req.comments.map(c => `
                                <div class="border-bottom mb-3 pb-2">
                                    <div class="d-flex justify-content-between">
                                        <strong>${c.user_name}</strong>
                                        <small class="text-muted">${new Date(c.created_at).toLocaleString()}</small>
                                    </div>
                                    <p class="mb-0 mt-1">${this.escapeHTML(c.text)}</p>
                                </div>
                            `).join('')}
                            ${req.comments.length === 0 ? '<p class="text-muted">Комментариев пока нет</p>' : ''}
                        </div>
                        <form id="comment-form">
                            <div class="mb-3">
                                <textarea class="form-control" name="text" placeholder="Ваш комментарий..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Отправить</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 mb-4">
                        <h5>Статус</h5>
                        <div class="mb-3">
                            <span class="status-badge status-${req.status} d-block text-center p-2">${this.getStatusLabel(req.status)}</span>
                        </div>
                        ${this.state.user.role !== 'employee' ? `
                            <div class="mb-3">
                                <label class="form-label">Изменить статус</label>
                                <select class="form-select" id="status-update-select">
                                    <option value="new" ${req.status === 'new' ? 'selected' : ''}>Новая</option>
                                    <option value="assigned" ${req.status === 'assigned' ? 'selected' : ''}>Назначена</option>
                                    <option value="in_work" ${req.status === 'in_work' ? 'selected' : ''}>В работе</option>
                                    <option value="completed" ${req.status === 'completed' ? 'selected' : ''}>Выполнено</option>
                                    <option value="rejected" ${req.status === 'rejected' ? 'selected' : ''}>Отклонено</option>
                                </select>
                            </div>
                        ` : ''}
                        <hr>
                        <h5>Приоритет</h5>
                        <p><span class="priority-badge priority-${req.priority}"></span> ${req.priority}</p>
                        <hr>
                        <h5>Дедлайн (SLA)</h5>
                        <p class="${new Date(req.deadline) < new Date() && req.status !== 'completed' ? 'text-danger fw-bold' : ''}">${new Date(req.deadline).toLocaleString()}</p>
                        <hr>
                        <h5>Исполнитель</h5>
                        ${this.state.user.role === 'admin' || this.state.user.role === 'head' ? `
                            <select class="form-select" id="executor-assign-select">
                                <option value="">Не назначен</option>
                                ${executors.map(e => `<option value="${e.id}" ${req.executor_id === e.id ? 'selected' : ''}>${e.name}</option>`).join('')}
                            </select>
                        ` : `
                            <p>${executors.find(e => e.id === req.executor_id)?.name || 'Не назначен'}</p>
                        `}
                    </div>
                </div>
            </div>
        `;

        const statusSelect = document.getElementById('status-update-select');
        if (statusSelect) {
            statusSelect.onchange = async (e) => {
                await API.requests.updateStatus(req.id, e.target.value);
                this.renderRequestDetails(req.id);
            };
        }

        const executorSelect = document.getElementById('executor-assign-select');
        if (executorSelect) {
            executorSelect.onchange = async (e) => {
                await API.requests.assign(req.id, e.target.value);
                this.renderRequestDetails(req.id);
            };
        }

        document.getElementById('comment-form').onsubmit = async (e) => {
            e.preventDefault();
            const text = new FormData(e.target).get('text');
            await API.requests.addComment({ request_id: req.id, text });
            this.renderRequestDetails(req.id);
        };
    },

    async renderAdmin() {
        const contentArea = document.getElementById('content-area');
        const users = await API.admin.getUsers();
        const settings = await API.admin.getSettings();

        contentArea.innerHTML = `
            <h2>Администрирование</h2>
            <ul class="nav nav-tabs mt-4" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#users-tab">Пользователи</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-tab">Настройки и SLA</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#board-tab">Доска назначения</button>
                </li>
            </ul>
            <div class="tab-content p-4 bg-white border border-top-0 rounded-bottom">
                <div class="tab-pane fade show active" id="users-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h4>Управление пользователями</h4>
                        <button class="btn btn-sm btn-primary" id="add-user-btn">Добавить пользователя</button>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Логин</th>
                                <th>Имя</th>
                                <th>Роль</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${users.map(u => `
                                <tr>
                                    <td>${u.login}</td>
                                    <td>${u.name}</td>
                                    <td>${u.role}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger delete-user" data-id="${u.id}">Удалить</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="settings-tab">
                    <h4>Системные настройки</h4>
                    <form id="settings-form">
                        <div class="mb-3">
                            <label class="form-label">Категории (через запятую)</label>
                            <input type="text" class="form-control" name="categories" value="${settings.categories.join(', ')}">
                        </div>
                        <h5>SLA (часов на выполнение)</h5>
                        <div class="row">
                            ${Object.entries(settings.sla).map(([p, h]) => `
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">${p}</label>
                                    <input type="number" class="form-control" name="sla_${p}" value="${h}">
                                </div>
                            `).join('')}
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                    <hr>
                    <h4>Режим хранения данных</h4>
                    <div class="alert alert-info">
                        Текущий режим: <strong>${this.state.settings.storage_mode.toUpperCase()}</strong>
                    </div>
                    <button class="btn btn-warning" id="toggle-storage-btn">Переключить на MySQL (DEMO)</button>
                </div>
                <div class="tab-pane fade" id="board-tab">
                    <h4>Назначение задач (Drag-and-Drop)</h4>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <h6>Новые заявки</h6>
                            <div id="new-requests-list" class="drop-zone"></div>
                        </div>
                        <div class="col-md-8">
                            <h6>Исполнители</h6>
                            <div class="row" id="executors-lists"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.initAdminFeatures(users, settings);
    },

    initAdminFeatures(users, settings) {
        // Add user
        document.getElementById('add-user-btn').onclick = () => {
            const modalHtml = `
                <div class="modal fade" id="addUserModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Добавить пользователя</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="add-user-form">
                                    <div class="mb-3">
                                        <label class="form-label">Логин</label>
                                        <input type="text" class="form-control" name="login" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Имя</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Пароль</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Роль</label>
                                        <select class="form-select" name="role">
                                            <option value="employee">Сотрудник</option>
                                            <option value="executor">Исполнитель</option>
                                            <option value="head">Начальник</option>
                                            <option value="admin">Админ</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" form="add-user-form" class="btn btn-primary">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('modals-container').innerHTML = modalHtml;
            const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
            modal.show();

            document.getElementById('add-user-form').onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                await API.admin.saveUser(Object.fromEntries(formData));
                modal.hide();
                this.renderAdmin();
            };
        };

        // User deletion
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.onclick = async () => {
                if (confirm('Вы уверены?')) {
                    await API.admin.deleteUser(btn.dataset.id);
                    this.renderAdmin();
                }
            };
        });

        // Toggle storage
        document.getElementById('toggle-storage-btn').onclick = async () => {
            const newMode = this.state.settings.storage_mode === 'json' ? 'mysql' : 'json';
            if (newMode === 'mysql') {
                const host = prompt('MySQL Host:', 'localhost');
                const dbname = prompt('Database Name:', 'crm_hop');
                const user = prompt('User:', 'root');
                const pass = prompt('Password:', '');
                if (host && dbname && user) {
                    await API.admin.setStorageMode('mysql', { host, dbname, user, pass });
                    alert('Режим MySQL активирован');
                }
            } else {
                await API.admin.setStorageMode('json');
                alert('Режим JSON активирован');
            }
            window.location.reload();
        };

        // Settings save
        document.getElementById('settings-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const newSettings = {
                categories: formData.get('categories').split(',').map(s => s.trim()),
                sla: {}
            };
            settings.priorities.forEach(p => {
                newSettings.sla[p] = parseInt(formData.get(`sla_${p}`));
            });
            await API.admin.saveSettings(newSettings);
            alert('Настройки сохранены');
            this.state.settings = await API.admin.getSettings();
        };

        // Drag and Drop
        this.initDragAndDrop(users);
    },

    async initDragAndDrop(users) {
        const requests = await API.requests.list();
        const newReqs = requests.filter(r => r.status === 'new');
        const newReqsList = document.getElementById('new-requests-list');

        newReqsList.innerHTML = newReqs.map(r => `
            <div class="card p-2 mb-2 draggable-request" data-id="${r.id}">
                <small>${r.id.substr(-6)}</small>
                <div>${this.escapeHTML(r.title)}</div>
            </div>
        `).join('');

        new Sortable(newReqsList, {
            group: 'requests',
            animation: 150
        });

        const executors = users.filter(u => u.role === 'executor');
        const executorsContainer = document.getElementById('executors-lists');

        executorsContainer.innerHTML = executors.map(e => `
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">${e.name}</div>
                    <div class="card-body drop-zone" data-executor-id="${e.id}" id="list-${e.id}">
                        ${requests.filter(r => r.executor_id === e.id && r.status !== 'completed').map(r => `
                            <div class="card p-2 mb-2 draggable-request" data-id="${r.id}">
                                <small>${r.id.substr(-6)}</small>
                                <div>${this.escapeHTML(r.title)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `).join('');

        executors.forEach(e => {
            new Sortable(document.getElementById(`list-${e.id}`), {
                group: 'requests',
                animation: 150,
                onAdd: async (evt) => {
                    const requestId = evt.item.dataset.id;
                    const executorId = e.id;
                    await API.requests.assign(requestId, executorId);
                }
            });
        });
    },

    async renderProfile() {
        const contentArea = document.getElementById('content-area');
        const user = await API.auth.me();
        contentArea.innerHTML = `
            <h2>Профиль пользователя</h2>
            <div class="card p-4 mt-4" style="max-width: 600px;">
                <form id="profile-form">
                    <div class="mb-3">
                        <label class="form-label">Имя</label>
                        <input type="text" class="form-control" name="name" value="${user.name}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="${user.email || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Новый пароль (оставьте пустым, если не хотите менять)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <div id="profile-msg" class="alert alert-success d-none">Профиль обновлен</div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
        `;

        document.getElementById('profile-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                await API.auth.updateProfile({
                    name: formData.get('name'),
                    email: formData.get('email'),
                    password: formData.get('password')
                });
                document.getElementById('profile-msg').classList.remove('d-none');
            } catch (error) {
                alert(error.message);
            }
        });
    }
};

window.onload = () => App.init();
