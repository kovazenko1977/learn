const API_BASE = '../api';
let token = localStorage.getItem('token');
let currentUser = null;
let workTypes = [];
let users = [];
let systemSettings = {};

const screens = {
    auth: document.getElementById('auth-screen'),
    app: document.getElementById('app-screen'),
    main: document.getElementById('main-content'),
    modal: document.getElementById('modal-overlay'),
    modalBody: document.getElementById('modal-body')
};

document.addEventListener('DOMContentLoaded', async () => {
    applyTheme(localStorage.getItem('mobile-theme') || 'default');

    try {
        const cfgRes = await fetch(`${API_BASE}/auth.php?action=config`);
        systemSettings = await cfgRes.json();
    } catch (e) { console.error('Config fetch failed', e); }

    if (token) {
        const success = await fetchUser();
        if (success) {
            await loadLookups();
            await showApp();
            hideSplashScreen();
        } else {
            showAuth();
            hideSplashScreen();
        }
    } else {
        showAuth();
        hideSplashScreen();
    }
});

function applyTheme(theme) {
    document.body.className = theme === 'default' ? '' : `theme-${theme}`;
    localStorage.setItem('mobile-theme', theme);
    document.querySelectorAll('.theme-opt').forEach(opt => {
        opt.classList.toggle('active', opt.dataset.theme === theme);
    });
}

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

