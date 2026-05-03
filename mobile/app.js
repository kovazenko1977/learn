const API_BASE = '../api';
let token = localStorage.getItem('token');
let currentUser = null;
let workTypes = [];
let users = [];

const screens = {
    auth: document.getElementById('auth-screen'),
    app: document.getElementById('app-screen'),
    main: document.getElementById('main-content'),
    modal: document.getElementById('modal-overlay'),
    modalBody: document.getElementById('modal-body')
};

document.addEventListener('DOMContentLoaded', async () => {
    if (token) {
        const success = await fetchUser();
        if (success) {
            await loadLookups();
            showApp();
        } else {
            showAuth();
        }
    } else {
        showAuth();
    }
});

async function fetchUser() {
    try {
        const res = await fetch(`${API_BASE}/auth.php?action=me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (res.ok) {
            currentUser = await res.json();
            return true;
        }
        return false;
    } catch (e) { return false; }
}

async function loadLookups() {
    try {
        const [wtRes, uRes] = await Promise.all([
            apiFetch('/admin.php?action=worktypes'),
            apiFetch('/admin.php?action=users')
        ]);
        if (wtRes.ok) workTypes = await wtRes.json();
        if (uRes.ok) users = await uRes.json();
    } catch (e) {}
}

async function apiFetch(url, options = {}) {
    options.headers = { ...options.headers, 'Authorization': `Bearer ${token}` };
    const res = await fetch(`${API_BASE}${url}`, options);
    if (res.status === 401) {
        localStorage.removeItem('token');
        location.reload();
    }
    return res;
}

function showAuth() {
    screens.auth.classList.remove('hidden');
    screens.app.classList.add('hidden');
}

function showApp() {
    screens.auth.classList.add('hidden');
    screens.app.classList.remove('hidden');

    const navRep = document.getElementById('nav-rep-btn');
    if (navRep && !['admin', 'manager'].includes(currentUser.role)) {
        navRep.classList.add('hidden');
    }

    renderDashboard();
}

// LOGIN
document.getElementById('login-form').onsubmit = async (e) => {
    e.preventDefault();
    const login = document.getElementById('login').value;
    const password = document.getElementById('password').value;
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
            showApp();
        } else {
            errorEl.classList.remove('hidden');
        }
    } catch (e) { errorEl.classList.remove('hidden'); }
};

document.getElementById('logout-btn').onclick = () => {
    localStorage.removeItem('token');
    location.reload();
};

// NAV
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.onclick = () => {
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const view = btn.dataset.view;
        switch(view) {
            case 'dashboard': renderDashboard(); break;
            case 'create': renderCreate(); break;
            case 'department': renderDepartment(); break;
            case 'reports': renderReports(); break;
            case 'profile': renderProfile(); break;
        }
    };
});

async function renderDashboard() {
    document.getElementById('view-title').innerText = 'ЗАЯВКИ';
    screens.main.innerHTML = '<div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch('/requests.php?action=my');
        const requests = await res.json();
        screens.main.innerHTML = '';
        requests.forEach(r => {
            const card = document.createElement('div');
            card.className = 'req-card';
            card.innerHTML = `
                <div class="req-header">
                    <span>${r.number}</span>
                    <span class="status-badge">${getStatusLabel(r.status)}</span>
                </div>
                <div class="req-desc">${r.description}</div>
                <div class="req-meta">
                    <span>${new Date(r.created_at).toLocaleDateString()}</span>
                    <span>${getWorkTypeName(r.work_type_id)}</span>
                </div>
            `;
            card.onclick = () => showDetails(r.id);
            screens.main.appendChild(card);
        });
        if (requests.length === 0) screens.main.innerHTML = '<div style="text-align:center; padding:20px;">НЕТ ЗАЯВОК</div>';
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

async function renderDepartment() {
    document.getElementById('view-title').innerText = 'ОТДЕЛ';
    screens.main.innerHTML = '<div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch('/requests.php?action=department');
        const requests = await res.json();
        screens.main.innerHTML = '';
        requests.forEach(r => {
            const card = document.createElement('div');
            card.className = 'req-card';
            card.innerHTML = `
                <div class="req-header">
                    <span>${r.number}</span>
                    <span class="status-badge">${getStatusLabel(r.status)}</span>
                </div>
                <div class="req-desc">${r.description}</div>
                <div class="req-meta">
                    <span>${getUserName(r.requester_id)}</span>
                    <span>→ ${getUserName(r.assigned_to)}</span>
                </div>
            `;
            card.onclick = () => showDetails(r.id);
            screens.main.appendChild(card);
        });
        if (requests.length === 0) screens.main.innerHTML = '<div style="text-align:center; padding:20px;">НЕТ ЗАЯВОК</div>';
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

function renderCreate() {
    document.getElementById('view-title').innerText = 'НОВАЯ ЗАЯВКА';
    screens.main.innerHTML = `
        <form id="create-form">
            <span class="label">ТИП РАБОТ</span>
            <select name="work_type_id" required>
                ${workTypes.map(wt => `<option value="${wt.id}">${wt.name}</option>`).join('')}
            </select>
            <span class="label">ПРИОРИТЕТ</span>
            <select name="priority">
                <option value="normal">ОБЫЧНЫЙ</option>
                <option value="high">ВЫСОКИЙ</option>
                <option value="low">НИЗКИЙ</option>
            </select>
            <span class="label">МЕСТО</span>
            <input type="text" name="location" placeholder="МЕСТО" required>
            <span class="label">ОПИСАНИЕ</span>
            <textarea name="description" rows="5" placeholder="ОПИСАНИЕ" required></textarea>
            <span class="label">ФОТО / ФАЙЛ</span>
            <input type="file" name="file">
            <button type="submit">ОТПРАВИТЬ</button>
        </form>
    `;

    document.getElementById('create-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await apiFetch('/requests.php?action=create', { method: 'POST', body: formData });
        if (res.ok) {
            document.querySelector('[data-view="dashboard"]').click();
        } else {
            alert('ОШИБКА');
        }
    };
}

async function renderReports() {
    document.getElementById('view-title').innerText = 'ОТЧЕТЫ';
    screens.main.innerHTML = '<div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch('/reports.php?action=summary');
        const s = await res.json();
        screens.main.innerHTML = `
            <div class="stat-box">
                <div class="stat-lbl">ВСЕГО ЗАЯВОК</div>
                <div class="stat-val">${s.total}</div>
            </div>
            <div class="stat-box">
                <div class="stat-lbl">СРЕДНИЙ РЕЙТИНГ</div>
                <div class="stat-val">${s.avg_rating}</div>
            </div>
            <div class="stat-box" style="border-color: #f00; color: #f00;">
                <div class="stat-lbl" style="color:#f00;">ПРОСРОЧЕНО</div>
                <div class="stat-val">${s.overdue}</div>
            </div>
            <div class="divider"></div>
            <div style="padding:10px;">
                <span class="label">СТАТУСЫ:</span>
                <div class="history-item">НОВЫЕ: ${s.status_dist.new}</div>
                <div class="history-item">В РАБОТЕ: ${s.status_dist.in_progress}</div>
                <div class="history-item">ВЫПОЛНЕНЫ: ${s.status_dist.completed + s.status_dist.closed}</div>
            </div>
        `;
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

function renderProfile() {
    document.getElementById('view-title').innerText = 'ПРОФИЛЬ';
    screens.main.innerHTML = `
        <div style="padding: 20px;">
            <span class="label">ФИО</span>
            <div class="value">${currentUser.full_name}</div>
            <span class="label">ЛОГИН</span>
            <div class="value">${currentUser.login}</div>
            <span class="label">РОЛЬ</span>
            <div class="value">${currentUser.role}</div>
            <div class="divider"></div>
            <button onclick="location.reload()" style="background:var(--bg); border: 2px solid var(--border); color:var(--fg);">ОБНОВИТЬ</button>
        </div>
    `;
}

async function showDetails(id) {
    screens.modal.classList.remove('hidden');
    screens.modalBody.innerHTML = 'ЗАГРУЗКА...';
    try {
        const [reqRes, histRes, chatRes] = await Promise.all([
            apiFetch(`/requests.php?action=details&id=${id}`),
            apiFetch(`/requests.php?action=history&id=${id}`),
            apiFetch(`/requests.php?action=get_comments&id=${id}`)
        ]);
        const r = await reqRes.json();
        const history = await histRes.json();
        const chat = await chatRes.json();

        screens.modalBody.innerHTML = `
            <span class="label">НОМЕР</span>
            <div class="value">${r.number}</div>
            <span class="label">СТАТУС</span>
            <div class="value">${getStatusLabel(r.status)}</div>
            <span class="label">ОПИСАНИЕ</span>
            <div class="value">${r.description}</div>
            ${r.file_path ? `<div class="value"><a href="../${r.file_path}" target="_blank" style="color:var(--fg);">[ФАЙЛ: ${r.file_original_name}]</a></div>` : ''}
            <span class="label">МЕСТО</span>
            <div class="value">${r.location}</div>
            <span class="label">ЗАЯВИТЕЛЬ</span>
            <div class="value">${getUserName(r.requester_id)}</div>
            <span class="label">ИСПОЛНИТЕЛЬ</span>
            <div class="value">${getUserName(r.assigned_to)}</div>

            <div class="divider"></div>
            <div id="actions"></div>
            <div class="divider"></div>

            <span class="label">ЧАТ</span>
            <div class="chat-container" id="mobile-chat">
                ${chat.map(c => `
                    <div class="msg">
                        <div class="msg-meta"><span>${getUserName(c.user_id)}</span><span>${new Date(c.created_at).toLocaleTimeString()}</span></div>
                        <div class="msg-text">${c.message}</div>
                    </div>
                `).join('')}
            </div>
            <div style="display:flex; gap:5px;">
                <input type="text" id="chat-msg" placeholder="СООБЩЕНИЕ..." style="margin-bottom:0; flex:1;">
                <button id="chat-send" style="width: auto; padding: 10px 20px;">OK</button>
            </div>

            <div class="divider"></div>
            <span class="label">ИСТОРИЯ</span>
            <div style="margin-bottom: 20px;">
                ${history.map(h => `
                    <div class="history-item">
                        <div class="history-meta">${new Date(h.changed_at).toLocaleString()}</div>
                        <div>${getStatusLabel(h.status)}: ${h.comment}</div>
                    </div>
                `).join('')}
            </div>
        `;

        document.getElementById('chat-send').onclick = async () => {
            const msg = document.getElementById('chat-msg').value;
            if (!msg) return;
            const res = await apiFetch('/requests.php?action=add_comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: id, message: msg })
            });
            if (res.ok) showDetails(id);
        };

        const actions = document.getElementById('actions');
        if (['executor', 'admin', 'manager'].includes(currentUser.role)) {
            if (['new', 'assigned'].includes(r.status)) {
                const btn = document.createElement('button');
                btn.innerText = 'В РАБОТУ';
                btn.onclick = () => updateStatus(id, 'in_progress');
                actions.appendChild(btn);
            } else if (r.status === 'in_progress') {
                const btn = document.createElement('button');
                btn.innerText = 'ВЫПОЛНЕНО';
                btn.onclick = () => updateStatus(id, 'completed');
                actions.appendChild(btn);
            }
        }

        if (r.requester_id == currentUser.id && r.status === 'completed') {
            const btn = document.createElement('button');
            btn.innerText = 'ЗАКРЫТЬ';
            btn.onclick = () => updateStatus(id, 'closed');
            actions.appendChild(btn);

            const rejBtn = document.createElement('button');
            rejBtn.innerText = 'ОТКЛОНИТЬ';
            rejBtn.style = 'background:var(--bg); border: 2px solid var(--error); color:var(--error); margin-top:10px;';
            rejBtn.onclick = () => {
                const comment = prompt('ПРИЧИНА:');
                if (comment) updateStatus(id, 'rejected', comment);
            };
            actions.appendChild(rejBtn);
        }
    } catch (e) { screens.modalBody.innerHTML = 'ОШИБКА'; }
}

async function updateStatus(id, status, comment = 'ОБНОВЛЕНО ЧЕРЕЗ MOBILE') {
    const res = await apiFetch('/requests.php?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status, comment })
    });
    if (res.ok) {
        screens.modal.classList.add('hidden');
        renderDashboard();
    } else {
        alert('ОШИБКА');
    }
}

document.getElementById('modal-close').onclick = () => {
    screens.modal.classList.add('hidden');
};

function getStatusLabel(status) {
    const labels = {
        'new': 'НОВАЯ',
        'assigned': 'НАЗНАЧЕНА',
        'in_progress': 'В РАБОТЕ',
        'completed': 'ВЫПОЛНЕНА',
        'closed': 'ЗАКРЫТА',
        'rejected': 'ОТКЛОНЕНА'
    };
    return labels[status] || status;
}

function getWorkTypeName(id) {
    const wt = workTypes.find(w => w.id == id);
    return wt ? wt.name : id;
}

function getUserName(id) {
    if (!id) return '---';
    const u = users.find(user => user.id == id);
    return u ? u.full_name : id;
}
