const API_BASE = 'api';
let currentUser = null;
let token = localStorage.getItem('token');
let workTypes = [];
let users = [];
let departments = [];
let authRequired = true;

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

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Check system config
    try {
        const cfgRes = await fetch(`${API_BASE}/auth.php?action=config`);
        const cfg = await cfgRes.json();
        authRequired = cfg.auth_required;
        if (cfg.announcement) {
            const banner = document.getElementById('announcement-banner');
            banner.innerHTML = `<i class="bi bi-megaphone-fill me-2"></i> ${escapeHTML(cfg.announcement)}`;
            banner.classList.remove('hidden');
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
        renderDashboard();
    } else if (token) {
        const success = await fetchUser();
        if (success) {
            await loadLookups();
            showLayout();
            renderDashboard();
        } else {
            showLogin();
        }
    } else {
        showLogin();
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
    el.userInfo.innerHTML = `
        <div class="fw-bold">${escapeHTML(currentUser.full_name)}</div>
        <div class="text-white-50 small">${escapeHTML(currentUser.role)}</div>
    `;

    const adminEl = document.getElementById('nav-admin');
    const reportsEl = document.getElementById('nav-reports');
    const deptEl = document.getElementById('nav-department');

    adminEl.classList.toggle('hidden', currentUser.role !== 'admin');
    reportsEl.classList.toggle('hidden', !['admin', 'manager'].includes(currentUser.role));
    deptEl.classList.toggle('hidden', currentUser.role === 'user');

    // Clone nav to mobile
    const mobileNav = document.getElementById('mobile-nav');
    mobileNav.innerHTML = el.mainNav.innerHTML;

    // Setup mobile nav clicks
    mobileNav.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link) {
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('mobileSidebar'));
            if (offcanvas) offcanvas.hide();
        }
    });
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