function hideSplashScreen() {
    const splash = document.getElementById('splash-screen');
    if (splash) {
        splash.classList.add('hidden');
        setTimeout(() => splash.remove(), 500);
    }
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

async function showApp() {
    screens.auth.classList.add('hidden');
    screens.app.classList.remove('hidden');

    const perms = currentUser.permissions || {};

    const navRep = document.getElementById('nav-rep-btn');
    if (navRep && !['admin', 'manager'].includes(currentUser.role) && !perms.can_view_reports) {
        navRep.classList.add('hidden');
    }
    const navChat = document.getElementById('nav-chat-btn');
    if (navChat && currentUser.role !== 'admin' && !(perms.can_view_chat ?? true)) {
        navChat.classList.add('hidden');
    }

    const navDept = document.querySelector('.nav-btn[data-view="department"]');
    if (navDept && currentUser.role !== 'admin' && !(perms.can_view_department ?? (currentUser.role !== 'user'))) {
        navDept.classList.add('hidden');
    }

    if (systemSettings.org_name) {
        document.querySelectorAll('.org-name-header').forEach(h => h.innerText = systemSettings.org_name);
    }

    await renderDashboard();
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
    btn.onclick = async () => {
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const view = btn.dataset.view;
        switch(view) {
            case 'dashboard': await renderDashboard(); break;
            case 'create': renderCreate(); break;
            case 'department': await renderDepartment(); break;
            case 'reports': await renderReports(); break;
            case 'profile': renderProfile(); break;
            case 'chat': await renderGlobalChat(); break;
        }
    };
});

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
                    <span class="req-number">${r.number}</span>
                    <span class="status-badge status-${r.status}">${getStatusLabel(r.status)}</span>
                </div>
                <div class="req-desc">${r.description}</div>
                <div class="req-meta">
                    <span><i class="bi bi-calendar3"></i> ${new Date(r.created_at).toLocaleDateString()}</span>
                    <span><i class="bi bi-tag"></i> ${getWorkTypeName(r.work_type_id)}</span>
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
                    <span class="req-number">${r.number}</span>
                    <span class="status-badge status-${r.status}">${getStatusLabel(r.status)}</span>
                </div>
                <div class="req-desc">${r.description}</div>
                <div class="req-meta">
                    <span><i class="bi bi-person"></i> ${getUserName(r.requester_id)}</span>
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
            <input type="text" name="location" placeholder="Корпус, этаж, кабинет" required>
            <span class="label">ОПИСАНИЕ</span>
            <textarea name="description" rows="5" placeholder="Опишите проблему..." required></textarea>
            <span class="label">ФОТО / ФАЙЛ</span>
            <input type="file" name="file">
            <button type="submit"><i class="bi bi-send-fill"></i> ОТПРАВИТЬ</button>
        </form>
    `;

    document.getElementById('create-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await apiFetch('/requests.php?action=create', { method: 'POST', body: formData });
        if (res.ok) {
            document.querySelector('[data-view="dashboard"]').click();
        } else {
            alert('ОШИБКА ПРИ СОЗДАНИИ');
        }
    };
}

async function renderReports() {
    document.getElementById('view-title').innerText = 'АНАЛИТИКА';
    screens.main.innerHTML = '<div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>';
    try {
        const res = await apiFetch('/reports.php?action=summary');
        const s = await res.json();
        screens.main.innerHTML = `
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-lbl">ЗАЯВОК</div>
                    <div class="stat-val">${s.total}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-lbl">РЕЙТИНГ</div>
                    <div class="stat-val">${s.avg_rating}</div>
                </div>
                <div class="stat-box" style="border-color: var(--error);">
                    <div class="stat-lbl" style="color:var(--error);">ПРОСРОЧЕНО</div>
                    <div class="stat-val" style="color:var(--error);">${s.overdue}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-lbl">В РАБОТЕ</div>
                    <div class="stat-val">${s.status_dist.in_progress}</div>
                </div>
            </div>
            <div class="req-card">
                <span class="label">СТАТИСТИКА:</span>
                <div style="font-size: 0.85rem; line-height: 2;">
                    <div>• НОВЫЕ: <b>${s.status_dist.new}</b></div>
                    <div>• В РАБОТЕ: <b>${s.status_dist.in_progress}</b></div>
                    <div>• ВЫПОЛНЕНЫ: <b>${s.status_dist.completed + s.status_dist.closed}</b></div>
                </div>
            </div>
        `;
    } catch (e) { screens.main.innerHTML = 'ОШИБКА'; }
}

function renderProfile() {
    document.getElementById('view-title').innerText = 'ПРОФИЛЬ';
    const currentTheme = localStorage.getItem('mobile-theme') || 'default';
    screens.main.innerHTML = `
        <div class="req-card" style="margin-top: 10px;">
            <span class="label">ПОЛЬЗОВАТЕЛЬ</span>
            <div class="value" style="margin-bottom: 5px;">${currentUser.full_name}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">${currentUser.role.toUpperCase()}</div>
        </div>

        <div class="req-card">
            <span class="label">ВЫБОР ИНТЕРФЕЙСА (ПРЕМИУМ)</span>
            <div class="theme-grid">
                <div class="theme-opt ${currentTheme === 'default' ? 'active' : ''}" data-theme="default" style="background:#6366f1; color:white;">MODERN</div>
                <div class="theme-opt ${currentTheme === 'midnight' ? 'active' : ''}" data-theme="midnight" style="background:#0f172a; color:#fbbf24;">MIDNIGHT</div>
                <div class="theme-opt ${currentTheme === 'emerald' ? 'active' : ''}" data-theme="emerald" style="background:#059669; color:white;">EMERALD</div>
            </div>
        </div>

        <button onclick="location.reload()" style="background:transparent; border: 1px solid var(--border); color:var(--text-main); box-shadow:none;">ОБНОВИТЬ ДАННЫЕ</button>
    `;

    document.querySelectorAll('.theme-opt').forEach(opt => {
        opt.onclick = () => applyTheme(opt.dataset.theme);
    });
}

async function showDetails(id) {
    screens.modal.classList.remove('hidden');
    screens.modalBody.innerHTML = '<div style="text-align:center; padding:40px;"><i class="bi bi-arrow-repeat spin"></i> ЗАГРУЗКА...</div>';
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
            <div class="req-header">
                <span class="req-number">${r.number}</span>
                <span class="status-badge">${getStatusLabel(r.status)}</span>
            </div>

            <span class="label">ОПИСАНИЕ</span>
            <div class="value">${r.description}</div>

            ${r.file_path ? `
                <span class="label">ВЛОЖЕНИЕ</span>
                <div class="value"><a href="../${r.file_path}" target="_blank" style="color:var(--primary); text-decoration:none;"><i class="bi bi-paperclip"></i> ПРОСМОТР ФАЙЛА</a></div>
            ` : ''}

            <div class="stat-grid" style="margin-top: 10px;">
                <div class="stat-box" style="padding: 10px;">
                    <div class="stat-lbl">МЕСТО</div>
                    <div class="stat-val" style="font-size: 0.9rem; margin-top: 5px;">${r.location}</div>
                </div>
                <div class="stat-box" style="padding: 10px;">
                    <div class="stat-lbl">ЗАЯВИТЕЛЬ</div>
                    <div class="stat-val" style="font-size: 0.9rem; margin-top: 5px;">${getUserName(r.requester_id)}</div>
                </div>
            </div>

            <div id="actions" style="margin: 20px 0;"></div>

            <div class="divider"></div>
            <span class="label">ЧАТ С ПОДДЕРЖКОЙ</span>
            <div class="chat-container" id="mobile-chat">
                ${chat.map(c => `
                    <div class="msg">
                        <div class="msg-meta"><span>${getUserName(c.user_id)}</span><span>${new Date(c.created_at).toLocaleTimeString()}</span></div>
                        <div class="msg-text">${c.message}</div>
                    </div>
                `).join('')}
                ${chat.length === 0 ? '<div style="text-align:center; color:var(--text-muted); font-size: 0.8rem; padding: 20px;">СООБЩЕНИЙ НЕТ</div>' : ''}
            </div>
            <div style="display:flex; gap:8px;">
                <input type="text" id="chat-msg" placeholder="Напишите сообщение..." style="margin-bottom:0; flex:1; height: 50px;">
                <button id="chat-send" style="width: 60px; height: 50px; padding: 0;"><i class="bi bi-send-fill"></i></button>
            </div>

            <div class="divider"></div>
            <span class="label">ИСТОРИЯ ИЗМЕНЕНИЙ</span>
            <div style="margin-bottom: 20px;">
                ${history.map(h => `
                    <div class="history-item">
                        <div class="history-meta">${new Date(h.changed_at).toLocaleString()}</div>
                        <div style="font-weight: 600;">${getStatusLabel(h.status)}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${h.comment}</div>
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
                btn.innerHTML = '<i class="bi bi-play-fill"></i> В РАБОТУ';
                btn.onclick = () => updateStatus(id, 'in_progress');
                actions.appendChild(btn);
            } else if (r.status === 'in_progress') {
                const btn = document.createElement('button');
                btn.innerHTML = '<i class="bi bi-check-all"></i> ВЫПОЛНЕНО';
                btn.style.background = 'var(--success)';
                btn.onclick = () => updateStatus(id, 'completed');
                actions.appendChild(btn);
            }
        }

        if (r.requester_id == currentUser.id && r.status === 'completed') {
            const btn = document.createElement('button');
            btn.innerHTML = '<i class="bi bi-lock-fill"></i> ЗАКРЫТЬ ЗАЯВКУ';
            btn.style.background = 'var(--success)';
            btn.onclick = () => updateStatus(id, 'closed', 'ПОДТВЕРЖДЕНО ПОЛЬЗОВАТЕЛЕМ');
            actions.appendChild(btn);

            const rejBtn = document.createElement('button');
            rejBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> НА ДОРАБОТКУ';
            rejBtn.style = 'background:transparent; border: 1px solid var(--error); color:var(--error); margin-top:12px; box-shadow:none;';
            rejBtn.onclick = () => {
                const comment = prompt('Укажите причину возврата:');
                if (comment) updateStatus(id, 'rejected', comment);
            };
            actions.appendChild(rejBtn);
        }
    } catch (e) { screens.modalBody.innerHTML = 'ОШИБКА ПРИ ЗАГРУЗКЕ'; }
}

