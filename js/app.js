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
    el.userInfo.innerText = `${currentUser.full_name} (${currentUser.role})`;
    document.getElementById('nav-admin').classList.toggle('hidden', currentUser.role !== 'admin');
    document.getElementById('nav-reports').classList.toggle('hidden', !['admin', 'manager'].includes(currentUser.role));
    document.getElementById('nav-department').classList.toggle('hidden', currentUser.role === 'user');
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

el.mainNav.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    const view = link?.dataset.view;
    if (view) {
        e.preventDefault();
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Мои заявки</h2>
            <div class="d-flex gap-2">
                <input type="date" id="dash-from" class="form-control form-control-sm" value="${from}">
                <input type="date" id="dash-to" class="form-control form-control-sm" value="${to}">
                <button class="btn btn-outline-primary btn-sm" onclick="filterDashboard()">Ок</button>
            </div>
        </div>
        <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Номер</th><th>Тип</th><th>Описание</th><th>Статус</th><th>Дата</th></tr></thead><tbody id="req-table"></tbody></table></div>
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
            tr.innerHTML = `<td><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td><td>${escapeHTML(getWorkTypeName(r.work_type_id))}</td><td>${escapeHTML(r.description)}</td><td><span class="badge bg-secondary">${escapeHTML(r.status)}</span></td><td>${new Date(r.created_at).toLocaleDateString()}</td>`;
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.req-link').forEach(link => {
            link.addEventListener('click', (e) => { e.preventDefault(); showRequestDetails(link.dataset.id); });
        });
    } catch (e) { el.appContent.innerHTML += '<div class="alert alert-danger">Ошибка загрузки данных</div>'; }
}

