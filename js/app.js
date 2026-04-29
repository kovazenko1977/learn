const API_BASE = '';
let currentUser = null;
let token = localStorage.getItem('token');
let workTypes = [];
let users = [];
let departments = [];

function escapeHTML(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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
    if (token) {
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
        const [wtRes, dRes] = await Promise.allSettled([
            apiFetch('/api/admin.php?action=worktypes'),
            apiFetch('/api/admin.php?action=departments')
        ]);

        if (wtRes.status === 'fulfilled' && wtRes.value.ok) workTypes = await wtRes.value.json();
        if (dRes.status === 'fulfilled' && dRes.value.ok) departments = await dRes.value.json();

        if (currentUser && currentUser.role === 'admin') {
            const uRes = await apiFetch('/api/admin.php?action=users');
            if (uRes.ok) users = await uRes.json();
        }
    } catch (e) {
        console.error('Lookup loading failed:', e);
    }
}

async function fetchUser() {
    try {
        const res = await fetch(`${API_BASE}/api/auth.php?action=me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (res.ok) {
            currentUser = await res.json();
            return true;
        }
        return false;
    } catch (e) {
        console.error('fetchUser error:', e);
        return false;
    }
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
        const res = await fetch(`${API_BASE}/api/auth.php?action=login`, {
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
            errorEl.innerText = data.message || `Error ${res.status}: ${res.statusText}`;
            errorEl.classList.remove('hidden');
        }
    } catch (err) {
        console.error('Login request failed:', err);
        errorEl.innerText = 'Network error or server unavailable';
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
    options.headers = {
        ...options.headers,
        'Authorization': `Bearer ${token}`
    };
    try {
        const res = await fetch(`${API_BASE}${url}`, options);
        if (res.status === 401) {
            localStorage.removeItem('token');
            location.reload();
        }
        return res;
    } catch (e) {
        console.error('apiFetch error:', e);
        throw e;
    }
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

async function renderDashboard() {
    el.appContent.innerHTML = '<h2>Мои заявки</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Номер</th><th>Тип</th><th>Описание</th><th>Статус</th><th>Дата</th></tr></thead><tbody id="req-table"></tbody></table></div>';
    try {
        const res = await apiFetch('/api/requests.php?action=my');
        const requests = await res.json();
        const tbody = document.getElementById('req-table');
        requests.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td><td>${escapeHTML(getWorkTypeName(r.work_type_id))}</td><td>${escapeHTML(r.description)}</td><td><span class="badge bg-secondary">${escapeHTML(r.status)}</span></td><td>${new Date(r.created_at).toLocaleDateString()}</td>`;
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.req-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                showRequestDetails(link.dataset.id);
            });
        });
    } catch (e) {
        el.appContent.innerHTML += '<div class="alert alert-danger">Ошибка загрузки данных</div>';
    }
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
            const res = await apiFetch('/api/requests.php?action=create', {
                method: 'POST',
                body: formData
            });
            if (res.ok) {
                renderDashboard();
            } else {
                const data = await res.json();
                alert('Ошибка: ' + (data.message || 'Unknown error'));
            }
        } catch (err) { alert('Ошибка сервера'); }
    });
}

async function renderDepartment() {
    el.appContent.innerHTML = '<h2>Заявки отдела</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Номер</th><th>Тип</th><th>Статус</th><th>Исполнитель</th></tr></thead><tbody id="dept-req-table"></tbody></table></div>';
    try {
        const res = await apiFetch('/api/requests.php?action=department');
        const requests = await res.json();
        const tbody = document.getElementById('dept-req-table');
        requests.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td><td>${escapeHTML(getWorkTypeName(r.work_type_id))}</td><td><span class="badge bg-info text-dark">${escapeHTML(r.status)}</span></td><td>${escapeHTML(getUserName(r.assigned_to))}</td>`;
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('.req-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                showRequestDetails(link.dataset.id);
            });
        });
    } catch (e) {
        el.appContent.innerHTML += '<div class="alert alert-danger">Ошибка загрузки данных отдела</div>';
    }
}

async function showRequestDetails(id) {
    try {
        const res = await apiFetch(`/api/requests.php?action=details&id=${id}`);
        const req = await res.json();
        const histRes = await apiFetch(`/api/requests.php?action=history&id=${id}`);
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
            <h6>История</h6>
            <ul class="list-unstyled">
                ${history.map(h => `<li class="small"><strong>${new Date(h.changed_at).toLocaleString()}:</strong> ${escapeHTML(h.status)} - ${escapeHTML(h.comment)}</li>`).join('')}
            </ul>
            <hr>
            <div id="action-buttons"></div>
        `;

        const btnsDiv = document.getElementById('action-buttons');
        if (['admin', 'executor'].includes(currentUser.role) && ['assigned', 'new'].includes(req.status)) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-success btn-sm me-2';
            btn.innerText = 'В работу';
            btn.onclick = () => updateStatus(req.id, 'in_progress', 'Взято в работу');
            btnsDiv.appendChild(btn);
        }
        if (['admin', 'executor', 'manager'].includes(currentUser.role) && req.status === 'in_progress') {
            const btn = document.createElement('button');
            btn.className = 'btn btn-info btn-sm me-2';
            btn.innerText = 'Выполнено';
            btn.onclick = () => updateStatus(req.id, 'completed', 'Работы завершены');
            btnsDiv.appendChild(btn);
        }
        if (req.requester_id == currentUser.id && req.status === 'completed') {
            const btn = document.createElement('button');
            btn.className = 'btn btn-success btn-sm me-2';
            btn.innerText = 'Подтвердить';
            btn.onclick = () => updateStatus(req.id, 'closed', 'Заявка подтверждена');
            btnsDiv.appendChild(btn);
        }

        const modal = new bootstrap.Modal(document.getElementById('requestModal'));
        modal.show();
    } catch (e) { alert('Ошибка загрузки деталей заявки'); }
}

async function updateStatus(id, status, comment) {
    try {
        const res = await apiFetch('/api/requests.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status, comment })
        });
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
            renderDashboard();
        }
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
        <h4>Пользователи</h4>
        <div id="users-list"></div>
    `;
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
    const file = prompt('Введите имя файла бекапа из папки data (например, backup_20260429_120000.zip):');
    if (!file) return;
    try {
        const res = await apiFetch(`/api/admin.php?action=restore&file=${encodeURIComponent(file)}`);
        const data = await res.json();
        alert(data.message);
        location.reload();
    } catch (e) { alert('Ошибка восстановления'); }
};

window.createBackup = async () => {
    try {
        const res = await apiFetch('/api/admin.php?action=backup');
        const data = await res.json();
        alert(`Бекап создан: ${data.file}`);
    } catch (e) { alert('Ошибка создания бекапа'); }
};

window.exportCSV = () => {
    window.open(`${API_BASE}/api/requests.php?action=export&token=${token}`, '_blank');
};

async function renderReports() {
    el.appContent.innerHTML = '<h2>Отчеты</h2><p>Модуль в разработке</p>';
}