async function renderGlobalChat() {
    document.getElementById('view-title').innerText = 'ОБЩИЙ ЧАТ';
    screens.main.innerHTML = `
        <div class="chat-container" id="global-chat-box" style="height: calc(100vh - 200px);">
            <div style="text-align:center; padding:20px;">ЗАГРУЗКА...</div>
        </div>
        <div style="display:flex; gap:8px;">
            <input type="text" id="global-chat-input" placeholder="Ваше сообщение..." style="margin-bottom:0; flex:1; height: 50px;">
            <button id="global-chat-send" style="width: 60px; height: 50px; padding: 0;"><i class="bi bi-send-fill"></i></button>
        </div>
    `;

    const box = document.getElementById('global-chat-box');
    const load = async () => {
        try {
            const res = await apiFetch('/chat.php?action=list');
            const msgs = await res.json();
            box.innerHTML = msgs.map(m => `
                <div class="msg" style="${m.user_id == currentUser.id ? 'margin-left: 20%; border-right: 4px solid var(--primary);' : 'margin-right: 20%; border-left: 4px solid var(--accent);'}">
                    <div class="msg-meta"><span>${m.user_name}</span><span>${new Date(m.created_at).toLocaleTimeString()}</span></div>
                    <div class="msg-text">${m.message}</div>
                </div>
            `).join('');
            box.scrollTop = box.scrollHeight;
        } catch (e) { box.innerHTML = 'ОШИБКА ЧАТА'; }
    };

    load();
    const interval = setInterval(() => { if (document.getElementById('global-chat-box')) load(); else clearInterval(interval); }, 5000);

    document.getElementById('global-chat-send').onclick = async () => {
        const input = document.getElementById('global-chat-input');
        const res = await apiFetch('/chat.php?action=send', {
            method: 'POST',
            body: JSON.stringify({ message: input.value })
        });
        if (res.ok) { input.value = ''; load(); }
    };
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
        alert('ОШИБКА ОБНОВЛЕНИЯ');
    }
}

document.getElementById('modal-close').onclick = () => {
    screens.modal.classList.add('hidden');
};