async function renderCreate() {
    el.appContent.innerHTML = `
        <h2>Создать заявку</h2>
        <form id="create-request-form" style="max-width: 600px">
            <div class="mb-3">
                <label class="form-label">Тип работ</label>
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
            <button type="submit" id="cr-submit" class="btn btn-primary">Отправить</button>
        </form>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Заявки отдела</h2>
            <div class="d-flex gap-2">
                <input type="date" id="dept-from" class="form-control form-control-sm" value="${from}">
                <input type="date" id="dept-to" class="form-control form-control-sm" value="${to}">
                <button class="btn btn-outline-primary btn-sm" onclick="filterDept()">Ок</button>
            </div>
        </div>
        <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Номер</th><th>Тип</th><th>Статус</th><th>Исполнитель</th></tr></thead><tbody id="dept-req-table"></tbody></table></div>
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

            tr.innerHTML = `<td><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td><td>${escapeHTML(getWorkTypeName(r.work_type_id))}</td><td><span class="badge bg-info text-dark">${escapeHTML(r.status)}</span></td><td>${assignHtml}</td>`;
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
                    <p><strong>Номер:</strong> ${escapeHTML(req.number)}</p>
                    <p><strong>Статус:</strong> <span class="badge bg-primary">${escapeHTML(req.status)}</span></p>
                    <p><strong>Приоритет:</strong> ${escapeHTML(req.priority)}</p>
                    <p><strong>Место:</strong> ${escapeHTML(req.location)}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Создана:</strong> ${new Date(req.created_at).toLocaleString()}</p>
                    <p><strong>Заявитель:</strong> ${escapeHTML(getUserName(req.requester_id))}</p>
                    <p><strong>Исполнитель:</strong> ${escapeHTML(getUserName(req.assigned_to))}</p>
                </div>
            </div>
            <hr>
            <h6>Описание</h6>
            <p>${escapeHTML(req.description)}</p>
            ${req.file_path ? `<p><strong>Файл:</strong> <a href="${req.file_path}" target="_blank">${escapeHTML(req.file_original_name)}</a></p>` : ''}
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6>История</h6>
                    <ul class="list-unstyled" style="max-height: 200px; overflow-y: auto;">
                        ${history.map(h => `<li class="small"><strong>${new Date(h.changed_at).toLocaleString()}:</strong> ${escapeHTML(h.status)} - ${escapeHTML(h.comment)}</li>`).join('')}
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Чат / Комментарии</h6>
                    <div id="chat-box" class="bg-light p-2 mb-2 border rounded" style="height: 150px; overflow-y: auto;"></div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="chat-input" class="form-control" placeholder="Сообщение...">
                        <button class="btn btn-primary" id="chat-send">></button>
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
            div.className = 'mt-3 p-3 bg-light border rounded';
            div.innerHTML = `
                <label class="form-label">Оцените работу (1-5):</label>
                <div class="d-flex mb-3">
                    ${[1,2,3,4,5].map(i => `<div class="form-check me-3"><input class="form-check-input" type="radio" name="req-rating" value="${i}" id="r${i}" ${i==5?'checked':''}> <label class="form-check-label" for="r${i}">${i}</label></div>`).join('')}
                </div>
                <button class="btn btn-success btn-sm w-100" id="confirm-btn">Подтвердить и закрыть</button>
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
        <div class="d-flex justify-content-between">
            <h2>Администрирование</h2>
            <div>
                <button class="btn btn-outline-danger btn-sm me-2" onclick="triggerRestore()">Восстановить из файла</button>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="createBackup()">Создать бекап</button>
                <button class="btn btn-outline-success btn-sm" onclick="exportCSV()">Экспорт CSV</button>
            </div>
        </div>
        <hr>
        <div class="card mb-4 border-warning">
            <div class="card-body">
                <h5 class="card-title text-warning">Безопасность</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="auth-toggle" ${authRequired ? 'checked' : ''}>
                    <label class="form-check-label" for="auth-toggle">Включить вход по логину и паролю</label>
                </div>
                <small class="text-muted">Если выключено, любой вход будет автоматически под ролью Администратора.</small>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <h4>Создать пользователя</h4>
                <form id="create-user-form" class="row g-3 mb-4">
                    <div class="col-md-3"><input type="text" name="login" class="form-control" placeholder="Логин" required></div>
                    <div class="col-md-3"><input type="password" name="password" class="form-control" placeholder="Пароль" required></div>
                    <div class="col-md-3"><input type="text" name="full_name" class="form-control" placeholder="ФИО" required></div>
                    <div class="col-md-2">
                        <select name="role" class="form-select" id="user-role-select">
                            <option value="user">Пользователь</option>
                            <option value="executor">Исполнитель</option>
                            <option value="manager">Руководитель</option>
                            <option value="admin">Админ</option>
                        </select>
                    </div>
                    <div class="col-md-1"><button type="submit" class="btn btn-success w-100">+</button></div>
                    <div class="col-12 mt-2">
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="perm_status" id="p-status" checked><label class="form-check-label" for="p-status">Смена статуса</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="perm_delete" id="p-delete"><label class="form-check-label" for="p-delete">Удаление</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="perm_assign" id="p-assign"><label class="form-check-label" for="p-assign">Назначение</label></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <hr>
        <h4>Пользователи</h4>
        <div id="users-list"></div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <h4>Создать отдел</h4>
                <form id="create-dept-form" class="row g-3 mb-4">
                    <div class="col-md-5"><input type="text" name="name" class="form-control" placeholder="Название отдела" required></div>
                    <div class="col-md-5">
                        <select name="manager_id" class="form-select">
                            <option value="">Без руководителя</option>
                            ${users.filter(u => u.role === 'manager' || u.role === 'admin').map(u => `<option value="${u.id}">${escapeHTML(u.full_name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-success w-100">+</button></div>
                </form>
            </div>
        </div>
        <h4>Отделы</h4>
        <div id="depts-list"></div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <h4>Создать вид работ</h4>
                <form id="create-wt-form" class="row g-3 mb-4">
                    <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Вид работ" required></div>
                    <div class="col-md-3">
                        <select name="department_id" class="form-select" required>
                            <option value="">Выберите отдел</option>
                            ${departments.map(d => `<option value="${d.id}">${escapeHTML(d.name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" name="sla_hours" class="form-control" placeholder="SLA (часы)" value="24" required></div>
                    <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Описание"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-success w-100">+</button></div>
                </form>
            </div>
        </div>
        <h4>Справочник видов работ</h4>
        <div id="wt-list"></div>
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

    const dList = document.getElementById('depts-list');
    dList.innerHTML = `<table class="table"><thead><tr><th>ID</th><th>Название</th><th>Руководитель</th><th>Действия</th></tr></thead><tbody id="admin-depts-table"></tbody></table>`;
    const dtbody = document.getElementById('admin-depts-table');
    departments.forEach(d => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${d.id}</td><td>${escapeHTML(d.name)}</td><td>${escapeHTML(getUserName(d.manager_id))}</td><td><button class="btn btn-sm btn-outline-danger" onclick="deleteDept('${d.id}')">Удалить</button></td>`;
        dtbody.appendChild(tr);
    });

    const wList = document.getElementById('wt-list');
    wList.innerHTML = `<table class="table"><thead><tr><th>Название</th><th>Отдел</th><th>SLA</th><th>Действия</th></tr></thead><tbody id="admin-wt-table"></tbody></table>`;
    const wtbody = document.getElementById('admin-wt-table');
    workTypes.forEach(w => {
        const tr = document.createElement('tr');
        const dept = departments.find(d => d.id == w.department_id);
        tr.innerHTML = `<td>${escapeHTML(w.name)}</td><td>${escapeHTML(dept ? dept.name : w.department_id)}</td><td>${w.sla_hours} ч.</td><td><button class="btn btn-sm btn-outline-danger" onclick="deleteWT('${w.id}')">Удалить</button></td>`;
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

    const list = document.getElementById('users-list');
    list.innerHTML = `<table class="table"><thead><tr><th>ID</th><th>Логин</th><th>Имя</th><th>Роль</th></tr></thead><tbody id="admin-users-table"></tbody></table>`;
    const tbody = document.getElementById('admin-users-table');
    users.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escapeHTML(u.id)}</td><td>${escapeHTML(u.login)}</td><td>${escapeHTML(u.full_name)}</td><td>${escapeHTML(u.role)}</td>`;
        tbody.appendChild(tr);
    });
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Отчеты</h2>
            <div class="d-flex gap-2">
                <input type="date" id="rep-from" class="form-control form-control-sm" value="${from}">
                <input type="date" id="rep-to" class="form-control form-control-sm" value="${to}">
                <button class="btn btn-primary btn-sm" id="rep-filter">Ок</button>
            </div>
        </div>
        <div id="reports-container" class="row"><div class="col-md-12 text-center">Загрузка данных...</div></div>
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
            <div class="col-md-3"><div class="card bg-light mb-3"><div class="card-body text-center"><h5>Всего</h5><p class="display-6">${s.total}</p></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white mb-3"><div class="card-body text-center"><h5>Рейтинг</h5><p class="display-6">${s.avg_rating}</p></div></div></div>
            <div class="col-md-3"><div class="card bg-danger text-white mb-3"><div class="card-body text-center"><h5>Просрочено</h5><p class="display-6">${s.overdue}</p></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white mb-3"><div class="card-body text-center"><h5>Время (ч)</h5><p class="display-6">${s.avg_res_time_hours}</p></div></div></div>

            <div class="col-md-6 mt-4">
                <h4>Статусы</h4>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">Новые <span>${s.status_dist.new}</span></li>
                    <li class="list-group-item d-flex justify-content-between">В работе <span>${s.status_dist.in_progress}</span></li>
                    <li class="list-group-item d-flex justify-content-between">Выполнены <span>${s.status_dist.completed}</span></li>
                    <li class="list-group-item d-flex justify-content-between">Закрыты <span>${s.status_dist.closed}</span></li>
                </ul>
            </div>
            <div class="col-md-6 mt-4">
                <h4>Приоритеты</h4>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">Высокий <span class="badge bg-danger">${s.priority_dist.high}</span></li>
                    <li class="list-group-item d-flex justify-content-between">Обычный <span class="badge bg-primary">${s.priority_dist.normal}</span></li>
                    <li class="list-group-item d-flex justify-content-between">Низкий <span class="badge bg-secondary">${s.priority_dist.low}</span></li>
                </ul>
            </div>

            <div class="col-md-12 mt-4">
                <h4>Эффективность отделов</h4>
                <table class="table table-sm mt-2">
                    <thead><tr><th>Отдел</th><th>Всего</th><th>Выполнено</th><th>Просрочено</th><th>Рейтинг</th></tr></thead>
                    <tbody>
                        ${s.dept_stats.map(d => `<tr><td>${escapeHTML(d.name)}</td><td>${d.total}</td><td>${d.completed}</td><td class="text-danger">${d.overdue}</td><td>${d.avg_rating}</td></tr>`).join('')}
                    </tbody>
                </table>
            </div>

            <div class="col-md-12 mt-4">
                <h4>Загрузка исполнителей</h4>
                <div id="executor-stats" class="list-group"></div>
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
