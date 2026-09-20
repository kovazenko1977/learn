// CRM Single Page Application Engine

const AppState = {
    currentUser: null,
    token: localStorage.getItem('crm_token') || null,
    currentView: 'dashboard',
    theme: localStorage.getItem('crm_theme') || 'light',
    lang: localStorage.getItem('crm_lang') || 'ru',
    settings: {},
    users: [],
    requests: [],
    formFields: [],
    analytics: null,
    searchQuery: '',
    statusFilter: 'all',
    categoryFilter: 'all',
    selectedRequests: []
};

// Translations Dictionary
const i18n = {
    ru: {
        appTitle: "CRM Обслуживание",
        dashboard: "Дашборд",
        requests: "Заявки",
        kanban: "Канбан доска",
        formBuilder: "Конструктор форм",
        users: "Пользователи",
        settings: "Настройки",
        profile: "Профиль",
        logout: "Выйти",
        loginTitle: "Вход в систему",
        loginSubtitle: "Управление заявками на ремонт и обслуживание",
        username: "Логин",
        password: "Пароль",
        loginBtn: "Войти",
        forgotPassword: "Забыли пароль?",
        totalRequests: "Всего заявок",
        newRequests: "Новые",
        inProgress: "В работе",
        completed: "Выполнено",
        avgTime: "Среднее время (ч)",
        slaCompliance: "SLA контроль",
        createRequest: "Создать заявку",
        exportExcel: "Экспорт в Excel",
        exportPdf: "Печать / PDF",
        searchPlaceholder: "Поиск по названию или описанию...",
        status: "Статус",
        category: "Категория",
        priority: "Приоритет",
        executor: "Исполнитель",
        author: "Автор",
        actions: "Действия",
        batchAssign: "Массовое назначение",
        noRequests: "Заявки не найдены",
        save: "Сохранить",
        cancel: "Отмена",
        delete: "Удалить",
        add: "Добавить"
    },
    en: {
        appTitle: "Maintenance CRM",
        dashboard: "Dashboard",
        requests: "Tickets",
        kanban: "Kanban Board",
        formBuilder: "Form Builder",
        users: "Users",
        settings: "Settings",
        profile: "Profile",
        logout: "Logout",
        loginTitle: "System Login",
        loginSubtitle: "Maintenance & Request Management System",
        username: "Username",
        password: "Password",
        loginBtn: "Sign In",
        forgotPassword: "Forgot password?",
        totalRequests: "Total Tickets",
        newRequests: "New",
        inProgress: "In Progress",
        completed: "Completed",
        avgTime: "Avg Time (h)",
        slaCompliance: "SLA Rate",
        createRequest: "New Ticket",
        exportExcel: "Export Excel",
        exportPdf: "Print / PDF",
        searchPlaceholder: "Search tickets...",
        status: "Status",
        category: "Category",
        priority: "Priority",
        executor: "Executor",
        author: "Author",
        actions: "Actions",
        batchAssign: "Batch Assign",
        noRequests: "No tickets found",
        save: "Save",
        cancel: "Cancel",
        delete: "Delete",
        add: "Add"
    }
};

function t(key) {
    return i18n[AppState.lang][key] || key;
}

// API Fetch Helper
async function apiFetch(endpoint, options = {}) {
    options.headers = options.headers || {};
    options.headers['Content-Type'] = 'application/json';
    if (AppState.token) {
        options.headers['Authorization'] = `Bearer ${AppState.token}`;
    }

    try {
        const response = await fetch(`api/${endpoint}`, options);
        if (response.status === 401 && endpoint !== 'auth/login') {
            AppState.token = null;
            localStorage.removeItem('crm_token');
            renderApp();
            return null;
        }
        return await response.json();
    } catch (err) {
        console.error('API Error:', err);
        return { error: 'Ошибка соединения с сервером' };
    }
}

// Notification Alert
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.padding = '12px 20px';
    alertDiv.style.borderRadius = '8px';
    alertDiv.style.background = type === 'error' ? '#ef4444' : '#10b981';
    alertDiv.style.color = '#ffffff';
    alertDiv.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.3)';
    alertDiv.style.fontWeight = '600';
    alertDiv.textContent = message;

    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3500);
}

// Initialize System
document.addEventListener('DOMContentLoaded', async () => {
    document.documentElement.setAttribute('data-theme', AppState.theme);

    if (AppState.token) {
        const profileRes = await apiFetch('profile');
        if (profileRes && profileRes.success) {
            AppState.currentUser = profileRes.user;
            await loadInitialData();
        } else {
            AppState.token = null;
            localStorage.removeItem('crm_token');
        }
    }
    renderApp();
});

async function loadInitialData() {
    const [settingsRes, reqsRes, usersRes, fieldsRes, analyticsRes] = await Promise.all([
        apiFetch('settings'),
        apiFetch('requests'),
        apiFetch('users'),
        apiFetch('form-fields'),
        apiFetch('analytics')
    ]);

    if (settingsRes && settingsRes.success) AppState.settings = settingsRes.settings;
    if (reqsRes && reqsRes.success) AppState.requests = reqsRes.requests;
    if (usersRes && usersRes.success) AppState.users = usersRes.users;
    if (fieldsRes && fieldsRes.success) AppState.formFields = fieldsRes.fields;
    if (analyticsRes && analyticsRes.success) AppState.analytics = analyticsRes.analytics;
}