document.addEventListener('click', (e) => {
    const link = e.target.closest('.nav-link');
    if (!link) return;

    const view = link.dataset.view;
    if (view) {
        e.preventDefault();
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        // Mark both mobile and desktop links as active if they point to same view
        document.querySelectorAll(`.nav-link[data-view="${view}"]`).forEach(l => l.classList.add('active'));
        switch (view) {
            case 'dashboard': renderDashboard(); break;
            case 'department': renderDepartment(); break;
            case 'create': renderCreate(); break;
            case 'admin': renderAdmin(); break;
            case 'reports': renderReports(); break;
        }
    }
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

async function renderDashboard(from = '', to = '') {
    el.appContent.innerHTML = `
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h2 class="fw-bold mb-0">Мои заявки</h2>
            <div class="d-flex gap-2 align-items-center bg-white p-2 rounded-3 shadow-sm">
                <i class="bi bi-calendar3 text-muted ms-1"></i>
                <input type="date" id="dash-from" class="form-control form-control-sm border-0" value="${from}">
                <span class="text-muted small">до</span>
                <input type="date" id="dash-to" class="form-control form-control-sm border-0" value="${to}">
                <button class="btn btn-primary btn-sm rounded-2 px-3" onclick="filterDashboard()">Фильтр</button>
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
            tr.innerHTML = `
                <td class="ps-4"><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td>
                <td><span class="small fw-medium">${escapeHTML(getWorkTypeName(r.work_type_id))}</span></td>
                <td class="d-none d-md-table-cell text-muted small">${escapeHTML(r.description.substring(0, 50))}${r.description.length > 50 ? '...' : ''}</td>
                <td>${getStatusBadge(r.status)}</td>
                <td class="pe-4 text-end text-muted small">${new Date(r.created_at).toLocaleDateString()}</td>
            `;
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.req-link').forEach(link => {
            link.addEventListener('click', (e) => { e.preventDefault(); showRequestDetails(link.dataset.id); });
        });
    } catch (e) { el.appContent.innerHTML += '<div class="alert alert-danger">Ошибка загрузки данных</div>'; }
}

async function renderCreate() {
    el.appContent.innerHTML = `
        <h2 class="fw-bold mb-4">Создать заявку</h2>
        <div class="card border-0 shadow-sm p-4" style="max-width: 700px">
        <form id="create-request-form">
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
    el.appContent.innerHTML = `
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h2 class="fw-bold mb-0">Заявки отдела</h2>
            <div class="d-flex gap-2 align-items-center bg-white p-2 rounded-3 shadow-sm">
                <i class="bi bi-calendar3 text-muted ms-1"></i>
                <input type="date" id="dept-from" class="form-control form-control-sm border-0" value="${from}">
                <span class="text-muted small">до</span>
                <input type="date" id="dept-to" class="form-control form-control-sm border-0" value="${to}">
                <button class="btn btn-primary btn-sm rounded-2 px-3" onclick="filterDept()">Фильтр</button>
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

            tr.innerHTML = `
                <td class="ps-4"><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td>
                <td><span class="small fw-medium">${escapeHTML(getWorkTypeName(r.work_type_id))}</span></td>
                <td>${getStatusBadge(r.status)}</td>
                <td class="pe-4">${assignHtml}</td>
            `;
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
    el.appContent.innerHTML = `
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h2 class="fw-bold mb-0">Администрирование</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="triggerRestore()"><i class="bi bi-upload"></i> Восстановить</button>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="createBackup()"><i class="bi bi-download"></i> Бекап</button>
                <button class="btn btn-success btn-sm rounded-pill px-3" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> Экспорт</button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-4 border-start border-primary border-5">
                        <h5 class="card-title fw-bold text-primary mb-4"><i class="bi bi-database"></i> Хранилище данных (JSON / MySQL)</h5>
                        <form id="storage-config-form" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Режим работы</label>
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
                            <label class="form-check-label fw-medium" for="auth-toggle">Авторизация по паролю</label>
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
                        <div class="mb-4">
                            <label class="small fw-bold text-muted text-uppercase mb-2 d-block">Разрешения</label>
                            <div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_status" id="p-status" checked><label class="form-check-label" for="p-status">Изменение статусов</label></div>
                            <div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_delete" id="p-delete"><label class="form-check-label" for="p-delete">Удаление заявок</label></div>
                            <div class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="perm_assign" id="p-assign"><label class="form-check-label" for="p-assign">Назначение исполнителей</label></div>
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
            <td class="ps-4 text-muted small">${escapeHTML(u.id)}</td>
            <td>
                <div class="fw-bold">${escapeHTML(u.full_name)}</div>
                <div class="text-muted small">${escapeHTML(u.login)}</div>
            </td>
            <td><span class="badge bg-light text-dark border small text-uppercase">${escapeHTML(u.role)}</span></td>
            <td class="pe-4">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-2" onclick="resetUserPassword('${u.id}')" title="Сброс пароля"><i class="bi bi-key"></i></button>
                <button class="btn btn-sm btn-outline-danger rounded-pill px-2">Отключить</button>
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

    window.resetUserPassword = async (userId) => {
        const newPass = prompt('Введите новый пароль для пользователя:');
        if (!newPass) return;
        const res = await apiFetch('/admin.php?action=reset_password', {
            method: 'POST',
            body: JSON.stringify({ user_id: userId, password: newPass })
        });
        if (res.ok) alert('Пароль успешно изменен');
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
                can_assign: formData.get('perm_assign') === 'on'
            }
        };
        const res = await apiFetch('/admin.php?action=create_user', { method: 'POST', body: JSON.stringify(data) });
        if (res.ok) { await loadLookups(); renderAdmin(); }
    });

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
                    <td class="ps-4">
                        <div class="fw-bold small">${escapeHTML(l.full_name)}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">${escapeHTML(l.login)} (ID: ${l.user_id})</div>
                    </td>
                    <td class="small text-muted">${escapeHTML(l.ip)}</td>
                    <td class="pe-4 text-end">
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
    const file = prompt('Введите имя файла бекапа из папки data:');
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

async function renderReports(from = '', to = '') {
    el.appContent.innerHTML = `
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h2 class="fw-bold mb-0">Аналитика и отчеты</h2>
            <div class="d-flex gap-2 align-items-center bg-white p-2 rounded-3 shadow-sm">
                <i class="bi bi-filter-left text-muted ms-1"></i>
                <input type="date" id="rep-from" class="form-control form-control-sm border-0" value="${from}">
                <span class="text-muted small">до</span>
                <input type="date" id="rep-to" class="form-control form-control-sm border-0" value="${to}">
                <button class="btn btn-primary btn-sm rounded-2 px-3" id="rep-filter">Обновить</button>
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
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-4">Отдел</th><th>Всего</th><th>Выполнено</th><th class="text-danger">Просрочено</th><th class="pe-4">Рейтинг</th></tr></thead>
                            <tbody>
                                ${s.dept_stats.map(d => `<tr><td class="ps-4 fw-medium">${escapeHTML(d.name)}</td><td>${d.total}</td><td>${d.completed}</td><td class="text-danger">${d.overdue}</td><td class="pe-4 text-success fw-bold">${d.avg_rating}</td></tr>`).join('')}
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
    } catch (e) { el.appContent.innerHTML = '<h2>Отчеты</h2><div class="alert alert-danger">Ошибка загрузки отчетов</div>'; }
}
