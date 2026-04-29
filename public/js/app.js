const API_BASE = '/api';
let currentUser = null;
let token = localStorage.getItem('token');

// Elements
const loginScreen = document.getElementById('login-screen');
const mainLayout = document.getElementById('main-layout');
const loginForm = document.getElementById('login-form');
const appContent = document.getElementById('app-content');
const mainNav = document.getElementById('main-nav');
const userInfo = document.getElementById('user-info');
const logoutBtn = document.getElementById('logout-btn');

function escapeHTML(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Initial Load
document.addEventListener('DOMContentLoaded', async () => {
    if (token) {
        const success = await fetchUser();
        if (success) {
            showLayout();
            renderDashboard();
        } else {
            showLogin();
        }
    } else {
        showLogin();
    }
});

async function fetchUser() {
    try {
        const res = await fetch(`${API_BASE}/auth/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (res.ok) {
            currentUser = await res.json();
            return true;
        }
        return false;
    } catch (e) {
        return false;
    }
}

function showLogin() {
    loginScreen.classList.remove('hidden');
    mainLayout.classList.add('hidden');
}

function showLayout() {
    loginScreen.classList.add('hidden');
    mainLayout.classList.remove('hidden');
    userInfo.innerText = `${currentUser.full_name} (${currentUser.role})`;

    // Role based visibility
    document.getElementById('nav-admin').classList.toggle('hidden', currentUser.role !== 'admin');
    document.getElementById('nav-reports').classList.toggle('hidden', !['admin', 'manager'].includes(currentUser.role));
    document.getElementById('nav-department').classList.toggle('hidden', currentUser.role === 'user');
}

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const login = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    const errorEl = document.getElementById('login-error');

    try {
        const res = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login, password })
        });
        const data = await res.json();
        if (res.ok) {
            token = data.token;
            currentUser = data.user;
            localStorage.setItem('token', token);
            showLayout();
            renderDashboard();
        } else {
            errorEl.innerText = data.message;
            errorEl.classList.remove('hidden');
        }
    } catch (err) {
        errorEl.innerText = 'Server error';
        errorEl.classList.remove('hidden');
    }
});

logoutBtn.addEventListener('click', () => {
    localStorage.removeItem('token');
    location.reload();
});

// Routing
mainNav.addEventListener('click', (e) => {
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
    const res = await fetch(`${API_BASE}${url}`, options);
    if (res.status === 401) {
        localStorage.removeItem('token');
        location.reload();
    }
    return res;
}

async function renderDashboard() {
    appContent.innerHTML = '<h2>Мои заявки</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Номер</th><th>Описание</th><th>Статус</th><th>Дата</th></tr></thead><tbody id="req-table"></tbody></table></div>';
    const res = await apiFetch('/requests/my');
    const requests = await res.json();
    const tbody = document.getElementById('req-table');
    requests.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td><td>${escapeHTML(r.description)}</td><td><span class="badge bg-secondary">${escapeHTML(r.status)}</span></td><td>${new Date(r.created_at).toLocaleDateString()}</td>`;
        tbody.appendChild(tr);
    });
    tbody.querySelectorAll('.req-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            showRequestDetails(link.dataset.id);
        });
    });
}

async function renderCreate() {
    const [wtRes, deptRes] = await Promise.all([apiFetch('/admin/worktypes'), apiFetch('/admin/departments')]);
    const workTypes = await wtRes.json();

    appContent.innerHTML = `
        <h2>Создать заявку</h2>
        <form id="create-request-form" style="max-width: 600px">
            <div class="mb-3">
                <label class="form-label">Тип работ</label>
                <select id="cr-worktype" class="form-select" required>
                    ${workTypes.map(wt => `<option value="${wt.id}">${escapeHTML(wt.name)}</option>`).join('')}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Приоритет</label>
                <select id="cr-priority" class="form-select">
                    <option value="normal">Обычный</option>
                    <option value="high">Высокий</option>
                    <option value="low">Низкий</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Место выполнения</label>
                <input type="text" id="cr-location" class="form-control" placeholder="Корпус, этаж, кабинет" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание проблемы</label>
                <textarea id="cr-description" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" id="cr-submit" class="btn btn-primary">Отправить</button>
        </form>
    `;

    document.getElementById('create-request-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = {
            work_type_id: document.getElementById('cr-worktype').value,
            priority: document.getElementById('cr-priority').value,
            location: document.getElementById('cr-location').value,
            description: document.getElementById('cr-description').value
        };
        const res = await apiFetch('/requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (res.ok) {
            renderDashboard();
        }
    });
}