// Global Main Render Function
function renderApp() {
    const appEl = document.getElementById('app');
    if (!AppState.token || !AppState.currentUser) {
        appEl.innerHTML = renderLoginView();
        bindLoginEvents();
        return;
    }

    appEl.innerHTML = `
        <div class="sidebar">
            <div class="sidebar-header">
                <span class="sidebar-brand">🔧 ${t('appTitle')}</span>
            </div>
            <ul class="nav-list">
                <li class="nav-item ${AppState.currentView === 'dashboard' ? 'active' : ''}" onclick="navigateTo('dashboard')">
                    📊 <span class="nav-text">${t('dashboard')}</span>
                </li>
                <li class="nav-item ${AppState.currentView === 'requests' ? 'active' : ''}" onclick="navigateTo('requests')">
                    📋 <span class="nav-text">${t('requests')}</span>
                </li>
                <li class="nav-item ${AppState.currentView === 'kanban' ? 'active' : ''}" onclick="navigateTo('kanban')">
                    🗂️ <span class="nav-text">${t('kanban')}</span>
                </li>
                ${AppState.currentUser.role === 'admin' ? `
                    <li class="nav-item ${AppState.currentView === 'formBuilder' ? 'active' : ''}" onclick="navigateTo('formBuilder')">
                        📐 <span class="nav-text">${t('formBuilder')}</span>
                    </li>
                    <li class="nav-item ${AppState.currentView === 'users' ? 'active' : ''}" onclick="navigateTo('users')">
                        👥 <span class="nav-text">${t('users')}</span>
                    </li>
                    <li class="nav-item ${AppState.currentView === 'settings' ? 'active' : ''}" onclick="navigateTo('settings')">
                        ⚙️ <span class="nav-text">${t('settings')}</span>
                    </li>
                ` : ''}
            </ul>
            <div class="sidebar-footer">
                <div style="font-size: 0.8125rem; color: #94a3b8; margin-bottom: 8px; cursor: pointer;" onclick="openProfileModal()">
                    👤 <strong>${AppState.currentUser.full_name}</strong> (${AppState.currentUser.role})
                </div>
                <button class="btn btn-secondary" style="width: 100%; justify-content: center;" onclick="handleLogout()">
                    🚪 ${t('logout')}
                </button>
            </div>
        </div>
        <div class="main-wrapper">
            <div class="top-bar">
                <div class="top-title">${t(AppState.currentView)}</div>
                <div class="top-actions">
                    <button class="btn btn-secondary btn-icon" title="Переключить тему" onclick="toggleTheme()">
                        ${AppState.theme === 'dark' ? '☀️ Light' : '🌙 Dark'}
                    </button>
                    <button class="btn btn-secondary btn-icon" onclick="toggleLang()">
                        🌐 ${AppState.lang.toUpperCase()}
                    </button>
                    <button class="btn btn-secondary btn-icon" onclick="openProfileModal()" title="Профиль">
                        👤 ${t('profile')}
                    </button>
                    <button class="btn btn-primary" onclick="openCreateRequestModal()">
                        ➕ ${t('createRequest')}
                    </button>
                </div>
            </div>
            <div class="content-area">
                ${renderCurrentView()}
            </div>
        </div>
    `;

    bindViewEvents();
}

async function navigateTo(view) {
    AppState.currentView = view;
    await loadInitialData();
    renderApp();
}

function toggleTheme() {
    AppState.theme = AppState.theme === 'light' ? 'dark' : 'light';
    localStorage.setItem('crm_theme', AppState.theme);
    document.documentElement.setAttribute('data-theme', AppState.theme);
    renderApp();
}

function toggleLang() {
    AppState.lang = AppState.lang === 'ru' ? 'en' : 'ru';
    localStorage.setItem('crm_lang', AppState.lang);
    renderApp();
}

function handleLogout() {
    AppState.token = null;
    AppState.currentUser = null;
    localStorage.removeItem('crm_token');
    renderApp();
}

