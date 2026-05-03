const API_BASE = 'api';
let currentUser = null;
let token = localStorage.getItem('token');
let workTypes = [];
let users = [];
let departments = [];
let authRequired = true;
let systemSettings = {};
let lastRequestCount = null;

function escapeHTML(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

const el = {
    loginScreen: document.getElementById('login-screen'),
    mainLayout: document.getElementById('main-layout'),
    loginForm: document.getElementById('login-form'),
    appContent: document.getElementById('app-content'),
    mainNav: document.getElementById('main-nav'),
    userInfo: document.getElementById('user-info'),
    logoutBtn: document.getElementById('logout-btn')
};

let deferredPrompt;

// PWA Logic
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./sw.js').catch(err => console.log('SW registration failed', err));
    });
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // Show banner only on mobile
    if (window.innerWidth < 768) {
        document.getElementById('pwa-install-banner').classList.remove('hidden');
    }
});

document.getElementById('pwa-install-btn').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User response to the install prompt: ${outcome}`);
    deferredPrompt = null;
    document.getElementById('pwa-install-banner').classList.add('hidden');
});

document.getElementById('pwa-close').addEventListener('click', () => {
    document.getElementById('pwa-install-banner').classList.add('hidden');
});

function hideSplashScreen() {
    const splash = document.getElementById('splash-screen');
    if (splash) {
        splash.classList.add('hidden');
        setTimeout(() => splash.remove(), 500);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Check system config
    try {
        const cfgRes = await fetch(`${API_BASE}/auth.php?action=config`);
        systemSettings = await cfgRes.json();
        authRequired = systemSettings.auth_required;
        if (systemSettings.announcement) {
            const banner = document.getElementById('announcement-banner');
            banner.innerHTML = `<i class="bi bi-megaphone-fill me-2"></i> ${escapeHTML(systemSettings.announcement)}`;
            banner.classList.remove('hidden');
        }

        if (systemSettings.notify_browser && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        if (systemSettings.org_name) {
            document.getElementById('sidebar-org-name').innerText = systemSettings.org_name;
            document.getElementById('page-title').innerText = systemSettings.org_name;
        }
    } catch (e) { console.error('Config fetch failed', e); }

    if (!authRequired) {
        // Auto-login as guest/admin
        const res = await fetch(`${API_BASE}/auth.php?action=login`, { method: 'POST' });
        const data = await res.json();
        token = data.token;
        currentUser = data.user;
        localStorage.setItem('token', token);
        await loadLookups();
        showLayout();
        await renderDashboard();
        startPolling();
        hideSplashScreen();
    } else if (token) {
        const success = await fetchUser();
        if (success) {
            await loadLookups();
            showLayout();
            await renderDashboard();
            startPolling();
            hideSplashScreen();
        } else {
            showLogin();
            hideSplashScreen();
        }
    } else {
        showLogin();
        hideSplashScreen();
    }
});

async function loadLookups() {
    try {
        const [wtRes, dRes, uRes] = await Promise.allSettled([
            apiFetch('/admin.php?action=worktypes'),
            apiFetch('/admin.php?action=departments'),
            apiFetch('/admin.php?action=users')
        ]);
        if (wtRes.status === 'fulfilled' && wtRes.value.ok) workTypes = await wtRes.value.json();
        if (dRes.status === 'fulfilled' && dRes.value.ok) departments = await dRes.value.json();
        if (uRes.status === 'fulfilled' && uRes.value.ok) users = await uRes.value.json();
    } catch (e) { console.error('Lookup loading failed:', e); }
}

async function fetchUser() {
    try {
        const res = await fetch(`${API_BASE}/auth.php?action=me`, { headers: { 'Authorization': `Bearer ${token}` } });
        if (res.ok) {
            currentUser = await res.json();
            return true;
        }
        return false;
    } catch (e) { return false; }
}

function showLogin() {
    el.loginScreen.classList.remove('hidden');
    el.mainLayout.classList.add('hidden');
}

function showLayout() {
    el.loginScreen.classList.add('hidden');
    el.mainLayout.classList.remove('hidden');
    const userInfoHtml = `
        <div class="fw-bold">${escapeHTML(currentUser.full_name)}</div>
        <div class="text-white small opacity-75">${escapeHTML(currentUser.role)}</div>
    `;
    el.userInfo.innerHTML = userInfoHtml;

    const mName = document.getElementById('mobile-profile-name');
    const mRole = document.getElementById('mobile-profile-role');
    if (mName) mName.innerText = currentUser.full_name;
    if (mRole) mRole.innerText = currentUser.role;

    const adminEl = document.getElementById('nav-admin');
    const reportsEl = document.getElementById('nav-reports');
    const deptEl = document.getElementById('nav-department');

    const perms = currentUser.permissions || {};

    adminEl.classList.toggle('hidden', currentUser.role !== 'admin' && !(perms.can_manage_system));
    reportsEl.classList.toggle('hidden', !['admin', 'manager'].includes(currentUser.role) && !(perms.can_view_reports));
    deptEl.classList.toggle('hidden', currentUser.role !== 'admin' && !(perms.can_view_department ?? (currentUser.role !== 'user')));

    // Mobile specific nav toggles
    const mAdmin = document.getElementById('mob-nav-admin');
    const mRep = document.getElementById('mob-nav-rep');
    const mDept = document.getElementById('mob-nav-dept');
    const mChat = document.getElementById('mob-nav-chat');
    const dChat = document.getElementById('nav-chat');

    if (mAdmin) mAdmin.classList.toggle('hidden', currentUser.role !== 'admin' && !(perms.can_manage_system));
    if (mRep) mRep.classList.toggle('hidden', !['admin', 'manager'].includes(currentUser.role) && !(perms.can_view_reports));
    if (mDept) mDept.classList.toggle('hidden', currentUser.role !== 'admin' && !(perms.can_view_department ?? (currentUser.role !== 'user')));
    if (mChat) mChat.classList.toggle('hidden', currentUser.role !== 'admin' && !(perms.can_view_chat ?? true));
    if (dChat) dChat.classList.toggle('hidden', currentUser.role !== 'admin' && !(perms.can_view_chat ?? true));

    // Mobile Profile Trigger
    const profileTrigger = document.getElementById('mobile-profile-trigger');
    if (profileTrigger) {
        profileTrigger.onclick = () => {
            const offcanvas = new bootstrap.Offcanvas(document.getElementById('mobileProfile'));
            offcanvas.show();
        };
    }

    const mBack = document.getElementById('mobile-back-btn');
    if (mBack) mBack.onclick = () => navigateToView('dashboard');
}

el.loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const login = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    const errorEl = document.getElementById('login-error');
    try {
        const res = await fetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login, password })
        });
        if (res.ok) {
            const data = await res.json();
            token = data.token;
            currentUser = data.user;
            localStorage.setItem('token', token);
            await loadLookups();
            showLayout();
            renderDashboard();
            startPolling();
        } else {
            const data = await res.json().catch(() => ({}));
            errorEl.innerText = data.message || 'Login failed';
            errorEl.classList.remove('hidden');
        }
    } catch (err) {
        errorEl.innerText = 'Server error';
        errorEl.classList.remove('hidden');
    }
});

el.logoutBtn.addEventListener('click', () => {
    localStorage.removeItem('token');
    location.reload();
});

function navigateToView(view) {
    if (!view) return;
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    document.querySelectorAll(`.nav-link[data-view="${view}"]`).forEach(l => l.classList.add('active'));

    // Mobile title management
    const mTitle = document.getElementById('mobile-title');
    const mBack = document.getElementById('mobile-back-btn');
    if (mTitle) {
        const titles = { 'dashboard': 'HOP', 'department': 'Заявки', 'create': 'Новая заявка', 'admin': 'Настройки', 'reports': 'Аналитика', 'help': 'Инфо', 'chat': 'Чат' };
        mTitle.innerText = titles[view] || 'HOP';
    }
    if (mBack) mBack.style.display = view === 'dashboard' ? 'none' : 'block';

    switch (view) {
        case 'dashboard': renderDashboard(); break;
        case 'department': renderDepartment(); break;
        case 'create': renderCreate(); break;
        case 'admin': renderAdmin(); break;
        case 'reports': renderReports(); break;
        case 'help': renderHelp(); break;
        case 'chat': renderGlobalChat(); break;
    }

    // Close mobile offcanvas if open
    const offcanvasEl = document.getElementById('mobileSidebar');
    if (offcanvasEl) {
        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (offcanvas) offcanvas.hide();
    }
}

document.addEventListener('click', (e) => {
    const link = e.target.closest('.nav-link') || e.target.closest('.mobile-fab');
    if (!link) return;
    e.preventDefault();
    navigateToView(link.dataset.view);
});

async function apiFetch(url, options = {}) {
    options.headers = { ...options.headers, 'Authorization': `Bearer ${token}` };
    try {
        const res = await fetch(`${API_BASE}${url}`, options);
        if (res.status === 401 && authRequired) {
            localStorage.removeItem('token');
            location.reload();
        }
        return res;
    } catch (e) { throw e; }
}

function getStatusBadge(status) {
    const config = {
        'new': 'bg-info text-white',
        'assigned': 'bg-primary text-white',
        'in_progress': 'bg-warning text-dark',
        'completed': 'bg-success text-white',
        'closed': 'bg-secondary text-white',
        'rejected': 'bg-danger text-white'
    };
    const cls = config[status] || 'bg-secondary text-white';
    const labels = {
        'new': 'Новая',
        'assigned': 'Назначена',
        'in_progress': 'В работе',
        'completed': 'Выполнена',
        'closed': 'Закрыта',
        'rejected': 'Отклонена'
    };
    return `<span class="badge ${cls}">${labels[status] || status}</span>`;
}

function getWorkTypeName(id) {
    const wt = workTypes.find(w => w.id == id);
    return wt ? wt.name : id;
}

function getUserName(id) {
    if (!id) return 'Не назначен';
    const u = users.find(user => user.id == id);
    return u ? u.full_name : `ID: ${id}`;
}

function setToday(fromId, toId, callback) {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById(fromId).value = today;
    document.getElementById(toId).value = today;
    callback();
}

async function renderDashboard(from = '', to = '') {
    const isMobile = window.innerWidth < 768;
    if (isMobile) {
        el.appContent.innerHTML = `
            <div class="p-3">
                <div class="bg-white p-3 rounded-4 shadow-sm mb-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-calendar3 text-primary"></i>
                        <input type="date" id="dash-from" class="form-control form-control-sm border-0 bg-light" value="${from}">
                        <span class="text-muted small">до</span>
                        <input type="date" id="dash-to" class="form-control form-control-sm border-0 bg-light" value="${to}">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm flex-grow-1 rounded-pill" onclick="setToday('dash-from', 'dash-to', filterDashboard)">Сегодня</button>
                        <button class="btn btn-primary btn-sm flex-grow-1 rounded-pill" onclick="filterDashboard()">Найти</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless mb-0 mobile-card-table">
                        <tbody id="req-table"></tbody>
                    </table>
                </div>
            </div>
        `;
    } else {
        el.appContent.innerHTML = `
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <h2 class="fw-bold mb-0">Мои заявки</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                        <i class="bi bi-calendar3 text-muted ms-1"></i>
                        <input type="date" id="dash-from" class="form-control form-control-sm border-0" value="${from}">
                        <span class="text-muted small">до</span>
                        <input type="date" id="dash-to" class="form-control form-control-sm border-0" value="${to}">
                    </div>
                    <div class="d-flex gap-2 w-100 w-md-auto">
                        <button class="btn btn-outline-secondary btn-sm rounded-2 flex-grow-1" onclick="setToday('dash-from', 'dash-to', filterDashboard)">Сегодня</button>
                        <button class="btn btn-primary btn-sm rounded-2 px-3 flex-grow-1" onclick="filterDashboard()">Фильтр</button>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Номер</th>
                                <th>Тип</th>
                                <th class="d-none d-md-table-cell">Описание</th>
                                <th>Статус</th>
                                <th class="pe-4 text-end">Дата</th>
                            </tr>
                        </thead>
                        <tbody id="req-table" class="border-top-0"></tbody>
                    </table>
                </div>
            </div>
        `;
    }

    window.filterDashboard = () => {
        const f = document.getElementById('dash-from').value;
        const t = document.getElementById('dash-to').value;
        renderDashboard(f, t);
    };

    try {
        const query = (from || to) ? `&from=${from}&to=${to}` : '';
        const res = await apiFetch(`/requests.php?action=my${query}`);
        const requests = await res.json();
        const tbody = document.getElementById('req-table');
        requests.forEach(r => {
            const tr = document.createElement('tr');
            if (isMobile) {
                tr.innerHTML = `
                    <td class="p-0 border-0">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <a href="#" class="req-link fw-bold h5 text-primary" data-id="${r.id}">${escapeHTML(r.number)}</a>
                            ${getStatusBadge(r.status)}
                        </div>
                        <div class="mb-2"><span class="badge bg-light text-dark border">${escapeHTML(getWorkTypeName(r.work_type_id))}</span></div>
                        <div class="text-muted small mb-3 text-truncate-2">${escapeHTML(r.description)}</div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-muted small"><i class="bi bi-calendar-event me-1"></i>${new Date(r.created_at).toLocaleDateString()}</span>
                            <span class="text-muted small"><i class="bi bi-person me-1"></i>${escapeHTML(getUserName(r.assigned_to))}</span>
                        </div>
                    </td>
                `;
            } else {
                tr.innerHTML = `
                    <td class="ps-4" data-label="Номер"><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td>
                    <td data-label="Тип"><span class="small fw-medium">${escapeHTML(getWorkTypeName(r.work_type_id))}</span></td>
                    <td class="d-none d-md-table-cell text-muted small">${escapeHTML(r.description.substring(0, 50))}${r.description.length > 50 ? '...' : ''}</td>
                    <td data-label="Статус">${getStatusBadge(r.status)}</td>
                    <td class="pe-4 text-end text-muted small" data-label="Дата">${new Date(r.created_at).toLocaleDateString()}</td>
                `;
            }
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.req-link').forEach(link => {
            link.addEventListener('click', (e) => { e.preventDefault(); showRequestDetails(link.dataset.id); });
        });
    } catch (e) { el.appContent.innerHTML += '<div class="alert alert-danger">Ошибка загрузки данных</div>'; }
}

async function renderCreate() {
    const isMobile = window.innerWidth < 768;
    el.appContent.innerHTML = `
        ${isMobile ? '' : '<h2 class="fw-bold mb-4">Создать заявку</h2>'}
        <div class="${isMobile ? 'p-3' : 'card border-0 shadow-sm p-4'}" style="max-width: 700px">
        <form id="create-request-form" class="${isMobile ? 'bg-white p-4 rounded-4 shadow-sm' : ''}">
            <div class="mb-3">
                <label class="form-label fw-semibold">Тип работ</label>
                <select id="cr-worktype" name="work_type_id" class="form-select" required>
                    ${workTypes.map(wt => `<option value="${wt.id}">${escapeHTML(wt.name)}</option>`).join('')}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Приоритет</label>
                <select id="cr-priority" name="priority" class="form-select">
                    <option value="normal">Обычный</option>
                    <option value="high">Высокий</option>
                    <option value="low">Низкий</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Место выполнения</label>
                <input type="text" id="cr-location" name="location" class="form-control" placeholder="Корпус, этаж, кабинет" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание проблемы</label>
                <textarea id="cr-description" name="description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Прикрепить фото/файл</label>
                <input type="file" id="cr-file" name="file" class="form-control">
            </div>
            <div class="text-end">
                <button type="submit" id="cr-submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill">Отправить заявку</button>
            </div>
        </form>
        </div>
    `;

    document.getElementById('create-request-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const res = await apiFetch('/requests.php?action=create', { method: 'POST', body: formData });
            if (res.ok) renderDashboard();
            else { const data = await res.json(); alert('Ошибка: ' + (data.message || 'Unknown error')); }
        } catch (err) { alert('Ошибка сервера'); }
    });
}

async function renderDepartment(from = '', to = '') {
    const isMobile = window.innerWidth < 768;
    if (isMobile) {
        el.appContent.innerHTML = `
            <div class="p-3">
                <div class="bg-white p-3 rounded-4 shadow-sm mb-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-calendar3 text-primary"></i>
                        <input type="date" id="dept-from" class="form-control form-control-sm border-0 bg-light" value="${from}">
                        <span class="text-muted small">до</span>
                        <input type="date" id="dept-to" class="form-control form-control-sm border-0 bg-light" value="${to}">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm flex-grow-1 rounded-pill" onclick="setToday('dept-from', 'dept-to', filterDept)">Сегодня</button>
                        <button class="btn btn-primary btn-sm flex-grow-1 rounded-pill" onclick="filterDept()">Найти</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless mb-0 mobile-card-table">
                        <tbody id="dept-req-table"></tbody>
                    </table>
                </div>
            </div>
        `;
    } else {
        el.appContent.innerHTML = `
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <h2 class="fw-bold mb-0">Заявки отдела</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                        <i class="bi bi-calendar3 text-muted ms-1"></i>
                        <input type="date" id="dept-from" class="form-control form-control-sm border-0" value="${from}">
                        <span class="text-muted small">до</span>
                        <input type="date" id="dept-to" class="form-control form-control-sm border-0" value="${to}">
                    </div>
                    <div class="d-flex gap-2 w-100 w-md-auto">
                        <button class="btn btn-outline-secondary btn-sm rounded-2 flex-grow-1" onclick="setToday('dept-from', 'dept-to', filterDept)">Сегодня</button>
                        <button class="btn btn-primary btn-sm rounded-2 px-3 flex-grow-1" onclick="filterDept()">Фильтр</button>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Номер</th>
                                <th>Тип</th>
                                <th>Статус</th>
                                <th class="pe-4">Исполнитель</th>
                            </tr>
                        </thead>
                        <tbody id="dept-req-table" class="border-top-0"></tbody>
                    </table>
                </div>
            </div>
        `;
    }

    window.filterDept = () => {
        const f = document.getElementById('dept-from').value;
        const t = document.getElementById('dept-to').value;
        renderDepartment(f, t);
    };

    try {
        const query = (from || to) ? `&from=${from}&to=${to}` : '';
        const res = await apiFetch(`/requests.php?action=department${query}`);
        const requests = await res.json();
        const tbody = document.getElementById('dept-req-table');
        const perms = currentUser.permissions || { can_assign: false };
        requests.forEach(r => {
            const tr = document.createElement('tr');
            const assignHtml = (currentUser.role === 'admin' || perms.can_assign) ?
                `<select class="form-select form-select-sm assign-select" data-id="${r.id}">
                    <option value="">Назначить...</option>
                    ${users.filter(u => u.role === 'executor' && u.department_id == r.department_id).map(u => `<option value="${u.id}" ${r.assigned_to == u.id ? 'selected' : ''}>${escapeHTML(u.full_name)}</option>`).join('')}
                </select>` : escapeHTML(getUserName(r.assigned_to));

            if (isMobile) {
                tr.innerHTML = `
                    <td class="p-0 border-0">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <a href="#" class="req-link fw-bold h5 text-primary" data-id="${r.id}">${escapeHTML(r.number)}</a>
                            ${getStatusBadge(r.status)}
                        </div>
                        <div class="mb-3"><span class="badge bg-light text-dark border">${escapeHTML(getWorkTypeName(r.work_type_id))}</span></div>
                        <div class="p-3 bg-light rounded-3 mb-3">
                             <div class="small text-muted mb-1">Исполнитель:</div>
                             ${assignHtml}
                        </div>
                        <div class="text-end">
                            <span class="text-muted small"><i class="bi bi-calendar-event me-1"></i>${new Date(r.created_at).toLocaleDateString()}</span>
                        </div>
                    </td>
                `;
            } else {
                tr.innerHTML = `
                    <td class="ps-4" data-label="Номер"><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td>
                    <td data-label="Тип"><span class="small fw-medium">${escapeHTML(getWorkTypeName(r.work_type_id))}</span></td>
                    <td data-label="Статус">${getStatusBadge(r.status)}</td>
                    <td class="pe-4" data-label="Исполнитель">${assignHtml}</td>
                `;
            }
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.assign-select').forEach(sel => {
            sel.onchange = (e) => assignRequest(sel.dataset.id, e.target.value);
        });
        tbody.querySelectorAll('.req-link').forEach(link => {
            link.addEventListener('click', (e) => { e.preventDefault(); showRequestDetails(link.dataset.id); });
        });
    } catch (e) { el.appContent.innerHTML += '<div class="alert alert-danger">Ошибка загрузки данных отдела</div>'; }
}

async function showRequestDetails(id) {
    try {
        const res = await apiFetch(`/requests.php?action=details&id=${id}`);
        const req = await res.json();
        const histRes = await apiFetch(`/requests.php?action=history&id=${id}`);
        const history = await histRes.json();
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2"><span class="text-muted small">Номер:</span> <span class="fw-bold">${escapeHTML(req.number)}</span></div>
                    <div class="mb-2"><span class="text-muted small">Статус:</span> ${getStatusBadge(req.status)}</div>
                    <div class="mb-2"><span class="text-muted small">Приоритет:</span> <span class="badge bg-light text-dark border">${escapeHTML(req.priority)}</span></div>
                    <div class="mb-2"><span class="text-muted small">Место:</span> <span class="fw-medium">${escapeHTML(req.location)}</span></div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><span class="text-muted small">Создана:</span> <span class="small">${new Date(req.created_at).toLocaleString()}</span></div>
                    <div class="mb-2"><span class="text-muted small">Заявитель:</span> <span class="small fw-medium">${escapeHTML(getUserName(req.requester_id))}</span></div>
                    <div class="mb-2"><span class="text-muted small">Исполнитель:</span> <span class="small fw-medium">${escapeHTML(getUserName(req.assigned_to))}</span></div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-light rounded-3">
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Описание проблемы</h6>
                <p class="mb-0">${escapeHTML(req.description)}</p>
                ${req.file_path ? `<div class="mt-2"><i class="bi bi-paperclip text-primary"></i> <a href="${req.file_path}" target="_blank" class="small fw-medium">${escapeHTML(req.file_original_name)}</a></div>` : ''}
            </div>
            <div class="row mt-4 g-3">
                <div class="col-md-6">
                    <h6 class="fw-bold small text-uppercase text-muted mb-3">История изменений</h6>
                    <div class="pe-2" style="max-height: 200px; overflow-y: auto;">
                        ${history.map(h => `
                            <div class="mb-3 pb-2 border-bottom border-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold">${escapeHTML(h.status)}</span>
                                    <span class="text-muted" style="font-size: 0.7rem">${new Date(h.changed_at).toLocaleTimeString()}</span>
                                </div>
                                <div class="small text-muted">${escapeHTML(h.comment)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold small text-uppercase text-muted mb-3">Чат поддержки</h6>
                    <div id="chat-box" class="bg-white border p-3 mb-2 rounded-3 shadow-sm" style="height: 150px; overflow-y: auto;"></div>
                    <div class="input-group input-group-sm shadow-sm">
                        <input type="text" id="chat-input" class="form-control border-0 bg-light" placeholder="Напишите сообщение...">
                        <button class="btn btn-primary" id="chat-send"><i class="bi bi-send"></i></button>
                    </div>
                </div>
            </div>
            <hr>
            <div id="action-buttons"></div>
        `;

        const loadChat = async () => {
            const chatRes = await apiFetch(`/requests.php?action=get_comments&id=${id}`);
            const comments = await chatRes.json();
            const box = document.getElementById('chat-box');
            box.innerHTML = comments.map(c => `<div class="mb-1 small"><strong>${escapeHTML(c.user_name)}:</strong> ${escapeHTML(c.message)}</div>`).join('');
            box.scrollTop = box.scrollHeight;
        };

        loadChat();

        document.getElementById('chat-send').onclick = async () => {
            const input = document.getElementById('chat-input');
            if (!input.value) return;
            const res = await apiFetch('/requests.php?action=add_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: id, message: input.value })
            });
            if (res.ok) { input.value = ''; loadChat(); }
        };

        const btnsDiv = document.getElementById('action-buttons');
        const perms = currentUser.permissions || { can_status: true, can_delete: false, can_assign: false };

        if (currentUser.role === 'admin' || (perms.can_status && (['executor', 'manager'].includes(currentUser.role)))) {
            if (['assigned', 'new'].includes(req.status)) {
                const btn = document.createElement('button'); btn.className = 'btn btn-success btn-sm me-2'; btn.innerText = 'В работу'; btn.onclick = () => updateStatus(req.id, 'in_progress', 'Взято в работу'); btnsDiv.appendChild(btn);
            }
            if (req.status === 'in_progress') {
                const btn = document.createElement('button'); btn.className = 'btn btn-info btn-sm me-2'; btn.innerText = 'Выполнено'; btn.onclick = () => updateStatus(req.id, 'completed', 'Работы завершены'); btnsDiv.appendChild(btn);
            }
        }
        if ((currentUser.role === 'admin' || perms.can_delete) && req.requester_id == currentUser.id) {
            const btn = document.createElement('button'); btn.className = 'btn btn-outline-danger btn-sm me-2'; btn.innerText = 'Удалить'; btn.onclick = () => deleteRequest(req.id); btnsDiv.appendChild(btn);
        }

        if (req.requester_id == currentUser.id && req.status === 'completed') {
            const div = document.createElement('div');
            div.className = 'mt-4 p-4 bg-primary bg-opacity-10 border-0 rounded-4';
            div.innerHTML = `
                <h6 class="fw-bold mb-3">Подтверждение выполнения</h6>
                <label class="form-label small fw-medium">Пожалуйста, оцените качество работы:</label>
                <div class="d-flex gap-3 mb-4">
                    ${[1,2,3,4,5].map(i => `
                        <div>
                            <input type="radio" class="btn-check" name="req-rating" value="${i}" id="r${i}" ${i==5?'checked':''}>
                            <label class="btn btn-outline-primary btn-sm rounded-circle" for="r${i}" style="width: 35px; height: 35px; line-height: 22px;">${i}</label>
                        </div>
                    `).join('')}
                </div>
                <button class="btn btn-success w-100 fw-bold rounded-pill mb-2 py-2" id="confirm-btn">Принять и закрыть заявку</button>
            `;
            div.querySelector('#confirm-btn').onclick = () => {
                const rating = div.querySelector('input[name="req-rating"]:checked').value;
                updateStatus(req.id, 'closed', 'Заявка подтверждена', rating);
            };
            const rejBtn = document.createElement('button');
            rejBtn.className = 'btn btn-outline-danger btn-sm w-100 mt-2';
            rejBtn.innerText = 'На доработку (Отклонить)';
            rejBtn.onclick = () => {
                const comment = prompt('Укажите причину возврата на доработку:');
                if (comment) updateStatus(req.id, 'rejected', comment);
            };
            div.appendChild(rejBtn);

            btnsDiv.appendChild(div);
        }
        const modal = new bootstrap.Modal(document.getElementById('requestModal'));
        modal.show();
    } catch (e) { alert('Ошибка загрузки деталей заявки'); }
}

async function assignRequest(id, userId) {
    if (!userId) return;
    const res = await apiFetch('/requests.php?action=assign', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, assigned_to: userId })
    });
    if (res.ok) renderDepartment();
}

async function deleteRequest(id) {
    if (confirm('Удалить заявку?')) {
        const res = await apiFetch(`/requests.php?action=delete&id=${id}`, { method: 'POST' });
        if (res.ok) { bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide(); renderDashboard(); }
    }
}

async function updateStatus(id, status, comment, rating = null) {
    try {
        const body = { id, status, comment };
        if (rating) body.rating = rating;
        const res = await apiFetch('/requests.php?action=update_status', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        if (res.ok) { bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide(); renderDashboard(); }
    } catch (e) { alert('Ошибка обновления статуса'); }
}

async function renderAdmin() {
    const isMobile = window.innerWidth < 768;
    el.appContent.innerHTML = `
        <div class="${isMobile ? 'p-3' : 'd-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4'}">
            ${isMobile ? '' : '<h2 class="fw-bold mb-0">Администрирование</h2>'}
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="triggerRestore()"><i class="bi bi-upload"></i> Восстановить</button>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="createBackup()"><i class="bi bi-download"></i> Бекап</button>
                <button class="btn btn-success btn-sm rounded-pill px-3" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> Экспорт</button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm overflow-hidden mb-4">
                    <div class="card-body p-4 border-start border-primary border-5">
                        <h5 class="card-title fw-bold text-primary mb-4"><i class="bi bi-building"></i> Общие настройки организации</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Название организации</label>
                                <input type="text" id="set-org-name" class="form-control" value="${escapeHTML(systemSettings.org_name || 'HOP CRM')}">
                            </div>
                            <div class="col-md-12">
                                <button class="btn btn-primary rounded-pill px-4" onclick="saveOrgSettings()">Сохранить название</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card border-0 shadow-sm overflow-hidden mb-4">
                    <div class="card-body p-4 border-start border-info border-5">
                        <h5 class="card-title fw-bold text-info mb-4"><i class="bi bi-folder2-open"></i> Обслуживание системы: Файлы</h5>
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light"><tr><th>Файл</th><th>Размер</th><th>Дата</th><th></th></tr></thead>
                                <tbody id="admin-files-table"></tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-outline-info btn-sm rounded-pill" onclick="refreshAdminFiles()">Обновить список файлов</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4 border-start border-success border-5">
                        <h5 class="card-title fw-bold text-success mb-4"><i class="bi bi-bell"></i> Настройка уведомлений</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify-sound-toggle" ${systemSettings.notify_sound ? 'checked' : ''}>
                                    <label class="form-check-label fw-medium" for="notify-sound-toggle">Звуковое уведомление</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify-browser-toggle" ${systemSettings.notify_browser ? 'checked' : ''}>
                                    <label class="form-check-label fw-medium" for="notify-browser-toggle">Браузерные уведомления</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Текст уведомления о новой заявке</label>
                                <input type="text" id="notify-new-text" class="form-control" value="${escapeHTML(systemSettings.notify_new_text || '')}" placeholder="У вас новая заявка!">
                            </div>
                            <div class="col-12 mt-2">
                                <button class="btn btn-success rounded-pill px-4" onclick="saveNotificationSettings()">Сохранить настройки уведомлений</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4 border-start border-primary border-5">
                        <h5 class="card-title fw-bold text-primary mb-4"><i class="bi bi-database"></i> Хранилище данных (JSON / MySQL)</h5>
                        <form id="storage-config-form" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Режим работы <i class="bi bi-info-circle text-muted" title="JSON для простых задач, MySQL для высокой нагрузки"></i></label>
                                <select id="storage-mode" class="form-select">
                                    <option value="json">JSON Файлы</option>
                                    <option value="mysql">MySQL БД</option>
                                </select>
                            </div>
                            <div class="col-md-9" id="mysql-settings-fields">
                                <div class="row g-2">
                                    <div class="col-md-3"><label class="form-label small fw-bold">Host</label><input type="text" id="db-host" class="form-control" placeholder="localhost"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">DB Name</label><input type="text" id="db-name" class="form-control" placeholder="crm_hop"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">User</label><input type="text" id="db-user" class="form-control" placeholder="root"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">Password</label><input type="password" id="db-pass" class="form-control" placeholder="********"></div>
                                </div>
                            </div>
                            <div class="col-12 mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary rounded-pill px-4">Сохранить конфигурацию</button>
                                <button type="button" id="init-db-btn" class="btn btn-outline-success rounded-pill px-4 hidden">Инициализировать таблицы MySQL</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-body p-4 border-start border-warning border-5">
                        <h5 class="card-title fw-bold text-warning mb-3"><i class="bi bi-shield-check"></i> Безопасность</h5>
                        <div class="form-check form-switch h5">
                            <input class="form-check-input" type="checkbox" id="auth-toggle" ${authRequired ? 'checked' : ''}>
                            <label class="form-check-label fw-medium" for="auth-toggle">Авторизация по паролю <i class="bi bi-question-circle text-muted small" title="Отключите для свободного входа при первичной настройке"></i></label>
                        </div>
                        <p class="text-muted small mb-0">Если выключено, вход свободный (Админ).</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-body p-4 border-start border-info border-5">
                        <h5 class="card-title fw-bold text-info mb-3"><i class="bi bi-megaphone"></i> Объявление</h5>
                        <div class="input-group">
                            <input type="text" id="ann-text" class="form-control" placeholder="Текст объявления">
                            <button class="btn btn-info text-white" onclick="saveAnnouncement()">Ок</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h5 class="fw-bold mb-4"><i class="bi bi-person-plus text-primary"></i> Новый пользователь</h5>
                    <form id="create-user-form">
                        <div class="mb-3"><input type="text" name="login" class="form-control rounded-3" placeholder="Логин" required></div>
                        <div class="mb-3"><input type="password" name="password" class="form-control rounded-3" placeholder="Пароль" required></div>
                        <div class="mb-3"><input type="text" name="full_name" class="form-control rounded-3" placeholder="ФИО" required></div>
                        <div class="mb-3">
                            <select name="role" class="form-select rounded-3" id="user-role-select">
                                <option value="user">Пользователь</option>
                                <option value="executor">Исполнитель</option>
                                <option value="manager">Руководитель</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <label class="small fw-bold text-primary text-uppercase mb-2 d-block"><i class="bi bi-magic"></i> Шаблоны прав</label>
                            <div class="input-group input-group-sm mb-2">
                                <select id="perm-template-select" class="form-select">
                                    <option value="">Выберите шаблон...</option>
                                </select>
                                <button type="button" class="btn btn-outline-danger" id="btn-del-template" title="Удалить шаблон"><i class="bi bi-trash"></i></button>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="text" id="new-template-name" class="form-control" placeholder="Имя нового шаблона">
                                <button type="button" class="btn btn-primary" id="btn-save-template">Сохр.</button>
                            </div>
                        </div>
                        <div class="mb-4" id="create-perms-container">
                            <label class="small fw-bold text-muted text-uppercase mb-2 d-block">Разрешения</label>
                            <div class="row g-1">
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_status" id="p-status" checked><label class="form-check-label" for="p-status">Статусы</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_delete" id="p-delete"><label class="form-check-label" for="p-delete">Удаление</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_assign" id="p-assign"><label class="form-check-label" for="p-assign">Назначение</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_edit_all" id="p-edit-all"><label class="form-check-label" for="p-edit-all">Все заявки</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_view_reports" id="p-view-reports"><label class="form-check-label" for="p-view-reports">Отчеты</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_manage_system" id="p-manage-system"><label class="form-check-label" for="p-manage-system">Система</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_export_data" id="p-export-data"><label class="form-check-label" for="p-export-data">Экспорт</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_access_chat" id="p-access-chat" checked><label class="form-check-label" for="p-access-chat">Общий чат</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_view_department" id="p-view-department"><label class="form-check-label" for="p-view-department">Заявки отдела</label></div></div>
                                <div class="col-6"><div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_view_chat" id="p-view-chat" checked><label class="form-check-label" for="p-view-chat">Виджет чата</label></div></div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill">Создать пользователя</button>
                    </form>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Журнал входов</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="date" id="log-from" class="form-control form-control-sm border-0 bg-light" style="width: 130px;">
                            <input type="date" id="log-to" class="form-control form-control-sm border-0 bg-light" style="width: 130px;">
                            <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setToday('log-from', 'log-to', refreshLoginLogs)">Сегодня</button>
                            <select id="log-user" class="form-select form-select-sm border-0 bg-light" style="width: 150px;">
                                <option value="">Все пользователи</option>
                                ${users.map(u => `<option value="${u.id}">${escapeHTML(u.full_name)}</option>`).join('')}
                            </select>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="refreshLoginLogs()"><i class="bi bi-arrow-repeat"></i></button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">Пользователь</th>
                                    <th>IP Адрес</th>
                                    <th class="pe-4">Дата и время</th>
                                </tr>
                            </thead>
                            <tbody id="login-logs-table">
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Загрузка логов...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0">Список пользователей</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-4">ID</th><th>Пользователь</th><th>Роль</th><th class="pe-4">Действия</th></tr></thead>
                            <tbody id="admin-users-table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-4">Отделы и виды работ</h5>
                    <form id="create-dept-form" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="name" class="form-control" placeholder="Новый отдел" required>
                            <select name="manager_id" class="form-select">
                                <option value="">Без главы</option>
                                ${users.filter(u => u.role === 'manager' || u.role === 'admin').map(u => `<option value="${u.id}">${escapeHTML(u.full_name)}</option>`).join('')}
                            </select>
                            <button type="submit" class="btn btn-success">+</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody id="admin-depts-table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-4">Виды работ (Услуги)</h5>
                    <form id="create-wt-form" class="mb-4">
                        <div class="row g-2">
                            <div class="col-7"><input type="text" name="name" class="form-control form-control-sm" placeholder="Название" required></div>
                            <div class="col-5">
                                <select name="department_id" class="form-select form-select-sm" required>
                                    <option value="">Отдел...</option>
                                    ${departments.map(d => `<option value="${d.id}">${escapeHTML(d.name)}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-4"><input type="number" name="sla_hours" class="form-control form-control-sm" placeholder="SLA (ч)" value="24" required></div>
                            <div class="col-8"><button type="submit" class="btn btn-success btn-sm w-100">Добавить услугу</button></div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody id="admin-wt-table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('create-dept-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const res = await apiFetch('/admin.php?action=create_department', { method: 'POST', body: JSON.stringify(data) });
        if (res.ok) { await loadLookups(); renderAdmin(); }
    });

    document.getElementById('create-wt-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const res = await apiFetch('/admin.php?action=create_worktype', { method: 'POST', body: JSON.stringify(data) });
        if (res.ok) { await loadLookups(); renderAdmin(); }
    });

    const tbody = document.getElementById('admin-users-table');
    users.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4 text-muted small" data-label="ID">${escapeHTML(u.id)}</td>
            <td data-label="ФИО">
                <div class="fw-bold">${escapeHTML(u.full_name)}</div>
                <div class="text-muted small">${escapeHTML(u.login)} ${u.is_active == 0 ? '<span class="text-danger">(Заблокирован)</span>' : ''}</div>
            </td>
            <td data-label="Роль"><span class="badge bg-light text-dark border small text-uppercase">${escapeHTML(u.role)}</span></td>
            <td class="pe-4" data-label="Действия">
                <div class="d-flex gap-1 justify-content-end">
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-2" onclick="editUser('${u.id}')" title="Редактировать"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-2" onclick="deleteUser('${u.id}')" title="Удалить"><i class="bi bi-trash"></i></button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    const dtbody = document.getElementById('admin-depts-table');
    departments.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="fw-bold">${escapeHTML(d.name)}</span></td>
            <td><span class="text-muted small">${escapeHTML(getUserName(d.manager_id))}</span></td>
            <td class="text-end"><button class="btn btn-link text-danger btn-sm p-0" onclick="deleteDept('${d.id}')"><i class="bi bi-trash"></i></button></td>
        `;
        dtbody.appendChild(tr);
    });

    const wtbody = document.getElementById('admin-wt-table');
    workTypes.forEach(w => {
        const tr = document.createElement('tr');
        const dept = departments.find(d => d.id == w.department_id);
        tr.innerHTML = `
            <td>
                <div class="fw-medium small">${escapeHTML(w.name)}</div>
                <div class="text-muted" style="font-size: 0.7rem">${escapeHTML(dept ? dept.name : '')}</div>
            </td>
            <td class="text-muted small">${w.sla_hours}ч</td>
            <td class="text-end"><button class="btn btn-link text-danger btn-sm p-0" onclick="deleteWT('${w.id}')"><i class="bi bi-trash"></i></button></td>
        `;
        wtbody.appendChild(tr);
    });

    document.getElementById('auth-toggle').addEventListener('change', async (e) => {
        const enabled = e.target.checked;
        const res = await apiFetch('/admin.php?action=update_settings', {
            method: 'POST',
            body: JSON.stringify({ auth_enabled: enabled })
        });
        if (res.ok) {
            authRequired = enabled;
            alert('Настройки сохранены. ' + (enabled ? 'Вход защищен.' : 'Вход свободный.'));
        }
    });

    window.saveAnnouncement = async () => {
        const text = document.getElementById('ann-text').value;
        const res = await apiFetch('/admin.php?action=update_settings', {
            method: 'POST',
            body: JSON.stringify({ announcement: text })
        });
        if (res.ok) alert('Объявление обновлено. Перезагрузите страницу для применения.');
    };

    window.saveNotificationSettings = async () => {
        const sound = document.getElementById('notify-sound-toggle').checked;
        const browser = document.getElementById('notify-browser-toggle').checked;
        const text = document.getElementById('notify-new-text').value;

        const res = await apiFetch('/admin.php?action=update_settings', {
            method: 'POST',
            body: JSON.stringify({
                notify_sound: sound,
                notify_browser: browser,
                notify_new_text: text
            })
        });
        if (res.ok) {
            systemSettings.notify_sound = sound;
            systemSettings.notify_browser = browser;
            systemSettings.notify_new_text = text;
            alert('Настройки уведомлений сохранены');
        }
    };

    window.saveOrgSettings = async () => {
        const name = document.getElementById('set-org-name').value;
        const res = await apiFetch('/admin.php?action=update_settings', {
            method: 'POST',
            body: JSON.stringify({ org_name: name })
        });
        if (res.ok) {
            systemSettings.org_name = name;
            document.getElementById('sidebar-org-name').innerText = name;
            document.getElementById('page-title').innerText = name;
            alert('Настройки сохранены');
        }
    };

    window.refreshAdminFiles = async () => {
        const tbody = document.getElementById('admin-files-table');
        try {
            const res = await apiFetch('/admin.php?action=list_files');
            const files = await res.json();
            tbody.innerHTML = files.map(f => `
                <tr>
                    <td><a href="uploads/${f.name}" target="_blank" class="small">${escapeHTML(f.name)}</a></td>
                    <td class="small text-muted">${(f.size / 1024).toFixed(1)} KB</td>
                    <td class="small text-muted">${new Date(f.date).toLocaleDateString()}</td>
                    <td class="text-end"><button class="btn btn-link text-danger btn-sm p-0" onclick="deleteAdminFile('${f.name}')"><i class="bi bi-trash"></i></button></td>
                </tr>
            `).join('');
        } catch (e) { tbody.innerHTML = '<tr><td colspan="4">Error</td></tr>'; }
    };

    window.deleteAdminFile = async (name) => {
        if (!confirm('Delete file?')) return;
        const res = await apiFetch(`/admin.php?action=delete_file&file=${encodeURIComponent(name)}`);
        if (res.ok) refreshAdminFiles();
    };

    refreshAdminFiles();

    if (isMobile) {
        document.querySelectorAll('.card-header .d-flex').forEach(flex => {
            flex.classList.add('flex-wrap');
        });
    }

    window.editUser = async (userId) => {
        const u = users.find(user => user.id == userId);
        if (!u) return;

        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = `
            <form id="edit-user-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Логин</label>
                        <input type="text" name="login" class="form-control" value="${escapeHTML(u.login)}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Новый пароль (оставьте пустым для сохранения)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">ФИО</label>
                        <input type="text" name="full_name" class="form-control" value="${escapeHTML(u.full_name)}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Роль</label>
                        <select name="role" class="form-select">
                            <option value="user" ${u.role==='user'?'selected':''}>Пользователь</option>
                            <option value="executor" ${u.role==='executor'?'selected':''}>Исполнитель</option>
                            <option value="manager" ${u.role==='manager'?'selected':''}>Руководитель</option>
                            <option value="admin" ${u.role==='admin'?'selected':''}>Администратор</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Отдел</label>
                        <select name="department_id" class="form-select">
                            <option value="">Нет</option>
                            ${departments.map(d => `<option value="${d.id}" ${u.department_id==d.id?'selected':''}>${escapeHTML(d.name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit-is-active" ${u.is_active != 0 ? 'checked' : ''}>
                            <label class="form-check-label" for="edit-is-active">Активен</label>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <div class="p-3 bg-light rounded-3">
                            <label class="small fw-bold text-primary text-uppercase mb-2 d-block"><i class="bi bi-magic"></i> Шаблоны прав</label>
                            <select id="edit-perm-template-select" class="form-select form-select-sm">
                                <option value="">Выберите шаблон для применения...</option>
                                ${window.currentTemplates.map(t => `<option value="${t.id}">${escapeHTML(t.name)}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="col-12" id="edit-perms-container">
                        <label class="small fw-bold text-muted text-uppercase mb-2 d-block">Разрешения</label>
                        <div class="row g-2">
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_status" id="e-p-status" ${u.permissions?.can_status ? 'checked' : ''}><label class="form-check-label" for="e-p-status">Статусы</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_delete" id="e-p-delete" ${u.permissions?.can_delete ? 'checked' : ''}><label class="form-check-label" for="e-p-delete">Удаление</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_assign" id="e-p-assign" ${u.permissions?.can_assign ? 'checked' : ''}><label class="form-check-label" for="e-p-assign">Назначение</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_edit_all" id="e-p-edit-all" ${u.permissions?.can_edit_all ? 'checked' : ''}><label class="form-check-label" for="e-p-edit-all">Все заявки</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_view_reports" id="e-p-view-reports" ${u.permissions?.can_view_reports ? 'checked' : ''}><label class="form-check-label" for="e-p-view-reports">Отчеты</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_manage_system" id="e-p-manage-system" ${u.permissions?.can_manage_system ? 'checked' : ''}><label class="form-check-label" for="e-p-manage-system">Система</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_export_data" id="e-p-export-data" ${u.permissions?.can_export_data ? 'checked' : ''}><label class="form-check-label" for="e-p-export-data">Экспорт</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_access_chat" id="e-p-access-chat" ${u.permissions?.can_access_chat !== false ? 'checked' : ''}><label class="form-check-label" for="e-p-access-chat">Общий чат</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_view_department" id="e-p-view-department" ${u.permissions?.can_view_department ?? (u.role !== 'user') ? 'checked' : ''}><label class="form-check-label" for="e-p-view-department">Заявки отдела</label></div></div>
                            <div class="col-6 col-md-4"><div class="form-check small"><input class="form-check-input" type="checkbox" name="perm_view_chat" id="e-p-view-chat" ${u.permissions?.can_view_chat !== false ? 'checked' : ''}><label class="form-check-label" for="e-p-view-chat">Виджет чата</label></div></div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">Сохранить изменения</button>
                </div>
            </form>
        `;

        const modal = new bootstrap.Modal(document.getElementById('requestModal'));
        modal.show();

        const editTplSelect = document.getElementById('edit-perm-template-select');
        if (editTplSelect) {
            editTplSelect.onchange = (e) => {
                applyTemplateToForm(e.target.value, 'edit-perms-container');
            };
        }

        document.getElementById('edit-user-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                login: formData.get('login'),
                full_name: formData.get('full_name'),
                role: formData.get('role'),
                department_id: formData.get('department_id'),
                is_active: formData.get('is_active') === 'on' ? 1 : 0,
                permissions: {}
            };
            if (formData.get('password')) data.password = formData.get('password');

            data.permissions = {
                can_status: formData.get('perm_status') === 'on',
                can_delete: formData.get('perm_delete') === 'on',
                can_assign: formData.get('perm_assign') === 'on',
                can_edit_all: formData.get('perm_edit_all') === 'on',
                can_view_reports: formData.get('perm_view_reports') === 'on',
                can_manage_system: formData.get('perm_manage_system') === 'on',
                can_export_data: formData.get('perm_export_data') === 'on',
                can_access_chat: formData.get('perm_access_chat') === 'on',
                can_view_department: formData.get('perm_view_department') === 'on',
                can_view_chat: formData.get('perm_view_chat') === 'on'
            };

            const res = await apiFetch(`/admin.php?action=update_user&id=${userId}`, {
                method: 'POST',
                body: JSON.stringify(data)
            });
            if (res.ok) {
                modal.hide();
                await loadLookups();
                renderAdmin();
            }
        };
    };

    window.deleteUser = async (userId) => {
        if (!confirm('Вы уверены, что хотите полностью удалить этого пользователя?')) return;
        const res = await apiFetch(`/admin.php?action=delete_user&id=${userId}`, { method: 'POST' });
        if (res.ok) { await loadLookups(); renderAdmin(); }
        else { const data = await res.json(); alert(data.message || 'Error deleting user'); }
    };

    document.getElementById('create-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            login: formData.get('login'),
            password: formData.get('password'),
            full_name: formData.get('full_name'),
            role: formData.get('role'),
            permissions: {
                can_status: formData.get('perm_status') === 'on',
                can_delete: formData.get('perm_delete') === 'on',
                can_assign: formData.get('perm_assign') === 'on',
                can_edit_all: formData.get('perm_edit_all') === 'on',
                can_view_reports: formData.get('perm_view_reports') === 'on',
                can_manage_system: formData.get('perm_manage_system') === 'on',
                can_export_data: formData.get('perm_export_data') === 'on',
                can_access_chat: formData.get('perm_access_chat') === 'on',
                can_view_department: formData.get('perm_view_department') === 'on',
                can_view_chat: formData.get('perm_view_chat') === 'on'
            }
        };
        const res = await apiFetch('/admin.php?action=create_user', { method: 'POST', body: JSON.stringify(data) });
        if (res.ok) { await loadLookups(); renderAdmin(); }
    });

    const loadTemplates = async () => {
        const res = await apiFetch('/admin.php?action=get_perm_templates');
        const templates = await res.json();
        const sel = document.getElementById('perm-template-select');
        sel.innerHTML = '<option value="">Выберите шаблон...</option>' +
            templates.map(t => `<option value="${t.id}">${escapeHTML(t.name)}</option>`).join('');
        window.currentTemplates = templates;
    };

    loadTemplates();

    const applyTemplateToForm = (templateId, containerId) => {
        const t = window.currentTemplates.find(tpl => tpl.id == templateId);
        if (!t) return;
        const container = document.getElementById(containerId);
        Object.entries(t.permissions).forEach(([key, val]) => {
            const input = container.querySelector(`[name="perm_${key.replace('can_', '')}"]`);
            if (input) input.checked = val;
        });
    };

    document.getElementById('perm-template-select').onchange = (e) => {
        applyTemplateToForm(e.target.value, 'create-perms-container');
    };

    document.getElementById('btn-save-template').onclick = async () => {
        const name = document.getElementById('new-template-name').value;
        if (!name) return alert('Введите имя шаблона');
        const container = document.getElementById('create-perms-container');
        const permissions = {
            can_status: container.querySelector('[name="perm_status"]').checked,
            can_delete: container.querySelector('[name="perm_delete"]').checked,
            can_assign: container.querySelector('[name="perm_assign"]').checked,
            can_edit_all: container.querySelector('[name="perm_edit_all"]').checked,
            can_view_reports: container.querySelector('[name="perm_view_reports"]').checked,
            can_manage_system: container.querySelector('[name="perm_manage_system"]').checked,
            can_export_data: container.querySelector('[name="perm_export_data"]').checked,
            can_access_chat: container.querySelector('[name="perm_access_chat"]').checked,
            can_view_department: container.querySelector('[name="perm_view_department"]').checked,
            can_view_chat: container.querySelector('[name="perm_view_chat"]').checked
        };
        const res = await apiFetch('/admin.php?action=save_perm_template', {
            method: 'POST',
            body: JSON.stringify({ name, permissions })
        });
        if (res.ok) {
            document.getElementById('new-template-name').value = '';
            await loadTemplates();
            alert('Шаблон сохранен');
        }
    };

    document.getElementById('btn-del-template').onclick = async () => {
        const id = document.getElementById('perm-template-select').value;
        if (!id) return;
        if (!confirm('Удалить шаблон?')) return;
        const res = await apiFetch(`/admin.php?action=delete_perm_template&id=${id}`, { method: 'POST' });
        if (res.ok) await loadTemplates();
    };

    window.refreshLoginLogs = async () => {
        const from = document.getElementById('log-from').value;
        const to = document.getElementById('log-to').value;
        const userId = document.getElementById('log-user').value;
        const tbody = document.getElementById('login-logs-table');

        try {
            const res = await apiFetch(`/admin.php?action=login_logs&from=${from}&to=${to}&user_id=${userId}`);
            const logs = await res.json();
            tbody.innerHTML = logs.map(l => `
                <tr>
                    <td class="ps-4" data-label="Пользователь">
                        <div class="fw-bold small">${escapeHTML(l.full_name)}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">${escapeHTML(l.login)} (ID: ${l.user_id})</div>
                    </td>
                    <td class="small text-muted" data-label="IP">${escapeHTML(l.ip)}</td>
                    <td class="pe-4 text-end" data-label="Дата">
                        <div class="small fw-medium">${new Date(l.timestamp).toLocaleDateString()}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">${new Date(l.timestamp).toLocaleTimeString()}</div>
                    </td>
                </tr>
            `).join('');
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted small">Логов не найдено</td></tr>';
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-danger small">Ошибка загрузки логов</td></tr>';
        }
    };
    refreshLoginLogs();

    // Storage Config Logic
    const storageModeSelect = document.getElementById('storage-mode');
    const initDbBtn = document.getElementById('init-db-btn');
    const mysqlFields = document.getElementById('mysql-settings-fields');
    const configForm = document.getElementById('storage-config-form');

    storageModeSelect.onchange = () => {
        const isMysql = storageModeSelect.value === 'mysql';
        mysqlFields.classList.toggle('hidden', !isMysql);
        initDbBtn.classList.toggle('hidden', !isMysql);
    };

    // Load current config
    try {
        const cRes = await apiFetch('/admin.php?action=get_config');
        const config = await cRes.json();
        storageModeSelect.value = config.mode || 'json';
        if (config.mysql) {
            document.getElementById('db-host').value = config.mysql.host || '';
            document.getElementById('db-name').value = config.mysql.dbname || '';
            document.getElementById('db-user').value = config.mysql.user || '';
        }
        storageModeSelect.onchange();
    } catch (e) {}

    configForm.onsubmit = async (e) => {
        e.preventDefault();
        const mode = storageModeSelect.value;
        const config = { mode };
        if (mode === 'mysql') {
            config.mysql = {
                host: document.getElementById('db-host').value,
                dbname: document.getElementById('db-name').value,
                user: document.getElementById('db-user').value,
                password: document.getElementById('db-pass').value
            };
            if (config.mysql.password === '********') delete config.mysql.password;
        }
        const res = await apiFetch('/admin.php?action=update_config', {
            method: 'POST',
            body: JSON.stringify(config)
        });
        if (res.ok) alert('Конфигурация сохранена. Перезагрузите страницу.');
    };

    initDbBtn.onclick = async () => {
        if (!confirm('Это создаст необходимые таблицы в базе данных. Продолжить?')) return;
        const res = await apiFetch('/admin.php?action=init_mysql');
        const data = await res.json();
        if (data.success) alert('Таблицы успешно созданы!');
        else alert('Ошибка инициализации: ' + (data.message || 'Check logs'));
    };
}

window.triggerRestore = async () => {
        const file = prompt('Введите имя файла бекапа из папки data (например backup_...zip):');
    if (!file) return;
    try {
        const res = await apiFetch(`/admin.php?action=restore&file=${encodeURIComponent(file)}`);
        const data = await res.json(); alert(data.message); location.reload();
    } catch (e) { alert('Ошибка восстановления'); }
};

window.createBackup = async () => {
    try {
        const res = await apiFetch('/admin.php?action=backup');
        const data = await res.json(); alert(`Бекап создан: ${data.file}`);
    } catch (e) { alert('Ошибка создания бекапа'); }
};

window.exportCSV = () => { window.open(`${API_BASE}/requests.php?action=export&token=${token}`, '_blank'); };

window.deleteDept = async (id) => {
    if (confirm('Удалить отдел?')) {
        await apiFetch(`/admin.php?action=delete_department&id=${id}`, { method: 'POST' });
        await loadLookups(); renderAdmin();
    }
};

window.deleteWT = async (id) => {
    if (confirm('Удалить вид работ?')) {
        await apiFetch(`/admin.php?action=delete_worktype&id=${id}`, { method: 'POST' });
        await loadLookups(); renderAdmin();
    }
};

async function renderHelp() {
    const isMobile = window.innerWidth < 768;
    el.appContent.innerHTML = `
        <div class="${isMobile ? 'p-3' : ''}">
        ${isMobile ? '' : '<h2 class="fw-bold mb-4">Подробное руководство пользователя HOP</h2>'}
        <div class="row g-3 slide-in">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-4 mb-4 border-start border-primary border-5">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge me-2"></i> 0. Роли в системе HOP</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <thead><tr class="text-muted small uppercase"><th>Роль</th><th>Описание</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge bg-light text-dark border w-100">User</span></td><td>Может создавать заявки, отслеживать только свои, вести чат и закрывать их.</td></tr>
                                <tr><td><span class="badge bg-light text-dark border w-100">Executor</span></td><td>Видит заявки своего отдела, переводит их в "В работу" и "Выполнена".</td></tr>
                                <tr><td><span class="badge bg-light text-dark border w-100">Manager</span></td><td>Видит все заявки отдела, назначает исполнителей, просматривает отчеты.</td></tr>
                                <tr><td><span class="badge bg-light text-dark border w-100">Admin</span></td><td>Полный доступ ко всем функциям, управление пользователями и системными настройками.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-grid-1x2 me-2"></i> 1. Мои заявки (Рабочий стол пользователя)</h5>
                    <p>Этот раздел является основным для обычных сотрудников. Здесь вы видите все поданные вами запросы в системе HOP.</p>
                    <ul>
                        <li><strong>Фильтрация:</strong> Используйте календарь сверху, чтобы найти заявки за определенный период. Кнопка "Сегодня" быстро установит текущую дату.</li>
                        <li><strong>Статусы:</strong> Цветные индикаторы показывают на каком этапе находится ваш запрос (Новая, В работе, Выполнена и т.д.).</li>
                        <li><strong>Просмотр деталей:</strong> Нажмите на номер заявки (например, HOP-0001), чтобы открыть окно с подробностями и чатом.</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 mb-4 h-100">
                    <h5 class="fw-bold text-success mb-3"><i class="bi bi-plus-circle me-2"></i> 2. Создание заявки</h5>
                    <p>Для подачи нового обращения:</p>
                    <ol>
                        <li>Выберите <strong>Тип работ</strong> (Электрика, Сантехника и т.д.). Система автоматически направит заявку в нужный отдел.</li>
                        <li>Установите <strong>Приоритет</strong>. "Высокий" приоритет сигнализирует об аварийной ситуации.</li>
                        <li>Укажите точное <strong>Место выполнения</strong> (корпус, этаж, кабинет).</li>
                        <li>Подробно опишите проблему в поле <strong>Описание</strong>.</li>
                        <li>При необходимости прикрепите фото поломки для ускорения диагностики.</li>
                    </ol>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 mb-4 h-100">
                    <h5 class="fw-bold text-warning mb-3"><i class="bi bi-people me-2"></i> 3. Заявки отдела (Для руководителей)</h5>
                    <p>Специальный раздел для управления потоком работ:</p>
                    <ul>
                        <li><strong>Назначение:</strong> Выберите исполнителя из выпадающего списка в строке заявки. Исполнитель мгновенно увидит её в своем списке.</li>
                        <li><strong>Контроль:</strong> Следите за тем, чтобы заявки не застаивались в статусе "Новая".</li>
                        <li><strong>Переназначение:</strong> Вы можете сменить исполнителя в любой момент, если это необходимо.</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold text-info mb-3"><i class="bi bi-chat-dots me-2"></i> 4. Чат и Взаимодействие</h5>
                    <p>Внутри каждой заявки есть "Чат поддержки":</p>
                    <ul>
                        <li>Вы можете задать уточняющий вопрос исполнителю.</li>
                        <li>Исполнитель может запросить дополнительную информацию.</li>
                        <li>Все сообщения сохраняются в истории заявки.</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold text-danger mb-3"><i class="bi bi-check2-all me-2"></i> 5. Подтверждение и Оценка</h5>
                    <p>Когда работа выполнена:</p>
                    <ul>
                        <li>Заявка переходит в статус <strong>"Выполнена"</strong>.</li>
                        <li>У вас появится кнопка <strong>"Принять и закрыть"</strong>.</li>
                        <li>Пожалуйста, поставьте оценку от 1 до 5 звезд. Это важно для рейтинга отделов.</li>
                        <li>Если работа выполнена некачественно, нажмите <strong>"На доработку"</strong> и укажите причину.</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-4 mb-4 bg-dark text-white">
                    <h5 class="fw-bold text-info mb-3"><i class="bi bi-shield-lock me-2"></i> 6. Администрирование (Для системных администраторов)</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Пользователи</h6>
                            <p class="small opacity-75">Создание аккаунтов, сброс паролей и настройка прав доступа (Permissions).</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Структура</h6>
                            <p class="small opacity-75">Настройка отделов и привязка к ним типов работ (услуг) с указанием SLA (нормативного времени выполнения).</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Данные</h6>
                            <p class="small opacity-75">Переключение между JSON и MySQL, создание резервных копий (Backup) и экспорт данных в Excel.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-question-diamond me-2"></i> 7. Часто задаваемые вопросы (FAQ)</h5>
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold px-0" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Как быстро мою заявку выполнят?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-0 small text-muted">
                                    У каждого типа работ есть свой норматив времени (SLA). Обычно это от 4 до 24 часов. Вы можете увидеть статус заявки в реальном времени в своем личном кабинете.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold px-0" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Что делать, если заявку отклонили?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-0 small text-muted">
                                    Если заявка отклонена ("Rejected"), проверьте комментарий исполнителя в истории изменений или чате. Возможно, требуется уточнение данных или предоставление доступа в помещение.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold px-0" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Как изменить приоритет уже созданной заявки?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-0 small text-muted">
                                    После создания заявки изменить приоритет может только руководитель отдела-исполнителя или администратор. Напишите соответствующую просьбу в чат к заявке.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 text-center mb-5">
                <div class="p-4 bg-white border rounded-4 shadow-sm">
                    <h6 class="fw-bold">О разработчике</h6>
                    <p class="mb-1">Разработка и техническая поддержка системы: <strong>Коваженко С.Б.</strong></p>
                    <p class="mb-0">Персональный сайт и контакты: <a href="https://wes.by" target="_blank" class="text-decoration-none fw-bold">wes.by</a></p>
                    <div class="mt-3 small text-muted">Версия системы: 1.0.4 | 2026</div>
                </div>
            </div>
        </div>
    `;
}

function startPolling() {
    setInterval(async () => {
        if (!token) return;
        try {
            // Check for new requests (assigned to me or in my department if manager)
            let endpoint = '/requests.php?action=my';
            if (currentUser.role === 'manager' || currentUser.role === 'admin') endpoint = '/requests.php?action=department';

            const res = await apiFetch(endpoint);
            const requests = await res.json();

            if (lastRequestCount !== null && requests.length > lastRequestCount) {
                // New request found!
                if (systemSettings.notify_sound) {
                    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                    audio.play().catch(e => console.log('Audio play blocked'));
                }
                if (systemSettings.notify_browser && Notification.permission === 'granted') {
                    new Notification('HOP CRM', {
                        body: systemSettings.notify_new_text || 'У вас новая заявка!',
                        icon: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/icons/tools.svg'
                    });
                }
            }
            lastRequestCount = requests.length;
        } catch (e) {}
    }, 30000); // Every 30 seconds
}

async function renderGlobalChat() {
    el.appContent.innerHTML = `
        <div class="card border-0 shadow-sm overflow-hidden" style="height: calc(100vh - 150px); display: flex; flex-direction: column;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-chat-quote-fill me-2"></i> Общий чат системы</h5>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="renderGlobalChat()"><i class="bi bi-arrow-repeat"></i></button>
            </div>
            <div class="card-body bg-light p-4" id="global-chat-box" style="flex: 1; overflow-y: auto;">
                <div class="text-center py-5 text-muted small">Загрузка сообщений...</div>
            </div>
            <div class="card-footer bg-white p-3 border-0">
                <form id="global-chat-form" class="d-flex gap-2">
                    <input type="text" id="global-chat-input" class="form-control rounded-pill border-0 bg-light px-4" placeholder="Ваше сообщение всему персоналу..." required>
                    <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 50px; height: 50px;"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    `;

    const box = document.getElementById('global-chat-box');
    const load = async () => {
        try {
            const res = await apiFetch('/chat.php?action=list');
            const msgs = await res.json();
            box.innerHTML = msgs.map(m => `
                <div class="mb-3 ${m.user_id == currentUser.id ? 'text-end' : ''}">
                    <div class="d-inline-block px-4 py-2 rounded-4 ${m.user_id == currentUser.id ? 'bg-primary text-white' : 'bg-white shadow-sm'}" style="max-width: 80%;">
                        <div class="small fw-bold mb-1" style="font-size: 0.7rem; opacity: 0.8;">${escapeHTML(m.user_name)}</div>
                        <div class="mb-1">${escapeHTML(m.message)}</div>
                        <div class="small opacity-50" style="font-size: 0.6rem;">${new Date(m.created_at).toLocaleTimeString()}</div>
                    </div>
                </div>
            `).join('');
            box.scrollTop = box.scrollHeight;
        } catch (e) { box.innerHTML = 'Ошибка загрузки чата'; }
    };

    load();
    const interval = setInterval(() => { if (document.getElementById('global-chat-box')) load(); else clearInterval(interval); }, 5000);

    document.getElementById('global-chat-form').onsubmit = async (e) => {
        e.preventDefault();
        const input = document.getElementById('global-chat-input');
        const res = await apiFetch('/chat.php?action=send', {
            method: 'POST',
            body: JSON.stringify({ message: input.value })
        });
        if (res.ok) { input.value = ''; load(); }
    };
}

async function renderReports(from = '', to = '') {
    const isMobile = window.innerWidth < 768;
    el.appContent.innerHTML = `
        <div class="${isMobile ? 'p-3' : 'd-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4'}">
            ${isMobile ? '' : '<h2 class="fw-bold mb-0">Аналитика и отчеты</h2>'}
            <div class="d-flex flex-wrap gap-2 align-items-center bg-white p-2 rounded-3 shadow-sm">
                <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                    <i class="bi bi-filter-left text-muted ms-1"></i>
                    <input type="date" id="rep-from" class="form-control form-control-sm border-0" value="${from}">
                    <span class="text-muted small">до</span>
                    <input type="date" id="rep-to" class="form-control form-control-sm border-0" value="${to}">
                </div>
                <div class="d-flex gap-2 w-100 w-md-auto">
                    <button class="btn btn-outline-secondary btn-sm rounded-2 flex-grow-1" onclick="setToday('rep-from', 'rep-to', () => document.getElementById('rep-filter').click())">Сегодня</button>
                    <button class="btn btn-primary btn-sm rounded-2 px-3 flex-grow-1" id="rep-filter">Обновить</button>
                </div>
            </div>
        </div>
        <div id="reports-container" class="row g-3"><div class="col-md-12 text-center py-5"><div class="spinner-border text-primary"></div></div></div>
    `;

    document.getElementById('rep-filter').onclick = () => {
        const f = document.getElementById('rep-from').value;
        const t = document.getElementById('rep-to').value;
        renderReports(f, t);
    };

    try {
        const query = (from || to) ? `&from=${from}&to=${to}` : '';
        const res = await apiFetch(`/reports.php?action=summary${query}`);
        const s = await res.json();

        el.appContent.querySelector('#reports-container').innerHTML = `
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 p-3 bg-primary text-white">
                    <div class="small fw-bold text-uppercase opacity-75">Всего заявок</div>
                    <div class="display-5 fw-bold my-2">${s.total}</div>
                    <div class="small"><i class="bi bi-arrow-up-short"></i> За весь период</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="small fw-bold text-uppercase text-muted">Средний рейтинг</div>
                    <div class="display-5 fw-bold my-2 text-success">${s.avg_rating} <i class="bi bi-star-fill fs-4 align-middle"></i></div>
                    <div class="small text-muted">По отзывам сотрудников</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="small fw-bold text-uppercase text-muted">Просрочено</div>
                    <div class="display-5 fw-bold my-2 text-danger">${s.overdue}</div>
                    <div class="small text-danger fw-medium">Требуют внимания</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="small fw-bold text-uppercase text-muted">Ср. время решения</div>
                    <div class="display-5 fw-bold my-2 text-info">${s.avg_res_time_hours} <span class="fs-4">ч.</span></div>
                    <div class="small text-muted">От "В работе" до "Выполнено"</div>
                </div>
            </div>

            <div class="col-md-6 mt-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Распределение по статусам</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1 small"><span class="fw-medium">Новые</span><span>${s.status_dist.new}</span></div>
                            <div class="progress" style="height: 6px;"><div class="progress-bar bg-info" style="width: ${s.total ? (s.status_dist.new/s.total*100) : 0}%"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1 small"><span class="fw-medium">В работе</span><span>${s.status_dist.in_progress}</span></div>
                            <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: ${s.total ? (s.status_dist.in_progress/s.total*100) : 0}%"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1 small"><span class="fw-medium">Выполнены / Закрыты</span><span>${s.status_dist.completed + s.status_dist.closed}</span></div>
                            <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: ${s.total ? ((s.status_dist.completed + s.status_dist.closed)/s.total*100) : 0}%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Приоритеты</div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">Высокий <span class="badge bg-danger rounded-pill">${s.priority_dist.high}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">Обычный <span class="badge bg-primary rounded-pill">${s.priority_dist.normal}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 pb-0">Низкий <span class="badge bg-secondary rounded-pill">${s.priority_dist.low}</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 fw-bold">Эффективность отделов</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 ${isMobile ? 'mobile-card-table' : ''}">
                            <thead class="table-light"><tr><th class="ps-4">Отдел</th><th>Всего</th><th>Выполнено</th><th class="text-danger">Просрочено</th><th class="pe-4">Рейтинг</th></tr></thead>
                            <tbody>
                                ${s.dept_stats.map(d => `<tr><td class="ps-4 fw-medium" data-label="Отдел">${escapeHTML(d.name)}</td><td data-label="Всего">${d.total}</td><td data-label="Выполнено">${d.completed}</td><td class="text-danger" data-label="Просрочено">${d.overdue}</td><td class="pe-4 text-success fw-bold" data-label="Рейтинг">${d.avg_rating}</td></tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 fw-bold">Загрузка исполнителей</div>
                    <div id="executor-stats" class="list-group list-group-flush"></div>
                </div>
            </div>
        `;
        const execRes = await apiFetch(`/reports.php?action=executors${query}`);
        const executors = await execRes.json();
        const execList = document.getElementById('executor-stats');
        executors.forEach(ex => {
            const item = document.createElement('div'); item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `${escapeHTML(ex.full_name)} <span class="badge bg-primary rounded-pill">${ex.active_requests} активных</span>`;
            execList.appendChild(item);
        });
    } catch (e) {
        console.error('Reports load failed:', e);
        el.appContent.innerHTML = `
            <h2>Отчеты</h2>
            <div class="alert alert-danger shadow-sm border-0 rounded-4 p-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    <div>
                        <h5 class="fw-bold mb-1">Ошибка загрузки отчетов</h5>
                        <p class="mb-0 small opacity-75">Не удалось получить данные с сервера. Пожалуйста, проверьте соединение или права доступа.</p>
                    </div>
                </div>
                <button class="btn btn-outline-danger btn-sm mt-3 rounded-pill" onclick="renderReports()">Попробовать снова</button>
            </div>
        `;
    }
}