async function renderDepartment() {
    appContent.innerHTML = '<h2>Заявки отдела</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Номер</th><th>Тип</th><th>Статус</th><th>Исполнитель</th></tr></thead><tbody id="dept-req-table"></tbody></table></div>';
    const res = await apiFetch('/requests/department');
    const requests = await res.json();
    const tbody = document.getElementById('dept-req-table');
    requests.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><a href="#" class="req-link" data-id="${r.id}">${escapeHTML(r.number)}</a></td><td>${escapeHTML(r.work_type_id)}</td><td><span class="badge bg-info text-dark">${escapeHTML(r.status)}</span></td><td>${escapeHTML(r.assigned_to) || 'Не назначен'}</td>`;
        tbody.appendChild(tr);
    });
    tbody.querySelectorAll('.req-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            showRequestDetails(link.dataset.id);
        });
    });
}

async function showRequestDetails(id) {
    const res = await apiFetch(`/requests/${id}`);
    const req = await res.json();
    const histRes = await apiFetch(`/requests/${id}/history`);
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
                <p><strong>Заявитель ID:</strong> ${escapeHTML(req.requester_id)}</p>
                <p><strong>Исполнитель ID:</strong> ${escapeHTML(req.assigned_to) || 'Не назначен'}</p>
            </div>
        </div>
        <hr>
        <h6>Описание</h6>
        <p>${escapeHTML(req.description)}</p>
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
        btn.onclick = () => takeRequest(req.id);
        btnsDiv.appendChild(btn);
    }
    if (['admin', 'executor', 'manager'].includes(currentUser.role) && req.status === 'in_progress') {
        const btn = document.createElement('button');
        btn.className = 'btn btn-info btn-sm me-2';
        btn.innerText = 'Выполнено';
        btn.onclick = () => completeRequest(req.id);
        btnsDiv.appendChild(btn);
    }
    if (req.requester_id === currentUser.id && req.status === 'completed') {
        const btn = document.createElement('button');
        btn.className = 'btn btn-success btn-sm me-2';
        btn.innerText = 'Подтвердить';
        btn.onclick = () => confirmRequest(req.id);
        btnsDiv.appendChild(btn);
    }

    const modal = new bootstrap.Modal(document.getElementById('requestModal'));
    modal.show();
}

window.takeRequest = async (id) => {
    await apiFetch(`/requests/${id}/take`, { method: 'PATCH' });
    bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
    renderDashboard();
};

window.completeRequest = async (id) => {
    await apiFetch(`/requests/${id}/status`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: 'completed', comment: 'Работы завершены' })
    });
    bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
    renderDashboard();
};

window.confirmRequest = async (id) => {
    await apiFetch(`/requests/${id}/confirm`, { method: 'PATCH' });
    bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
    renderDashboard();
};

async function renderAdmin() {
    appContent.innerHTML = '<h2>Администрирование</h2><div class="row"><div class="col-md-12"><h4>Пользователи</h4><div id="users-list"></div></div></div>';
    const res = await apiFetch('/admin/users');
    const users = await res.json();
    const list = document.getElementById('users-list');
    list.innerHTML = `<table class="table"><thead><tr><th>ID</th><th>Логин</th><th>Имя</th><th>Роль</th></tr></thead><tbody id="admin-users-table"></tbody></table>`;
    const tbody = document.getElementById('admin-users-table');
    users.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escapeHTML(u.id)}</td><td>${escapeHTML(u.login)}</td><td>${escapeHTML(u.full_name)}</td><td>${escapeHTML(u.role)}</td>`;
        tbody.appendChild(tr);
    });
}

async function renderReports() {
    const res = await apiFetch('/reports/summary');
    const summary = await res.json();
    appContent.innerHTML = `
        <h2>Отчеты</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5>Всего заявок</h5>
                        <p class="display-6">${summary.total || 0}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>Выполнено</h5>
                        <p class="display-6">${summary.completed || 0}</p>
                    </div>
                </div>
            </div>
             <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>В работе</h5>
                        <p class="display-6">${summary.in_progress || 0}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}