// PROFILE MODAL
function openProfileModal() {
    const u = AppState.currentUser;
    const modalHtml = `
        <div class="modal-overlay" id="profileModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>👤 Настройка профиля пользователя</h3>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('profileModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>ФИО</label>
                        <input type="text" id="profFullName" class="form-control" value="${u.full_name || ''}">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="profEmail" class="form-control" value="${u.email || ''}">
                    </div>
                    <div class="form-group">
                        <label>Отдел</label>
                        <input type="text" id="profDept" class="form-control" value="${u.department || ''}">
                    </div>
                    <h4 style="margin-top: 1.5rem; margin-bottom: 0.75rem;">Смена пароля</h4>
                    <div class="form-group">
                        <label>Текущий пароль</label>
                        <input type="password" id="profCurPass" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Новый пароль</label>
                        <input type="password" id="profNewPass" class="form-control" placeholder="••••••••">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('profileModal')">Отмена</button>
                    <button class="btn btn-primary" onclick="submitUpdateProfile()">Сохранить изменения</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function submitUpdateProfile() {
    const full_name = document.getElementById('profFullName').value;
    const email = document.getElementById('profEmail').value;
    const department = document.getElementById('profDept').value;
    const current_password = document.getElementById('profCurPass').value;
    const new_password = document.getElementById('profNewPass').value;

    const payload = { full_name, email, department };
    if (new_password) {
        payload.current_password = current_password;
        payload.new_password = new_password;
    }

    const res = await apiFetch('profile', {
        method: 'PUT',
        body: JSON.stringify(payload)
    });

    if (res && res.success) {
        showAlert('Профиль сохранен', 'info');
        AppState.currentUser = res.user;
        closeModal('profileModal');
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

// 1. LOGIN & FORGOT PASSWORD VIEW
function renderLoginView() {
    return `
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-header">
                    <h1>⚙️ ${t('loginTitle')}</h1>
                    <p>${t('loginSubtitle')}</p>
                </div>
                <form id="loginForm">
                    <div class="form-group">
                        <label>${t('username')}</label>
                        <input type="text" id="loginUsername" class="form-control" required placeholder="admin / head / exec / resp">
                    </div>
                    <div class="form-group">
                        <label>${t('password')}</label>
                        <input type="password" id="loginPassword" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                        ${t('loginBtn')}
                    </button>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="#" onclick="openForgotPasswordModal(); return false;" style="color: var(--accent-color); font-size: 0.875rem; text-decoration: none;">
                        ${t('forgotPassword')}
                    </a>
                </div>
            </div>
        </div>
    `;
}

function bindLoginEvents() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;

            const res = await apiFetch('auth/login', {
                method: 'POST',
                body: JSON.stringify({ username, password })
            });

            if (res && res.success) {
                AppState.token = res.user.token;
                AppState.currentUser = res.user;
                localStorage.setItem('crm_token', res.user.token);
                await loadInitialData();
                renderApp();
            } else {
                showAlert(res?.error || 'Ошибка входа', 'error');
            }
        });
    }
}

function openForgotPasswordModal() {
    const modalHtml = `
        <div class="modal-overlay" id="forgotModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Восстановление пароля</h3>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('forgotModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Имя пользователя или Email</label>
                        <input type="text" id="forgotIdentifier" class="form-control" placeholder="admin@crm.local">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('forgotModal')">Отмена</button>
                    <button class="btn btn-primary" onclick="submitForgotPassword()">Сгенерировать сброс</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function submitForgotPassword() {
    const identifier = document.getElementById('forgotIdentifier').value;
    if (!identifier) return showAlert('Укажите логин или e-mail', 'error');

    const res = await apiFetch('auth/forgot-password', {
        method: 'POST',
        body: JSON.stringify({ identifier })
    });

    if (res && res.success) {
        showAlert(`Токен сброса: ${res.reset_token}`, 'info');
        closeModal('forgotModal');
    } else {
        showAlert(res?.error || 'Пользователь не найден', 'error');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.remove();
}

// 2. DASHBOARD VIEW
function renderDashboardView() {
    const a = AppState.analytics || {
        by_status: { new: 0, assigned: 0, in_progress: 0, completed: 0, total: 0 },
        avg_completion_hours: 0,
        sla_compliance_rate: 100,
        workload: []
    };

    return `
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">${t('totalRequests')}</div>
                <div class="kpi-value">${a.by_status.total || 0}</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
                <div class="kpi-title">${t('newRequests')}</div>
                <div class="kpi-value">${a.by_status.new || 0}</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #6366f1;">
                <div class="kpi-title">${t('inProgress')}</div>
                <div class="kpi-value">${(a.by_status.assigned || 0) + (a.by_status.in_progress || 0)}</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #10b981;">
                <div class="kpi-title">${t('completed')}</div>
                <div class="kpi-value">${a.by_status.completed || 0}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">${t('avgTime')}</div>
                <div class="kpi-value">${a.avg_completion_hours} ч</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
                <div class="kpi-title">${t('slaCompliance')}</div>
                <div class="kpi-value">${a.sla_compliance_rate}%</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="card">
                <div class="card-header">
                    <h3>👥 Нагрузка на сотрудников</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Сотрудник</th>
                                <th>Назначено</th>
                                <th>В работе</th>
                                <th>Завершено</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${a.workload.map(w => `
                                <tr>
                                    <td><strong>${w.full_name}</strong></td>
                                    <td><span class="badge badge-assigned">${w.assigned}</span></td>
                                    <td><span class="badge badge-in_progress">${w.in_progress}</span></td>
                                    <td><span class="badge badge-completed">${w.completed}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📌 Распределение по приоритетам</h3>
                </div>
                <div class="card-body">
                    ${Object.entries(a.by_priority || {}).map(([p, count]) => `
                        <div style="margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 4px;">
                                <span>${p}</span>
                                <strong>${count}</strong>
                            </div>
                            <div style="width: 100%; background: var(--border-color); height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="width: ${a.by_status.total ? (count / a.by_status.total) * 100 : 0}%; background: var(--accent-color); height: 100%;"></div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

// 3. REQUESTS TABLE VIEW
function renderRequestsView() {
    const userMap = {};
    AppState.users.forEach(u => userMap[u.id] = u.full_name);

    let filtered = AppState.requests.filter(r => {
        if (AppState.statusFilter !== 'all' && r.status !== AppState.statusFilter) return false;
        if (AppState.categoryFilter !== 'all' && r.category !== AppState.categoryFilter) return false;
        if (AppState.searchQuery) {
            const q = AppState.searchQuery.toLowerCase();
            const title = (r.title || '').toLowerCase();
            const desc = (r.description || '').toLowerCase();
            if (!title.includes(q) && !desc.includes(q) && !r.id.includes(q)) return false;
        }
        return true;
    });

    const isHeadOrAdmin = ['admin', 'head'].includes(AppState.currentUser.role);

    return `
        <div class="card">
            <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 0.75rem; flex-grow: 1;">
                    <input type="text" class="form-control" style="max-width: 280px;" placeholder="${t('searchPlaceholder')}" value="${AppState.searchQuery}" oninput="updateSearch(this.value)">
                    <select class="form-control" style="max-width: 160px;" onchange="updateStatusFilter(this.value)">
                        <option value="all">Все статусы</option>
                        <option value="new" ${AppState.statusFilter === 'new' ? 'selected' : ''}>Новая</option>
                        <option value="assigned" ${AppState.statusFilter === 'assigned' ? 'selected' : ''}>Назначена</option>
                        <option value="in_progress" ${AppState.statusFilter === 'in_progress' ? 'selected' : ''}>В работе</option>
                        <option value="completed" ${AppState.statusFilter === 'completed' ? 'selected' : ''}>Выполнено</option>
                        <option value="rejected" ${AppState.statusFilter === 'rejected' ? 'selected' : ''}>Отклонено</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    ${isHeadOrAdmin ? `
                        <button class="btn btn-secondary" onclick="openBatchAssignModal()">👥 ${t('batchAssign')}</button>
                    ` : ''}
                    <a href="api/export?token=${AppState.token}" target="_blank" class="btn btn-secondary">📥 ${t('exportExcel')}</a>
                    <button class="btn btn-secondary" onclick="window.print()">🖨️ ${t('exportPdf')}</button>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            ${isHeadOrAdmin ? `<th style="width: 40px;"><input type="checkbox" onchange="toggleSelectAll(this)"></th>` : ''}
                            <th>ID / Название</th>
                            <th>Категория</th>
                            <th>Приоритет</th>
                            <th>Статус</th>
                            <th>Исполнитель</th>
                            <th>Срок SLA</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filtered.length === 0 ? `
                            <tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">${t('noRequests')}</td></tr>
                        ` : filtered.map(r => `
                            <tr>
                                ${isHeadOrAdmin ? `<td><input type="checkbox" class="req-checkbox" value="${r.id}"></td>` : ''}
                                <td>
                                    <div style="font-weight: 600; cursor: pointer; color: var(--accent-color);" onclick="openRequestDetailModal('${r.id}')">${r.title}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">${r.id}</div>
                                </td>
                                <td>${r.category}</td>
                                <td><strong>${r.priority}</strong></td>
                                <td><span class="badge badge-${r.status}">${formatStatus(r.status)}</span></td>
                                <td>${r.executor_id ? (userMap[r.executor_id] || r.executor_id) : '<em>Не назначен</em>'}</td>
                                <td>${r.due_date ? r.due_date.substring(0, 16) : '-'}</td>
                                <td>
                                    <button class="btn btn-secondary btn-icon" onclick="openRequestDetailModal('${r.id}')">👁️</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function updateSearch(val) {
    AppState.searchQuery = val;
    renderApp();
}

function updateStatusFilter(val) {
    AppState.statusFilter = val;
    renderApp();
}

function formatStatus(st) {
    const map = { new: 'Новая', assigned: 'Назначена', in_progress: 'В работе', completed: 'Выполнено', rejected: 'Отклонено' };
    return map[st] || st;
}

function toggleSelectAll(masterCb) {
    document.querySelectorAll('.req-checkbox').forEach(cb => cb.checked = masterCb.checked);
}

// 4. KANBAN BOARD VIEW (Drag and Drop)
function renderKanbanView() {
    const statuses = [
        { id: 'new', title: '🆕 Новые' },
        { id: 'assigned', title: '👤 Назначены' },
        { id: 'in_progress', title: '⚙️ В работе' },
        { id: 'completed', title: '✅ Выполнено' },
        { id: 'rejected', title: '❌ Отклонено' }
    ];

    const userMap = {};
    AppState.users.forEach(u => userMap[u.id] = u.full_name);

    return `
        <div class="kanban-board">
            ${statuses.map(st => {
                const colRequests = AppState.requests.filter(r => r.status === st.id);
                return `
                    <div class="kanban-column" data-status="${st.id}" ondragover="handleDragOver(event)" ondrop="handleDrop(event, '${st.id}')">
                        <div class="kanban-header">
                            <span>${st.title}</span>
                            <span class="badge badge-${st.id}">${colRequests.length}</span>
                        </div>
                        <div class="kanban-cards">
                            ${colRequests.map(r => `
                                <div class="kanban-card" draggable="true" ondragstart="handleDragStart(event, '${r.id}')" onclick="openRequestDetailModal('${r.id}')">
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">${r.category}</div>
                                    <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 8px;">${r.title}</div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                                        <span>⚡ ${r.priority}</span>
                                        <span style="color: var(--text-secondary);">${r.executor_id ? userMap[r.executor_id] : 'Не назначен'}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

let draggedRequestId = null;

function handleDragStart(e, reqId) {
    draggedRequestId = reqId;
    e.dataTransfer.setData('text/plain', reqId);
}

function handleDragOver(e) {
    e.preventDefault();
}

async function handleDrop(e, newStatus) {
    e.preventDefault();
    if (!draggedRequestId) return;

    const res = await apiFetch(`requests/${draggedRequestId}`, {
        method: 'PUT',
        body: JSON.stringify({ status: newStatus })
    });

    if (res && res.success) {
        showAlert(`Статус обновлен: ${formatStatus(newStatus)}`, 'info');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка смены статуса', 'error');
    }
    draggedRequestId = null;
}

// 5. FORM BUILDER VIEW
function renderFormBuilderView() {
    return `
        <div class="card">
            <div class="card-header">
                <h3>📐 Конструктор форм и настраиваемых полей</h3>
                <button class="btn btn-primary" onclick="openCreateFieldModal()">➕ Добавить поле</button>
            </div>
            <div class="card-body">
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.875rem;">
                    Добавляйте дополнительные поля, которые пользователи будут заполнять при создании заявок на ремонт и обслуживание.
                </p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Название поля</th>
                            <th>Тип</th>
                            <th>Обязательное</th>
                            <th>Опции</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${AppState.formFields.length === 0 ? `
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">Настраиваемые поля отсутствуют</td></tr>
                        ` : AppState.formFields.map(f => `
                            <tr>
                                <td><strong>${f.label}</strong></td>
                                <td><code>${f.type}</code></td>
                                <td>${f.required ? '✅ Да' : 'Нет'}</td>
                                <td>${f.options ? f.options.join(', ') : '-'}</td>
                                <td>
                                    <button class="btn btn-secondary btn-icon" onclick="deleteFormField('${f.id}')">🗑️</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function openCreateFieldModal() {
    const modalHtml = `
        <div class="modal-overlay" id="createFieldModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Новое настраиваемое поле</h3>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('createFieldModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Название поля</label>
                        <input type="text" id="fieldLabel" class="form-control" placeholder="Например: Инвентарный номер" required>
                    </div>
                    <div class="form-group">
                        <label>Тип поля</label>
                        <select id="fieldType" class="form-control" onchange="toggleFieldOptions(this.value)">
                            <option value="text">Текст</option>
                            <option value="number">Число</option>
                            <option value="select">Выпадающий список</option>
                            <option value="date">Дата</option>
                        </select>
                    </div>
                    <div class="form-group" id="fieldOptionsGroup" style="display: none;">
                        <label>Варианты (через запятую)</label>
                        <input type="text" id="fieldOptions" class="form-control" placeholder="Вариант 1, Вариант 2">
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="fieldRequired">
                        <label for="fieldRequired" style="margin: 0;">Обязательное для заполнения</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('createFieldModal')">Отмена</button>
                    <button class="btn btn-primary" onclick="submitCreateFormField()">Сохранить поле</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function toggleFieldOptions(type) {
    const grp = document.getElementById('fieldOptionsGroup');
    if (grp) grp.style.display = type === 'select' ? 'block' : 'none';
}

async function submitCreateFormField() {
    const label = document.getElementById('fieldLabel').value;
    const type = document.getElementById('fieldType').value;
    const required = document.getElementById('fieldRequired').checked;
    const optionsRaw = document.getElementById('fieldOptions')?.value || '';

    if (!label) return showAlert('Укажите название поля', 'error');

    const options = optionsRaw ? optionsRaw.split(',').map(s => s.trim()) : [];

    const res = await apiFetch('form-fields', {
        method: 'POST',
        body: JSON.stringify({ label, type, required, options })
    });

    if (res && res.success) {
        showAlert('Поле добавлено', 'info');
        closeModal('createFieldModal');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

async function deleteFormField(id) {
    if (!confirm('Удалить поле?')) return;
    const res = await apiFetch(`form-fields/${id}`, { method: 'DELETE' });
    if (res && res.success) {
        showAlert('Поле удалено', 'info');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

// 6. REQUEST DETAILS & CREATE MODALS
function openCreateRequestModal() {
    const categories = AppState.settings.categories || ['Оборудование', 'ПО / ИТ', 'Сантехника', 'Электрика', 'Мебель'];
    const priorities = AppState.settings.priorities || ['Низкий', 'Средний', 'Высокий', 'Критический'];

    const modalHtml = `
        <div class="modal-overlay" id="createRequestModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>➕ Создание заявки на обслуживание</h3>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('createRequestModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Заголовок заявки</label>
                        <input type="text" id="reqTitle" class="form-control" placeholder="Короткое описание проблемы" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Категория</label>
                            <select id="reqCategory" class="form-control">
                                ${categories.map(c => `<option value="${c}">${c}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Приоритет</label>
                            <select id="reqPriority" class="form-control">
                                ${priorities.map(p => `<option value="${p}">${p}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Подробное описание</label>
                        <textarea id="reqDesc" class="form-control" rows="3" placeholder="Укажите детали, номер кабинета или оборудования"></textarea>
                    </div>

                    ${AppState.formFields.length > 0 ? `
                        <h4 style="margin-top: 1rem; margin-bottom: 0.75rem;">Настраиваемые поля</h4>
                        ${AppState.formFields.map(f => `
                            <div class="form-group">
                                <label>${f.label} ${f.required ? '<span style="color: red;">*</span>' : ''}</label>
                                ${f.type === 'select' ? `
                                    <select class="form-control custom-field-input" data-field-id="${f.id}">
                                        ${(f.options || []).map(o => `<option value="${o}">${o}</option>`).join('')}
                                    </select>
                                ` : `
                                    <input type="${f.type}" class="form-control custom-field-input" data-field-id="${f.id}" ${f.required ? 'required' : ''}>
                                `}
                            </div>
                        `).join('')}
                    ` : ''}

                    <div class="form-group">
                        <label>Прикрепить фото / документ</label>
                        <input type="file" id="reqFile" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('createRequestModal')">Отмена</button>
                    <button class="btn btn-primary" onclick="submitCreateRequest()">Отправить заявку</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function submitCreateRequest() {
    const title = document.getElementById('reqTitle').value;
    const category = document.getElementById('reqCategory').value;
    const priority = document.getElementById('reqPriority').value;
    const description = document.getElementById('reqDesc').value;

    if (!title) return showAlert('Укажите заголовок заявки', 'error');

    const custom_fields = {};
    document.querySelectorAll('.custom-field-input').forEach(input => {
        const fid = input.getAttribute('data-field-id');
        custom_fields[fid] = input.value;
    });

    const attachments = [];
    const fileInput = document.getElementById('reqFile');
    if (fileInput && fileInput.files.length > 0) {
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        try {
            const uploadRes = await fetch('api/uploads', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${AppState.token}` },
                body: formData
            });
            const fileData = await uploadRes.json();
            if (fileData && fileData.success) {
                attachments.push(fileData);
            }
        } catch (err) {
            console.error('File upload error', err);
        }
    }

    const res = await apiFetch('requests', {
        method: 'POST',
        body: JSON.stringify({ title, category, priority, description, custom_fields, attachments })
    });

    if (res && res.success) {
        showAlert('Заявка успешно создана!', 'info');
        closeModal('createRequestModal');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

async function openRequestDetailModal(id) {
    const res = await apiFetch(`requests/${id}`);
    if (!res || !res.success) return showAlert('Не удалось загрузить данные заявки', 'error');

    const req = res.request;
    const userMap = {};
    AppState.users.forEach(u => userMap[u.id] = u.full_name);

    const isHeadOrAdmin = ['admin', 'head'].includes(AppState.currentUser.role);
    const executors = AppState.users.filter(u => ['executor', 'head', 'admin'].includes(u.role));

    const modalHtml = `
        <div class="modal-overlay" id="reqDetailModal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <span class="badge badge-${req.status}">${formatStatus(req.status)}</span>
                        <h3 style="margin-top: 4px;">${req.title}</h3>
                    </div>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('reqDetailModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div style="background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem;">
                        <p style="font-size: 0.9375rem; color: var(--text-primary);">${req.description || '<em>Описание отсутствует</em>'}</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem; margin-bottom: 1.5rem;">
                        <div><strong>Категория:</strong> ${req.category}</div>
                        <div><strong>Приоритет:</strong> ${req.priority}</div>
                        <div><strong>Автор:</strong> ${userMap[req.author_id] || req.author_id}</div>
                        <div><strong>Срок SLA:</strong> ${req.due_date ? req.due_date.substring(0, 16) : '-'}</div>
                    </div>

                    ${isHeadOrAdmin ? `
                        <div class="form-group" style="background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem;">
                            <label>Назначить исполнителя</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <select id="modalExecutorSelect" class="form-control">
                                    <option value="">-- Выберите исполнителя --</option>
                                    ${executors.map(e => `<option value="${e.id}" ${req.executor_id === e.id ? 'selected' : ''}>${e.full_name}</option>`).join('')}
                                </select>
                                <button class="btn btn-primary" onclick="assignExecutorModal('${req.id}')">Назначить</button>
                            </div>
                        </div>
                    ` : ''}

                    <div class="form-group">
                        <label>Изменить статус</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="modalStatusSelect" class="form-control">
                                <option value="new" ${req.status === 'new' ? 'selected' : ''}>Новая</option>
                                <option value="assigned" ${req.status === 'assigned' ? 'selected' : ''}>Назначена</option>
                                <option value="in_progress" ${req.status === 'in_progress' ? 'selected' : ''}>В работе</option>
                                <option value="completed" ${req.status === 'completed' ? 'selected' : ''}>Выполнено</option>
                                <option value="rejected" ${req.status === 'rejected' ? 'selected' : ''}>Отклонено</option>
                            </select>
                            <button class="btn btn-primary" onclick="updateStatusModal('${req.id}')">Обновить</button>
                        </div>
                    </div>

                    <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">💬 Комментарии и Отчёты</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; max-height: 200px; overflow-y: auto;">
                        ${(!req.comments || req.comments.length === 0) ? `
                            <div style="color: var(--text-muted); font-size: 0.875rem;">Комментариев пока нет</div>
                        ` : req.comments.map(c => `
                            <div style="background: var(--bg-primary); padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem;">
                                <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 4px;">
                                    <span>${c.author_name}</span>
                                    <span style="font-weight: 400; color: var(--text-muted); font-size: 0.75rem;">${c.created_at.substring(0, 16)}</span>
                                </div>
                                <div>${c.content}</div>
                            </div>
                        `).join('')}
                    </div>

                    <div class="form-group">
                        <textarea id="modalCommentText" class="form-control" rows="2" placeholder="Написать комментарий или отчет..."></textarea>
                        <button class="btn btn-secondary" style="margin-top: 0.5rem;" onclick="addCommentModal('${req.id}')">Отправить комментарий</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function assignExecutorModal(id) {
    const execId = document.getElementById('modalExecutorSelect').value;
    const res = await apiFetch(`requests/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ executor_id: execId })
    });
    if (res && res.success) {
        showAlert('Исполнитель назначен', 'info');
        closeModal('reqDetailModal');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

async function updateStatusModal(id) {
    const status = document.getElementById('modalStatusSelect').value;
    const res = await apiFetch(`requests/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ status })
    });
    if (res && res.success) {
        showAlert('Статус изменён', 'info');
        closeModal('reqDetailModal');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

async function addCommentModal(id) {
    const content = document.getElementById('modalCommentText').value;
    if (!content) return;

    const res = await apiFetch(`requests/${id}/comments`, {
        method: 'POST',
        body: JSON.stringify({ content })
    });

    if (res && res.success) {
        showAlert('Комментарий добавлен', 'info');
        closeModal('reqDetailModal');
        openRequestDetailModal(id);
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

function openBatchAssignModal() {
    const checked = Array.from(document.querySelectorAll('.req-checkbox:checked')).map(cb => cb.value);
    if (checked.length === 0) return showAlert('Выберите хотя бы одну заявку', 'error');

    const executors = AppState.users.filter(u => ['executor', 'head', 'admin'].includes(u.role));

    const modalHtml = `
        <div class="modal-overlay" id="batchAssignModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>👥 Массовое назначение (${checked.length} заявок)</h3>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('batchAssignModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Выберите исполнителя</label>
                        <select id="batchExecutorSelect" class="form-control">
                            ${executors.map(e => `<option value="${e.id}">${e.full_name}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('batchAssignModal')">Отмена</button>
                    <button class="btn btn-primary" onclick="submitBatchAssign([${checked.map(c => `'${c}'`).join(',')}])">Назначить</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function submitBatchAssign(requestIds) {
    const executorId = document.getElementById('batchExecutorSelect').value;
    const res = await apiFetch('requests/batch-assign', {
        method: 'POST',
        body: JSON.stringify({ request_ids: requestIds, executor_id: executorId })
    });

    if (res && res.success) {
        showAlert(res.message, 'info');
        closeModal('batchAssignModal');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

// 7. SETTINGS & USER MANAGEMENT VIEWS
function renderSettingsView() {
    const s = AppState.settings;
    return `
        <div class="card">
            <div class="card-header">
                <h3>⚙️ Системные настройки и Режим хранения</h3>
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Режим хранения данных</label>
                            <select id="settingStorageMode" class="form-control" onchange="toggleMySQLInputs(this.value)">
                                <option value="json" ${s.storage_mode === 'json' ? 'selected' : ''}>JSON файлы (Atomic Lock)</option>
                                <option value="mysql" ${s.storage_mode === 'mysql' ? 'selected' : ''}>MySQL БД (Авто-миграция)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Язык интерфейса по умолчанию</label>
                            <select id="settingLanguage" class="form-control">
                                <option value="ru" ${s.language === 'ru' ? 'selected' : ''}>Русский</option>
                                <option value="en" ${s.language === 'en' ? 'selected' : ''}>English</option>
                            </select>
                        </div>
                    </div>

                    <div id="mysqlConfigBlock" style="display: ${s.storage_mode === 'mysql' ? 'block' : 'none'}; background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <h4 style="margin-bottom: 1rem;">Параметры подключения MySQL</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Хост MySQL</label>
                                <input type="text" id="mysqlHost" class="form-control" value="${s.mysql_host || '127.0.0.1'}">
                            </div>
                            <div class="form-group">
                                <label>Порт</label>
                                <input type="text" id="mysqlPort" class="form-control" value="${s.mysql_port || '3306'}">
                            </div>
                            <div class="form-group">
                                <label>Имя БД</label>
                                <input type="text" id="mysqlDb" class="form-control" value="${s.mysql_db || 'crm_db'}">
                            </div>
                            <div class="form-group">
                                <label>Пользователь БД</label>
                                <input type="text" id="mysqlUser" class="form-control" value="${s.mysql_user || 'root'}">
                            </div>
                            <div class="form-group">
                                <label>Пароль БД</label>
                                <input type="password" id="mysqlPass" class="form-control" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <h4 style="margin-bottom: 1rem; margin-top: 1.5rem;">Категории заявок (через запятую)</h4>
                    <div class="form-group">
                        <input type="text" id="settingCategories" class="form-control" value="${(s.categories || []).join(', ')}">
                    </div>

                    <h4 style="margin-bottom: 1rem; margin-top: 1.5rem;">SLA Нормативы выполнения (Часы)</h4>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                        <div class="form-group">
                            <label>Низкий приоритет</label>
                            <input type="number" id="slaLow" class="form-control" value="${s.sla_low || 72}">
                        </div>
                        <div class="form-group">
                            <label>Средний приоритет</label>
                            <input type="number" id="slaMedium" class="form-control" value="${s.sla_medium || 48}">
                        </div>
                        <div class="form-group">
                            <label>Высокий приоритет</label>
                            <input type="number" id="slaHigh" class="form-control" value="${s.sla_high || 24}">
                        </div>
                        <div class="form-group">
                            <label>Критический</label>
                            <input type="number" id="slaUrgent" class="form-control" value="${s.sla_urgent || 8}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Сохранить настройки</button>
                </form>
            </div>
        </div>
    `;
}

function toggleMySQLInputs(val) {
    const block = document.getElementById('mysqlConfigBlock');
    if (block) block.style.display = val === 'mysql' ? 'block' : 'none';
}

function renderUsersView() {
    return `
        <div class="card">
            <div class="card-header">
                <h3>👥 Управление пользователями и ролями</h3>
                <button class="btn btn-primary" onclick="openCreateUserModal()">➕ Добавить пользователя</button>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Логин</th>
                            <th>ФИО</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Отдел</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${AppState.users.map(u => `
                            <tr>
                                <td><strong>${u.username}</strong></td>
                                <td>${u.full_name}</td>
                                <td>${u.email || '-'}</td>
                                <td><span class="badge badge-assigned">${u.role}</span></td>
                                <td>${u.department || '-'}</td>
                                <td><span class="badge ${u.status === 'active' ? 'badge-completed' : 'badge-rejected'}">${u.status}</span></td>
                                <td>
                                    <button class="btn btn-secondary btn-icon" onclick="deleteUser('${u.id}')">🗑️</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderCurrentView() {
    switch (AppState.currentView) {
        case 'dashboard': return renderDashboardView();
        case 'requests': return renderRequestsView();
        case 'kanban': return renderKanbanView();
        case 'formBuilder': return renderFormBuilderView();
        case 'users': return renderUsersView();
        case 'settings': return renderSettingsView();
        default: return renderDashboardView();
    }
}

function bindViewEvents() {
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const rawCats = document.getElementById('settingCategories').value;
            const categories = rawCats ? rawCats.split(',').map(c => c.trim()) : [];

            const payload = {
                storage_mode: document.getElementById('settingStorageMode').value,
                language: document.getElementById('settingLanguage').value,
                mysql_host: document.getElementById('mysqlHost').value,
                mysql_port: document.getElementById('mysqlPort').value,
                mysql_db: document.getElementById('mysqlDb').value,
                mysql_user: document.getElementById('mysqlUser').value,
                categories: categories,
                sla_low: parseInt(document.getElementById('slaLow').value),
                sla_medium: parseInt(document.getElementById('slaMedium').value),
                sla_high: parseInt(document.getElementById('slaHigh').value),
                sla_urgent: parseInt(document.getElementById('slaUrgent').value)
            };

            const pass = document.getElementById('mysqlPass').value;
            if (pass) payload.mysql_pass = pass;

            const res = await apiFetch('settings', {
                method: 'PUT',
                body: JSON.stringify(payload)
            });

            if (res && res.success) {
                showAlert('Настройки сохранены', 'info');
                await loadInitialData();
                renderApp();
            } else {
                showAlert(res?.error || 'Ошибка сохранения', 'error');
            }
        });
    }
}

function openCreateUserModal() {
    const modalHtml = `
        <div class="modal-overlay" id="createUserModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Новый пользователь</h3>
                    <button class="btn btn-secondary btn-icon" onclick="closeModal('createUserModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" id="newUsername" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" id="newPassword" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>ФИО</label>
                        <input type="text" id="newFullName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Роль</label>
                        <select id="newRole" class="form-control">
                            <option value="admin">Администратор</option>
                            <option value="head">Начальник отдела</option>
                            <option value="responsible">Ответственный сотрудник</option>
                            <option value="executor">Исполнитель (Техник)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Отдел</label>
                        <input type="text" id="newDept" class="form-control" placeholder="Отдел ИТ и Ремонта">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('createUserModal')">Отмена</button>
                    <button class="btn btn-primary" onclick="submitCreateUser()">Создать</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

async function submitCreateUser() {
    const payload = {
        username: document.getElementById('newUsername').value,
        password: document.getElementById('newPassword').value,
        full_name: document.getElementById('newFullName').value,
        role: document.getElementById('newRole').value,
        department: document.getElementById('newDept').value
    };

    const res = await apiFetch('users', {
        method: 'POST',
        body: JSON.stringify({ username: payload.username, password: payload.password, full_name: payload.full_name, role: payload.role, department: payload.department })
    });

    if (res && res.success) {
        showAlert('Пользователь создан', 'info');
        closeModal('createUserModal');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}

async function deleteUser(id) {
    if (!confirm('Удалить пользователя?')) return;
    const res = await apiFetch(`users/${id}`, { method: 'DELETE' });
    if (res && res.success) {
        showAlert('Удалено', 'info');
        await loadInitialData();
        renderApp();
    } else {
        showAlert(res?.error || 'Ошибка', 'error');
    }
}
